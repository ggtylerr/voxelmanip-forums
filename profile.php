<?php
require("lib/common.php");

$uid = (int)$_GET['id'] ?? -1;
if ($uid < 0) noticemsg("Error", "You must specify a user ID!", true);

$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$uid]);
if (!$user) noticemsg("Error", "This user does not exist!", true);

pageheader("Profile for ".($user['displayname'] ?: $user['name']));

$days = (time() - $user['regdate']) / 86400;

$thread = $sql->fetch("SELECT p.id, t.title ttitle, f.title ftitle, t.forum FROM forums f
	LEFT JOIN threads t ON t.forum = f.id LEFT JOIN posts p ON p.thread = t.id
	WHERE p.date = ? AND p.user = ? AND ? >= f.minread", [$user['lastpost'], $uid, $loguser['powerlevel']]);

if ($thread) {
	$lastpostlink = sprintf(
		'<br>in <a href="thread.php?pid=%s#%s">%s</a> (<a href="forum.php?id=%s">%s</a>)',
	$thread['id'], $thread['id'], esc($thread['ttitle']), $thread['forum'], esc($thread['ftitle']));
} else if ($user['posts'] == 0) {
	$lastpostlink = '';
} else {
	$lastpostlink = "<br>in <i>a private forum</i>";
}

$themes = themelist();
$themename = $themes[(string)$user['theme'] ?: $defaulttheme];

if ($user['birth'] != -1) {
	//Crudely done code.
	$monthnames = [1 => 'January', 'February', 'March', 'April',
		'May', 'June', 'July', 'August',
		'September', 'October', 'November', 'December'];
	$bdec = explode("-", $user['birth']);
	$mn = intval($bdec[0]);
	if ($bdec['0'] <= 0 && $bdec['0'] > -2)
		$birthday = $monthnames[$mn] . " " . $bdec[1];
	else
		$birthday = date("F j, Y", strtotime($user['birth']));

	$bd1 = new DateTime($user['birth']);
	$bd2 = new DateTime(date("Y-m-d"));
	if (($bd2 < $bd1 && !$bdec['2'] <= 0) || ($bdec['2'] <= 0 && $bdec['2'] > -2))
		$age = '';
	else
		$age = '('.intval($bd1->diff($bd2)->format("%Y")).' years old)';
} else {
	$birthday = $age = '';
}

$email = ($user['email'] && $user['showemail'] ? str_replace(".", "<b> (dot) </b>", str_replace("@", "<b> (at) </b>", esc($user['email']))) : '');

$post['date'] = time();

$post['text'] = <<<HTML
[b]This[/b] is a [i]sample message.[/i] It shows how [u]your posts[/u] will look on the board.
[quote=Anonymous][spoiler]Hello![/spoiler][/quote]
[code]if (true) {\r
	print "The world isn't broken.";\r
} else {\r
	print "Something is very wrong.";\r
}[/code]
[irc]This is like code tags but without formatting.
<Anonymous> I said something![/irc]
[url=]Test Link. Ooh![/url]
HTML;

foreach ($user as $field => $val) {
	$post['u'.$field] = $val;
}

$links = [];
$links[] = ['url' => "forum.php?user=$uid", 'title' => 'View threads'];
$links[] = ['url' => "thread.php?user=$uid", 'title' => 'Show posts'];

$isblocked = $sql->result("SELECT COUNT(*) FROM blockedlayouts WHERE user = ? AND blockee = ?", [$uid, $loguser['id']]);
if ($log) {
	if (isset($_GET['toggleblock'])) {
		if (!$isblocked) {
			$sql->query("INSERT INTO blockedlayouts (user, blockee) VALUES (?,?)", [$uid, $loguser['id']]);
			$isblocked = true;
		} else {
			$sql->query("DELETE FROM blockedlayouts WHERE user = ? AND blockee = ?", [$uid, $loguser['id']]);
			$isblocked = false;
		}
	}

	$links[] = ['url' => "profile.php?id=$uid&toggleblock", 'title' => ($isblocked ? 'Unblock' : 'Block').' layout'];

	if ($loguser['powerlevel'] > 0)
		$links[] = ['url' => "sendprivate.php?uid=$uid", 'title' => 'Send private message'];
}

if ($loguser['powerlevel'] > 3)
	$links[] = ['url' => "private.php?id=$uid", 'title' => 'View private messages'];
if ($loguser['powerlevel'] > 2 && $loguser['powerlevel'] > $user['powerlevel'])
	$links[] = ['url' => "editprofile.php?id=$uid", 'title' => 'Edit user'];

if ($loguser['powerlevel'] > 1) {
	if ($user['powerlevel'] != -1)
		$links[] = ['url' => "banmanager.php?id=$uid", 'title' => 'Ban user'];
	else
		$links[] = ['url' => "banmanager.php?unban&id=$uid", 'title' => 'Unban user'];
}

//timezone calculations
$now = new DateTime("now");
$usertz = new DateTimeZone($user['timezone'] ?: $defaulttimezone);
$userdate = new DateTime("now", $usertz);
$userct = date_format($userdate, $dateformat);
$logtz = new DateTimeZone($loguser['timezone']);
$usertzoff = $usertz->getOffset($now);
$logtzoff = $logtz->getOffset($now);

$profilefields = [
	"General information" => [
		['title' => 'Real handle', 'value' => '<span style="color:#'.powIdToColour($user['powerlevel']).';"><b>'.esc($user['name']).'</b></span>'],
		['title' => 'Group', 'value' => powIdToName($user['powerlevel'])],
		['title' => 'Total posts', 'value' => sprintf('%s (%1.02f per day)', $user['posts'], $user['posts'] / $days)],
		['title' => 'Total threads', 'value' => sprintf('%s (%1.02f per day)' ,$user['threads'], $user['threads'] / $days)],
		['title' => 'Registered on', 'value' => dateformat($user['regdate']).' ('.timeunits($days * 86400).' ago)'],
		['title' => 'Last post', 'value'=>($user['lastpost'] ? dateformat($user['lastpost'])." (".timeunits(time()-$user['lastpost'])." ago)" : "None").$lastpostlink],
		['title' => 'Last view', 'value' => sprintf(
				'%s (%s ago) %s %s',
			dateformat($user['lastview']), timeunits(time() - $user['lastview']),
			($user['url'] ? sprintf('<br>at <a href="%s">%s</a>', esc($user['url']), esc($user['url'])) : ''),
			($loguser['powerlevel'] > 2 ? '<br>from IP: '.$user['ip'] : ''))]
	],
	"User information" => [
		['title' => 'Bio', 'value' => ($user['bio'] ? postfilter($user['bio']) : '')],
		['title' => 'Location', 'value' => ($user['location'] ? esc($user['location']) : '')],
		['title' => 'Email', 'value' => $email],
		['title' => 'Birthday', 'value' => "$birthday $age"],
	],
	"User settings" => [
		['title' => 'Theme', 'value' => esc($themename)],
		['title' => 'Time offset', 'value' => sprintf("%d:%02d from you (Current time: %s)", ($usertzoff - $logtzoff) / 3600, abs(($usertzoff - $logtzoff) / 60) % 60, $userct)],
		['title' => 'Items per page', 'value' => $user['ppp']." posts, ".$user['tpp']." threads"]
	]
];

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main']],
	'title' => ($user['displayname'] ?: $user['name'])
];

RenderPageBar($topbot);

foreach ($profilefields as $k => $v) {
	echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">'.$k.'</td></tr>';
	foreach ($v as $pf) {
		if ($pf['title'] == 'Real handle' && !$user['displayname']) continue;
		echo '<tr><td class="b n1" width="130"><b>'.$pf['title'].'</b></td><td class="b n2">'.$pf['value'].'</td>';
	}
	echo '</table>';
}

?><br>
<table class="c1"><tr class="h"><td class="b h">Sample post</td><tr></table>
<?=threadpost($post)?>
<br>
<table class="c1">
	<tr class="c"><td class="b n3"><ul class="menulisting">
		<?php
		foreach ($links as $link) {
			printf('<li><a href="%s">%s</a></li>', $link['url'], $link['title']);
		}
		?>
	</ul></td></tr>
</table><br>
<?php
RenderPageBar($topbot);
pagefooter();
