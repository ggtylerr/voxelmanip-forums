<?php
require('lib/common.php');
needs_login();

$_POST['action'] = $_POST['action'] ?? null;

if ($action = $_POST['action']) {
	$tid = $_POST['tid'];
} else {
	$tid = $_GET['id'];
}
$action = $action ?? null;

$thread = $sql->fetch("SELECT t.*, f.title ftitle, f.minreply fminreply
	FROM threads t LEFT JOIN forums f ON f.id=t.forum
	WHERE t.id = ? AND ? >= f.minread", [$tid, $loguser['powerlevel']]);

if (!$thread) {
	error("Thread does not exist.");
} else if ($thread['fminreply'] > $loguser['powerlevel']) {
	error("You have no permissions to create posts in this forum!");
} elseif ($thread['closed'] && $loguser['powerlevel'] < 2) {
	error("You can't post in closed threads!");
}

$error = '';
if ($action == 'Submit') {
	$lastpost = $sql->fetch("SELECT id,user,date FROM posts WHERE thread = ? ORDER BY id DESC LIMIT 1", [$thread['id']]);
	if ($lastpost['user'] == $loguser['id'] && $lastpost['date'] >= (time() - 86400) && $loguser['powerlevel'] < 4)
		$error = "You can't double post until it's been at least one day!";
	//if ($lastpost['user'] == $loguser['id'] && $lastpost['date'] >= (time() - 2) && !has_perm('consecutive-posts'))
	//	$error = "You must wait 2 seconds before posting consecutively.";
	if (strlen(trim($_POST['message'])) == 0)
		$error = "Your post is empty! Enter a message and try again.";
	if (strlen(trim($_POST['message'])) < 35)
		$error = "Your post is too short to be meaningful. Please try to write something longer or refrain from posting.";

	if (!$error) {
		$sql->query("UPDATE users SET posts = posts + 1, lastpost = ? WHERE id = ?", [time(), $loguser['id']]);
		$sql->query("INSERT INTO posts (user,thread,date,ip) VALUES (?,?,?,?)",
			[$loguser['id'],$tid,time(),$userip]);

		$pid = $sql->insertid();
		$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
			[$pid,$_POST['message']]);

		$sql->query("UPDATE threads SET replies = replies + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
			[time(), $loguser['id'], $pid, $tid]);

		$sql->query("UPDATE forums SET posts = posts + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
			[time(), $loguser['id'], $pid, $thread['forum']]);

		// nuke entries of this thread in the "threadsread" table
		$sql->query("DELETE FROM threadsread WHERE tid = ? AND NOT (uid = ?)", [$thread['id'], $loguser['id']]);

		redirect("thread.php?pid=$pid#$pid");
	}
}

$topbot = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'], ['href' => "forum.php?id={$thread['forum']}", 'title' => $thread['ftitle']],
		['href' => "thread.php?id={$thread['id']}", 'title' => esc($thread['title'])]
	],
	'title' => "New reply"
];

pageheader('New reply', $thread['forum']);

$message = $_POST['message'] ?? '';

$pid = (int)($_GET['pid'] ?? 0);
if ($pid) {
	$post = $sql->fetch("SELECT u.name name, p.user, pt.text, f.id fid, p.thread, f.minread
			FROM posts p
			LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			LEFT JOIN threads t ON t.id = p.thread
			LEFT JOIN forums f ON f.id = t.forum
			WHERE p.id = ?", [$pid]);

	//does the user have reading access to the quoted post?
	if ($loguser['powerlevel'] < $post['minread']) {
		$post['name'] = 'your overlord';
		$post['text'] = '';
	}

	$message = sprintf('[quote="%s" id="%s"]%s[/quote]', $post['name'], $pid, str_replace("&", "&amp;", $post['text']));
}

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
?><br><?=($error ? noticemsg($error).'<br>' : '')?>
<form action="newreply.php" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan="2">Reply</td></tr>
		<tr>
			<td class="b n1 center" width="120">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center">Post:</td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc($message) ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="tid" value="<?=$tid ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table>
</form><br>
<?php

RenderPageBar($topbot);

pagefooter();
