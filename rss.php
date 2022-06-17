<?php
$rss = true;
require('lib/common.php');

header('Content-Type: text/xml');

$threads = $sql->query("SELECT u.id uid, u.name uname, t.*, f.id fid, f.title ftitle
		FROM threads t
		LEFT JOIN users u ON u.id = t.user
		LEFT JOIN forums f ON f.id = t.forum
		WHERE ? >= f.minread
		ORDER BY t.lastdate DESC LIMIT 20",
	[$loguser['powerlevel']]);

$fullurl = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];

?><?xml version="1.0"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
	<title><?=$boardtitle?></title>
	<description>The latest active threads of <?=$boardtitle?></description>
	<link><?=$fullurl?></link>
	<atom:link href="<?=$fullurl?>/rss.php" rel="self" type="application/rss+xml"/>
<?php while ($t = $threads->fetch()) { ?>
	<item>
		<title><?=$t['title']?></title>
		<description>
			New post in thread "<?=$t['title']?>"
			by &lt;a href="<?=$fullurl?>/profile.php?id=<?=$t['uid']?>"&gt;<?=$t['uname']?>&lt;/a&gt;
			in forum &lt;a href="<?=$fullurl?>/forum.php?id=<?=$t['forum']?>"&gt;<?=$t['ftitle']?>&lt;/a&gt;
		</description>
		<pubDate><?=date("r",$t['lastdate'])?></pubDate>
		<category><?=$t['ftitle']?></category>
		<guid><?=$fullurl?>/thread.php?pid=<?=$t['lastid']?>#<?=$t['lastid']?></guid>
	</item>
<?php } ?>
</channel></rss>
