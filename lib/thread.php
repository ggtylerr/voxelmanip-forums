<?php

function movethread($id, $forum, $close = 0) {
	global $sql;

	if (!$sql->result("SELECT COUNT(*) FROM forums WHERE id = ?", [$forum])) return;

	$thread = $sql->fetch("SELECT forum, posts FROM threads WHERE id = ?", [$id]);
	$sql->query("UPDATE threads SET forum = ? WHERE id = ?", [$forum, $id]);

	$last1 = $sql->fetch("SELECT lastdate,lastuser,lastid FROM threads WHERE forum = ? ORDER BY lastdate DESC LIMIT 1", [$thread['forum']]);
	$last2 = $sql->fetch("SELECT lastdate,lastuser,lastid FROM threads WHERE forum = ? ORDER BY lastdate DESC LIMIT 1", [$forum]);
	if ($last1)
		$sql->query("UPDATE forums SET posts = posts - ?, threads = threads - 1, lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
		[$thread['posts'], $last1['lastdate'], $last1['lastuser'], $last1['lastid'], $thread['forum']]);

	if ($last2)
		$sql->query("UPDATE forums SET posts = posts + ?, threads = threads + 1, lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
		[$thread['posts'], $last2['lastdate'], $last2['lastuser'], $last2['lastid'], $forum]);
}
