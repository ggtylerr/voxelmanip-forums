<?php

$userbirthdays = [];

function dobirthdays() { //Function for calling after we get the timezone for the user set
	global $sql, $userbirthdays;
	// Check for birthdays globally.
	// Makes stuff like checking for rainbow usernames a lot easier.
	$rbirthdays = $sql->query("SELECT id FROM users WHERE birth LIKE ?", ['%'.date('m-d')]);
	while ($bd = $rbirthdays->fetch())
		$userbirthdays[$bd['id']] = true;
	return;
}

function checkctitle() {
	global $loguser;

	// TODO: allow for users to set their own custom title
	if ($loguser['powerlevel'] > 1) return true;

	return false;
}

function checkcusercolor() {
	global $loguser;

	// TODO: allow for users to set their own custom colour
	if ($loguser['powerlevel'] > 2) return true;

	return false;
}

function getrank($set, $posts) {
	global $rankset_data, $rankset_names;

	if ($set == 0 || !isset($rankset_data[$set])) return '';

	$rankset = &$rankset_data[$rankset_names[$set]];
	for (end($rankset); key($rankset) !== null; prev($rankset)) {
		$currentRank = current($rankset);
		if ($posts >= $currentRank['p']) {
			return $currentRank['str'];
		}
	}
	return '';
}

function randnickcolor() {
	/* OLD HACKISH CODE FOR APRIL 5 */
	$stime = gettimeofday();
	$h = (($stime['usec'] * 10) % 600);
	if ($h < 100) {
		$r = 255;
		$g = 155 + $h;
		$b = 155;
	} elseif ($h < 200) {
		$r = 255 - $h + 100;
		$g = 255;
		$b = 155;
	} elseif ($h < 300) {
		$r = 155;
		$g = 255;
		$b = 155 + $h - 200;
	} elseif ($h < 400) {
		$r = 155;
		$g = 255 - $h + 300;
		$b = 255;
	} elseif ($h < 500) {
		$r = 155 + $h - 400;
		$g = 155;
		$b = 255;
	} else {
		$r = 255;
		$g = 155;
		$b = 255 - $h + 500;
	}
	$rndcolor = substr(dechex($r * 65536 + $g * 256 + $b), -6);
	return $rndcolor;
}

function userfields($tbl = '', $pf = '') {
	$fields = ['id', 'name', 'powerlevel', 'nick_color'];

	$ret = '';
	foreach ($fields as $f) {
		if ($ret)
			$ret .= ',';
		if ($tbl)
			$ret .= $tbl . '.';
		$ret .= $f;
		if ($pf)
			$ret .= ' ' . $pf . $f;
	}

	return $ret;
}

function userfields_post() {
	$ufields = ['posts','regdate','lastpost','lastview','rankset','title','usepic','head','sign','signsep'];
	$fieldlist = '';
	foreach ($ufields as $field)
		$fieldlist .= "u.$field u$field,";
	return $fieldlist;
}

function userlink_by_id($uid) {
	global $sql;
	$u = $sql->fetch("SELECT ".userfields()." FROM users WHERE id=?", [$uid]);
	return userlink($u);
}

function userlink($user, $u = '') {
	return '<a href="profile.php?id='.$user[$u.'id'].'">'.userdisp($user, $u).'</a>';
}

function userdisp($user, $u = '') {
	global $userbirthdays;

	if ($user[$u.'nick_color'] != '000000') //Over-ride for custom colours
		$nc = $user[$u.'nick_color'];
	else
		$nc = powIdToColour($user[$u.'powerlevel']);

	//Random Nick Color on Birthday
	if (isset($userbirthdays[$user[$u.'id']]))
		$nc = randnickcolor();

	$n = $user[$u.'name'] ?: 'null';

	$userdisname = "<span style='color:#$nc;'>".esc($n).'</span>';

	return $userdisname;
}
