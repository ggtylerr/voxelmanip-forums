<?php
require('lib/common.php');

$_GET['act'] = (isset($_GET['act']) ? $_GET['act'] : 'needle');
$_POST['action'] = (isset($_POST['action']) ? $_POST['action'] : '');

if ($act = $_POST['action']) {
	$pid = $_POST['pid'];
} else {
	$pid = $_GET['pid'];
}

if ($_GET['act'] == 'delete' || $_GET['act'] == 'undelete') {
	$act = $_GET['act'];
	$pid = $pid;
}

needs_login();

$thread = $sql->fetch("SELECT p.user puser, t.*, f.title ftitle FROM posts p LEFT JOIN threads t ON t.id = p.thread "
	."LEFT JOIN forums f ON f.id=t.forum WHERE p.id = ? AND (? >= f.minread OR (t.forum IN (0, NULL) AND t.announce >= 1))", [$pid, $loguser['powerlevel']]);

if (!$thread) $pid = 0;

if ($thread['closed'] && $loguser['powerlevel'] <= 1) {
	$err = "You can't edit a post in closed threads!<br>$threadlink";
} else if ($loguser['powerlevel'] < 3 && $loguser['id'] != $thread['puser']) {
	$err = "You do not have permission to edit this post.<br>$threadlink";
} else if ($pid == -1) {
	$err = "Invalid post ID.<br>$threadlink";
}

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main']],
	'title' => 'Edit post'
];

if ($thread['announce']) {
	array_push($topbot['breadcrumb'], ['href' => 'thread.php?announce=1', 'title' => 'Announcements']);
} else {
	array_push($topbot['breadcrumb'],
		['href' => "forum.php?id={$thread['forum']}", 'title' => $thread['ftitle']],
		['href' => "thread.php?id={$thread['id']}", 'title' => esc($thread['title'])]);
}

$post = $sql->fetch("SELECT u.id, p.user, pt.text FROM posts p
		LEFT JOIN poststext pt ON p.id = pt.id AND p.revision = pt.revision
		LEFT JOIN users u ON p.user = u.id WHERE p.id = ?",
	[$pid]);

if (!isset($post)) $err = "Post doesn't exist.";

$quotetext = esc($post['text']);
if ($act == "Submit" && $post['text'] == $_POST['message']) {
	$err = "No changes detected.<br>$threadlink";
}

if (isset($err)) {
	pageheader('Edit post',$thread['forum']);
	$topbot['title'] .= ' (Error)';
	RenderPageBar($topbot);
	echo '<br>';
	noticemsg("Error", $err);
} else if (!$act) {
	pageheader('Edit post',$thread['forum']);
	RenderPageBar($topbot);
	?><br>
	<form action="editpost.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan=2>Edit Post</td></tr>
		<tr>
			<td class="b n1 center" width=120>Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center" width=120>Post:</td>
			<td class="b n2"><textarea wrap="virtual" name="message" id="message" rows=20 cols=80><?=$quotetext ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="pid" value="<?=$pid ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form>
<?php
} else if ($act == 'Preview') {
	$euser = $sql->fetch("SELECT * FROM users WHERE id = ?", [$post['id']]);
	$post['date'] = time();
	$post['text'] = $_POST['message'];
	foreach ($euser as $field => $val)
		$post['u'.$field] = $val;
	$post['ulastpost'] = time();

	pageheader('Edit post',$thread['forum']);
	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);
	?><br>
	<table class="c1"><tr class="h"><td class="b h" colspan="2">Post preview</table>
	<?=threadpost($post)?><br>
	<form action="editpost.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan=2>Post</td></tr>
		<tr>
			<td class="b n1 center" width="120">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center" width="120">Post:</td>
			<td class="b n2"><textarea wrap="virtual" name="message" id="message" rows="10" cols="80"><?=esc($_POST['message'])?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="pid" value="<?=$pid?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form>
	<?php
} else if ($act == 'Submit') {
	$newrev = $sql->result("SELECT revision FROM posts WHERE id = ?", [$pid]) + 1;

	$sql->query("UPDATE posts SET revision = ? WHERE id = ?", [$newrev, $pid]);

	$sql->query("INSERT INTO poststext (id,text,revision,user,date) VALUES (?,?,?,?,?)",
		[$pid, $_POST['message'], $newrev, $loguser['id'], time()]);

	redirect("thread.php?pid=$pid#edit");
} else if ($act == 'delete' || $act == 'undelete') {
	if ($loguser['powerlevel'] <= 1) {
		pageheader('Edit post',$thread['forum']);
		$topbot['title'] .= ' (Error)';
		RenderPageBar($topbot);
		echo '<br>';
		noticemsg("Error", "You do not have the permission to do this.");
	} else {
		$sql->query("UPDATE posts SET deleted = ? WHERE id = ?", [($act == 'delete' ? 1 : 0), $pid]);
		redirect("thread.php?pid=$pid#edit");
	}
}

echo '<br>';
RenderPageBar($topbot);

pagefooter();
