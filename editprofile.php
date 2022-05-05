<?php
require("lib/common.php");

needs_login();

$targetuserid = $loguser['id'];
$act = isset($_POST['action']) ? $_POST['action'] : '';

if (isset($_GET['id'])) {
	$targetuserid = $_GET['id'];
}

$token = $loguser['token'];
if ($act == 'Edit profile') {
	if ($_POST['token'] !== $token) die('No.');

	if ($_POST['pass'] != '' && $_POST['pass'] == $_POST['pass2'] && $targetuserid == $loguser['id']) {
		$newtoken = bin2hex(random_bytes(32));
		setcookie('token', $newtoken, 2147483647);
	}
}

$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$targetuserid]);

if ($loguser['id'] != $targetuserid && ($loguser['powerlevel'] < 3 || $loguser['powerlevel'] <= $user['powerlevel']))
	noticemsg("Error", "You have no permissions to do this!", true);

if (!$user) noticemsg("Error", "This user doesn't exist!", true);

$user['timezone'] = $user['timezone'] ?: $defaulttimezone;

$canedituser = $loguser['powerlevel'] > 2 && ($loguser['powerlevel'] > $user['powerlevel'] || $targetuserid == $loguser['id']);

if ($act == 'Edit profile') {
	$error = '';

	if ($_POST['pass'] && $_POST['pass2'] && $_POST['pass'] != $_POST['pass2'])
		$error = "- The passwords you entered don't match.<br>";

	$usepic = $user['usepic'];
	$fname = $_FILES['picture'];
	if ($fname['size'] > 0) {
		$ftypes = ['png','jpeg','jpg','gif'];
		$img_data = getimagesize($fname['tmp_name']);
		$err = '';
		if ($img_data[0] > 180 || $img_data[1] > 180)
			$err .= "<br>The image is too big.";
		if ($fname['size'] > 81920)
			$err .= "<br>The image filesize too big.";
		if (!in_array(str_replace('image/','',$img_data['mime']),$ftypes))
			$err = "Invalid file type.";

		if ($err != '')
			$ava_out = $err;
		else {
			if (move_uploaded_file($fname['tmp_name'], "userpic/$user[id]")) {
				$ava_out = "OK!";
			} else {
				$ava_out = "<br>Error creating file.";
			}
		}

		if ($ava_out != "OK!")
			$error .= $ava_out;
		else
			$usepic = 1;
	}
	if (isset($_POST['picturedel']))
		$usepic = 0;

	$pass = (strlen($_POST['pass2']) ? $_POST['pass'] : '');

	//Validate birthday values.
	if (!$_POST['birthM'] || !$_POST['birthD']) //Reject if any are missing.
		$birthday = -1;
	else {
		if (!is_numeric($_POST['birthM']) || !is_numeric($_POST['birthD'])) //Reject if not numeric.
			$birthday = -1;
	}
	if ($_POST['birthM'] > 12 || $_POST['birthD'] > 31) // fixes a small bug where if the fields are above a certain value, the profile fails to load
		$birthday = -1;
	$year = $_POST['birthY'];
	if (!$_POST['birthY'] || !is_numeric($_POST['birthY']))
		$year = -1;
	if ($birthday != -1 && $_POST['birthM'] != '' && $_POST['birthD'] != '')
		$birthday = $year.'-'.str_pad($_POST['birthM'], 2, "0", STR_PAD_LEFT).'-'.str_pad($_POST['birthD'], 2, "0", STR_PAD_LEFT);
	else
		$birthday = -1;

	$dateformat = $_POST['dateformat'];
	$timeformat = $_POST['timeformat'];

	if ($canedituser) {
		$targetgroup = $_POST['powerlevel'];

		if ($targetgroup >= $loguser['powerlevel'] && $targetgroup != $user['powerlevel']) {
			$error .= "- You do not have the permissions to assign this group.<br>";
		}

		$targetname = $_POST['name'];

		if ($sql->result("SELECT COUNT(name) FROM users WHERE (name = ? OR displayname = ?) AND id != ?", [$targetname, $targetname, $user['id']])) {
			$error .= "- Name already in use.<br>";
		}
	}
	if (checkcdisplayname($targetuserid)) {
		//Checks Displayname to name and other displaynames
		$targetdname = $_POST['displayname'];

		if (checkcdisplayname($targetuserid) && $targetdname != '') {
			if ($sql->result("SELECT COUNT(name) FROM users WHERE (name = ? OR displayname = ?) AND id != ?", [$targetdname, $targetdname, $user['id']])) {
				$error .= "- Displayname already in use.<br>";
			}
		}
	}

	if (checkcusercolor($targetuserid)) {
		//Validate Custom username color is a 6 digit hex RGB color
		$_POST['nick_color'] = ltrim($_POST['nick_color'], '#');

		if ($_POST['nick_color'] != '') {
			if (!preg_match('/^([A-Fa-f0-9]{6})$/', $_POST['nick_color'])) {
				$error .= "- Custom usercolor is not a valid RGB hex color.<br>";
			}
		}
	}

	if (!$error) {
		// Temp variables for dynamic query construction.
		$fieldquery = '';
		$placeholders = [];

		$fields = [
			'ppp' => $_POST['ppp'],
			'tpp' => $_POST['tpp'],
			'signsep' => $_POST['signsep'],
			'rankset' => $_POST['rankset'],
			'location' => $_POST['location'] ?: null,
			'email' => $_POST['email'] ?: null,
			'head' => $_POST['head'] ?: null,
			'sign' => $_POST['sign'] ?: null,
			'bio' => $_POST['bio'] ?: null,
			'theme' => $_POST['theme'] != $defaulttheme ? $_POST['theme'] : null,
			'blocklayouts' => $_POST['blocklayouts'] ?: 0,
			'showemail' => isset($_POST['showemail']) ? 1 : 0,
			'timezone' => $_POST['timezone'] != $defaulttimezone ? $_POST['timezone'] : null,
			'birth' => $birthday,
			'usepic' => $usepic,
			'dateformat' => $dateformat,
			'timeformat' => $timeformat
		];

		if ($pass) {
			$fields['pass'] = password_hash($pass, PASSWORD_DEFAULT);
			$fields['token'] = $newtoken;
		}

		if (checkcdisplayname($targetuserid))
			$fields['displayname'] = $_POST['displayname'];

		if (checkcusercolor($targetuserid))
			$fields['nick_color'] = $_POST['nick_color'];

		if (checkctitle($targetuserid))
			$fields['title'] = $_POST['title'];

		if ($targetgroup != 0)
			$fields['powerlevel'] = $targetgroup;

		// Construct a query containing all fields.
		foreach ($fields as $fieldk => $fieldv) {
			if ($fieldquery) $fieldquery .= ',';
			$fieldquery .= $fieldk.'=?';
			$placeholders[] = $fieldv;
		}

		// 100% safe from SQL injection because no arbitrary user input is ever put directly
		// into the query, rather it is passed as a prepared statement placeholder.
		$placeholders[] = $user['id'];
		$sql->query("UPDATE users SET $fieldquery WHERE id = ?", $placeholders);

		redirect("profile.php?id=$user[id]");
	} else {
		noticemsg("Error", "Couldn't save the profile changes. The following errors occured:<br><br>" . $error);

		$act = '';
		foreach ($_POST as $k => $v)
			$user[$k] = $v;
		$user['birth'] = $birthday;
	}
}

pageheader('Edit profile');

$listtimezones = [];
foreach (timezone_identifiers_list() as $tz) {
	$listtimezones[$tz] = $tz;
}

$birthM = $birthD = $birthY = '';
if ($user['birth'] && $user['birth'] != -1) {
	$birthday = explode('-', $user['birth']);
	$birthY = $birthday[0]; $birthM = $birthday[1]; $birthD = $birthday[2];
}

$passinput = '<input type="password" name="pass" size="13" maxlength="32"> Retype: <input type="password" name="pass2" size="13" maxlength="32">';
$birthinput = sprintf(
	'Day: <input type="text" name="birthD" size="2" maxlength="2" value="%s">
	Month: <input type="text" name="birthM" size="2" maxlength="2" value="%s">
	Year: <input type="text" name="birthY" size="4" maxlength="4" value="%s">',
$birthD, $birthM, $birthY);

$colorinput = sprintf(
	'<input type="color" name="nick_color" value="#%s">',
$user['nick_color']);

echo '<form action="editprofile.php?id='.$targetuserid.'" method="post" enctype="multipart/form-data"><table class="c1">' .
	catheader('Login information')
.($canedituser ? fieldrow('Username', fieldinput(40, 255, 'name')) : fieldrow('Username', $user['name']))
.(checkcdisplayname($targetuserid) ? fieldrow('Display name', fieldinput(40, 255, 'displayname')) : '')
.fieldrow('Password', $passinput);

if ($canedituser)
	echo
	catheader('Administrative bells and whistles')
.fieldrow('Group', fieldselect('powerlevel', $user['powerlevel'], $powerlevels))
.(($user['tempbanned'] > 0) ? fieldrow('Ban Information', '<input type=checkbox name=permaban value=1 id=permaban><label for=permaban>Make ban permanent</label>') : '');

echo
	catheader('Appearance')
.fieldrow('Rankset', fieldselect('rankset', $user['rankset'], ranklist()))
.((checkctitle($targetuserid)) ? fieldrow('Title', fieldinput(40, 255, 'title')) : '')
.fieldrow('Picture', '<input type=file name=picture size=40> <input type=checkbox name=picturedel value=1 id=picturedel><label for=picturedel>Erase</label>
	<br><span class=sfont>Must be PNG, JPG or GIF, within 80KB, within 180x180.</span>')
.(checkcusercolor($targetuserid) ? fieldrow('Custom username color', $colorinput) : '')
.	catheader('User information')
.fieldrow('Location', fieldinput(40, 60, 'location'))
.fieldrow('Birthday', $birthinput)
.fieldrow('Bio', fieldtext(5, 80, 'bio'))
.fieldrow('Email address', fieldinput(40, 60, 'email')
				.'<br>'.fieldcheckbox('showemail', $user['showemail'], 'Show email on profile page'))
.	catheader('Post layout')
.fieldrow('Header', fieldtext(5, 80, 'head'))
.fieldrow('Signature', fieldtext(5, 80, 'sign'))
.fieldrow('Signature line', fieldoption('signsep', $user['signsep'], ['Display', 'Hide']))
.	catheader('Options')
.fieldrow('Theme', fieldselect('theme', $user['theme'], themelist(), "themePreview(this.value)"))
.fieldrow('Timezone', fieldselect('timezone', $user['timezone'], $listtimezones))
.fieldrow('Posts per page', fieldinput(3, 3, 'ppp'))
.fieldrow('Threads per page', fieldinput(3, 3, 'tpp'))
.fieldrow('Date format', fieldinput(15, 15, 'dateformat'))
.fieldrow('Time format', fieldinput(15, 15, 'timeformat'))
.fieldrow('Post layouts', fieldoption('blocklayouts', $user['blocklayouts'], ['Show everything in general', 'Block everything']))
.	catheader('&nbsp;'); ?>
<tr class="n1"><td class="b"></td><td class="b"><input type="submit" name="action" value="Edit profile"></td>
</table><input type="hidden" name="token" value="<?=$token?>"></form>
<script>
function themePreview(id) {
	document.head.getElementsByTagName('link')[2].href = 'theme/'+id+'/'+id+'.css';
}
</script><?php

pagefooter();
