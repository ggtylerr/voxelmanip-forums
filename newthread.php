<?php
require('lib/common.php');
needs_login();

if (!isset($_POST['action'])) $_POST['action'] = '';
if ($action = $_POST['action']) {
	$fid = $_POST['fid'];
} else {
	$fid = $_GET['id'] ?? 0;
}

$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND ? >= minread", [$fid, $loguser['powerlevel']]);

if (!$forum)
	noticemsg("Error", "Forum does not exist.", true);
if ($forum['minthread'] > $loguser['powerlevel'])
	noticemsg("Error", "You have no permissions to create threads in this forum!", true);

if ($action == 'Submit') {
	if ($loguser['lastpost'] > time() - 30 && $loguser['powerlevel'] < 4)
		$error = "Don't post threads so fast, wait a little longer.";
	if (strlen(trim(str_replace(' ', '', $_POST['title']))) < 4)
		$error = "You need to enter a longer title.";
	//if ($loguser['lastpost'] > time() - 2 && has_perm('ignore-thread-time-limit'))
	//	$error = "You must wait 2 seconds before posting a thread.";

	if (!$error) {
		$sql->query("UPDATE users SET posts = posts + 1,threads = threads + 1,lastpost = ? WHERE id = ?", [time(), $loguser['id']]);
		$sql->query("INSERT INTO threads (title,forum,user,lastdate,lastuser) VALUES (?,?,?,?,?)",
			[$_POST['title'],$fid,$loguser['id'],time(),$loguser['id']]);

		$tid = $sql->insertid();
		$sql->query("INSERT INTO posts (user,thread,date,ip) VALUES (?,?,?,?)",
			[$loguser['id'],$tid,time(),$userip]);

		$pid = $sql->insertid();
		$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
			[$pid,$_POST['message']]);

		$sql->query("UPDATE forums SET threads = threads + 1, posts = posts + 1, lastdate = ?,lastuser = ?,lastid = ? WHERE id = ?",
			[time(), $loguser['id'], $pid, $fid]);

		$sql->query("UPDATE threads SET lastid = ? WHERE id = ?", [$pid, $tid]);

		redirect("thread.php?id=$tid");
	}
}

$topbot = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'],
		['href' => "forum.php?id=$fid", 'title' => $forum['title']]
	],
	'title' => "New thread"
];

pageheader("New thread", $forum['id']);

$title = $_POST['title'] ?? '';
$message = $_POST['message'] ?? '';

if ($action == 'Preview') {
	$post['date'] = $post['ulastpost'] = time();
	$loguser['posts']++;
	$post['text'] = $message;
	foreach ($loguser as $field => $val)
		$post['u'.$field] = $val;

	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);

	echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">Post preview</table>'.threadpost($post);
} else {
	RenderPageBar($topbot);
}
?><br><?=($error ? noticemsg('Error', $error).'<br>' : '')?>
<form action="newthread.php" method="post"><table class="c1">
	<tr class="h"><td class="b h" colspan="2">Thread</td></tr>
	<tr>
		<td class="b n1 center" width="120">Thread title:</td>
		<td class="b n2"><input type="text" name="title" size="100" maxlength="100" value="<?=esc($title) ?>"></td>
	</tr><tr>
		<td class="b n1 center">Format:</td>
		<td class="b n2"><?=posttoolbar() ?></td>
	</tr><tr>
		<td class="b n1 center">Post:</td>
		<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc($message) ?></textarea></td>
	</tr><tr>
		<td class="b n1"></td>
		<td class="b n1">
			<input type="hidden" name="fid" value="<?=$fid ?>">
			<input type="submit" name="action" value="Submit">
			<input type="submit" name="action" value="Preview">
		</td>
	</tr>
</table></form><br>
<?php

RenderPageBar($topbot);

pagefooter();
