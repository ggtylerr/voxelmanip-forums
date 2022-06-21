<?php
if (!file_exists('conf/config.php'))
	die('Great job getting the files onto a web server. Now install it.');

$start = microtime(true);

$rankset_names = ['None'];

require('conf/config.php');
foreach (glob("lib/*.php") as $filename)
	require_once($filename);

$userip = $_SERVER['REMOTE_ADDR'];
$useragent = $_SERVER['HTTP_USER_AGENT'];
$url = $_SERVER['REQUEST_URI'];

$log = false;

if (isset($_COOKIE['token'])) {
	if ($sql->result("SELECT id FROM users WHERE token = ?", [$_COOKIE['token']])) {
		$log = true;
		$loguser = $sql->fetch("SELECT * FROM users WHERE token = ?", [$_COOKIE['token']]);
	} else {
		setcookie('token', 0);
	}
}

if (!$log) {
	$loguser = [];
	$loguser['id'] = $loguser['powerlevel'] = 0;
	$loguser['dateformat'] = "Y-m-d";
	$loguser['timeformat'] = "H:i";
	$loguser['theme'] = $defaulttheme;
	$loguser['ppp'] = $loguser['tpp'] = 20;
}

if ($lockdown) {
	if ($loguser['powerlevel'] < 1) {
		echo <<<HTML
<body style="background-color:#B02020;max-width:500px;color:#ffffff;margin:40px auto;">
	<p>The board is currently in maintenance mode.</p>
	<p>Please forgive any inconvenience caused and stand by until the underlying issues have been resolved.</p>
</body>
HTML;
		die();
	}
}

if (!$log || !$loguser['timezone'])
	$loguser['timezone'] = $defaulttimezone;

$dateformat = $loguser['dateformat'].' '.$loguser['timeformat'];

date_default_timezone_set($loguser['timezone']);
dobirthdays(); //Called here to account for timezone bugs.

if ($loguser['ppp'] < 1) $loguser['ppp'] = 20;
if ($loguser['tpp'] < 1) $loguser['tpp'] = 20;

$theme = $override_theme ?? ($_GET['theme'] ?? $loguser['theme']);

if (!is_file("theme/$theme/$theme.css"))
	$theme = $defaulttheme;

//Unban users whose tempbans have expired.
$sql->query("UPDATE users SET powerlevel = 1, title = '', tempbanned = 0 WHERE tempbanned < ? AND tempbanned > 0", [time()]);

$bot = 0;
if (str_replace($botlist, "x", strtolower($useragent)) != strtolower($useragent))
	$bot = 1;

if (!isset($rss)) {
	$sql->query("DELETE FROM guests WHERE ip = ? OR date < ?", [$userip, (time() - 300)]);
	if ($log)
		$sql->query("UPDATE users SET lastview = ?, ip = ?, url = ? WHERE id = ?",
			[time(), $userip, $url, $loguser['id']]);
	else
		$sql->query("INSERT INTO guests (date, ip, bot) VALUES (?,?,?)", [time(),$userip,$bot]);

	if (!$bot)
		$sql->query("UPDATE misc SET views = views + 1");
}

$sql->query("DELETE FROM ipbans WHERE expires < ? AND expires > 0", [time()]);

$r = $sql->fetch("SELECT * FROM ipbans WHERE ? LIKE ipmask", [$userip]);
if ($r) {
	pageheader('IP banned');
	echo '<table class="c1"><tr class="n2"><td class="b n1 center">Sorry, but your IP address has been banned.</td></tr></table>';
	pagefooter();
	die();
}

function pageheader($pagetitle = '', int $fid = null) {
	global $sql, $log, $loguser, $boardtitle, $boardlogo, $theme, $boarddesc, $rankset_names;

	if ($log)
		$sql->query("UPDATE users SET lastforum = ? WHERE id = ?", [($fid == null ? 0 : $fid), $loguser['id']]);
	else
		$sql->query("UPDATE guests SET lastforum = ? WHERE ip = ?", [($fid == null ? 0 : $fid), $_SERVER['REMOTE_ADDR']]);

	if ($pagetitle) $pagetitle .= " - ";

	$boardlogo = '<a href="./"><img src="'.$boardlogo.'" style="max-width:100%"></a>';

	$attn = $sql->result("SELECT attention FROM misc");
	if ($attn)
		$boardlogo = <<<HTML
<table width="100%"><tr>
	<td>$boardlogo</td>
	<td width="300">
		<table class="c1 center">
			<tr class="h"><td class="b h">News</td></tr>
			<tr class="n1 center"><td class="b sfont">$attn</td></tr>
		</table>
	</td>
</tr></table>
HTML;

	?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
		<title><?=$pagetitle.$boardtitle?></title>
		<?php if (isset($boarddesc)) { ?><meta name="description" content="<?=$boarddesc?>"><?php } ?>
		<link rel="stylesheet" href="theme/common.css">
		<link rel="stylesheet" href="theme/<?=$theme?>/<?=$theme?>.css">
		<link href="rss.php" type="application/atom+xml" rel="alternate">
		<script src="js/tools.js"></script>
	</head>
	<body>
		<table class="c1">
			<tr class="nt n2 center"><td class="b n1 center" colspan="3"><?=$boardlogo?></td></tr>
			<tr class="n2">
				<td class="nb headermenu">
					<a href="./">Main</a>
					<a href="faq.php">FAQ</a>
					<a href="memberlist.php">Memberlist</a>
					<a href="activeusers.php">Active users</a>
					<a href="thread.php?time=86400">Latest posts</a>
					<?php if (count($rankset_names) > 1) { ?><a href="ranks.php">Ranks</a><?php } ?>
					<a href="online.php">Online users</a>
					<a href="search.php">Search</a>
				</td>
				<td class="nb headermenu_right"><?php
	if ($log) {
		$unreadpms = $sql->result("SELECT COUNT(*) FROM pmsgs WHERE userto = ? AND unread = 1 AND del_to = 0", [$loguser['id']]);

		printf(
			'<span class="menulink">'.userlink($loguser).' <a href="private.php"><img src="img/pm%s.png" width="20" alt="Private messages"></a> %s</span>  ',
		(!$unreadpms ? '-off' : ''), ($unreadpms ? "($unreadpms new)" : ''));
	}

	$userlinks = [];

	if ($log) {
		if ($loguser['powerlevel'] > 0)
			$userlinks[] = ['url' => "editprofile.php", 'title' => 'Edit profile'];
		if ($loguser['powerlevel'] > 2)
			$userlinks[] = ['url' => 'management.php', 'title' => 'Admin'];
	}

	if (!$log) {
		$userlinks[] = ['url' => "register.php", 'title' => 'Register'];
		$userlinks[] = ['url' => "login.php", 'title' => 'Login'];
	} else
		$userlinks[] = ['url' => "javascript:document.logout.submit()", 'title' => 'Logout'];

	foreach ($userlinks as $v)
		echo "<a class=\"menulink\" href=\"{$v['url']}\">{$v['title']}</a> ";

	?></td></table>
	<form action="login.php" method="post" name="logout">
		<input type="hidden" name="action" value="logout">
	</form><br><?php

	if ($fid || $fid == 0) {
		$onusers = $sql->query("SELECT ".userfields().",lastpost,lastview FROM users WHERE lastview > ? ".($fid != 0 ? " AND lastforum =".$fid : '')." ORDER BY name",
			[(time()-300)]);
		$onuserlist = '';
		$onusercount = 0;
		while ($user = $onusers->fetch()) {
			$onuserlist.=($onusercount ? ', ' : '') . userlink($user);
			$onusercount++;
		}

		$result = $sql->query("SELECT COUNT(*) guest_count, SUM(bot) bot_count FROM guests WHERE date > ?".($fid != 0 ? " AND lastforum =".$fid : ''),
			[(time()-300)]);

		while ($data = $result->fetch()) {
			$numbots = $data['bot_count'];
			$numguests = $data['guest_count'] - $numbots;

			if ($numguests)	$onuserlist .= " | $numguests guest" . ($numguests != 1 ? "s" : '');
			if ($numbots)	$onuserlist .= " | $numbots bot" . ($numbots != 1 ? "s" : '');
		}
	}

	if ($fid) {
		$fname = $sql->result("SELECT title FROM forums WHERE id = ?", [$fid]);
		$onuserlist = "$onusercount user" . ($onusercount != 1 ? "s" : '') . " currently in $fname" . ($onusercount > 0 ? ": " : '') . $onuserlist;

		?><table class="c1"><tr class="n1"><td class="b n1 center"><?=$onuserlist ?></td></tr></table><br><?php
	} else if (isset($fid) && $fid == 0) {

		$rbirthdays = $sql->query("SELECT birth, ".userfields()." FROM users WHERE birth LIKE ? ORDER BY name", ['%'.date('m-d')]);

		$birthdays = [];
		while ($user = $rbirthdays->fetch()) {
			$b = explode('-', $user['birth']);
			$birthdays[] = userlink($user)." (".(date("Y") - $b['0']).")";
		}

		$birthdaybox = '';
		if (count($birthdays))
			$birthdaybox = '<tr class="n1 center"><td class="b n2 center">Birthdays today: '.implode(", ", $birthdays).'</td></tr>';

		$count = $sql->fetch("SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM threads) t, (SELECT COUNT(*) FROM posts) p");

		$count['d'] = $sql->result("SELECT COUNT(*) FROM posts WHERE date > ?", [(time() - 86400)]);
		$count['h'] = $sql->result("SELECT COUNT(*) FROM posts WHERE date > ?", [(time() - 3600)]);
		$lastuser = $sql->fetch("SELECT ".userfields()." FROM users ORDER BY id DESC LIMIT 1");

		$onuserlist = "$onusercount user" . ($onusercount != 1 ? 's' : '') . ' online' . ($onusercount > 0 ? ': ' : '') . $onuserlist;

		?><table class="c1">
			<?=$birthdaybox ?>
			<tr><td class="b n1">
				<table style="width:100%"><tr>
					<td class="nb" width="200"></td>
					<td class="nb center" style="min-width:100px"><span class="white-space:nowrap">
						<?=$count['t'] ?> threads and <?=$count['p'] ?> posts total.<br><?=$count['d'] ?> new posts
						today, <?=$count['h'] ?> last hour.<br>
					</span></td>
					<td class="nb right" width="200">
						<?=$count['u'] ?> registered users<br> Newest: <?=userlink($lastuser) ?>
					</td>
				</tr></table>
			</td></tr>
			<tr><td class="b n2 center"><?=$onuserlist ?></td></tr>
		</table><br><?php
	}
}

function noticemsg($msg, $title = "Error") {
	?><table class="c1">
		<tr class="h"><td class="b h center"><?=$title ?></td></tr>
		<tr><td class="b n1 center"><?=$msg ?></td></tr>
	</table><?php
}

function error($msg) {
	pageheader('Error');
	noticemsg($msg.'<br><a href="./">Back to main</a>', 'Error');
	pagefooter();
	die();
}

function pagefooter() {
	global $start;
	$time = microtime(true) - $start;
	?><br>
	<table class="c1">
		<tr><td class="b n2 sfont footer">
			<span class="stats">
				<?=sprintf("Page rendered in %1.3f seconds. (%dKB of memory used)", $time, memory_get_usage(false) / 1024); ?>
			</span>

			<img src="img/poweredbyvoxelmanip.png" class="poweredby"
				title="like a warm hug from someone you love">

			Voxelmanip Forums (commit <?=gitCommit(true)?>)<br>
			&copy; 2022 ROllerozxa, <a href="credits.php">et al</a>.
		</td></tr>
	</table><?php
}

function gitCommit($trim = true) {
	$commit = file_get_contents('.git/refs/heads/master');

	if ($trim)
		return substr($commit, 0, 7);
	else
		return rtrim($commit);
}
