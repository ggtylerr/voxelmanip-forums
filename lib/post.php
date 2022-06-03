<?php

function userlink_by_name($name) {
	global $sql;
	$u = $sql->fetch("SELECT ".userfields()." FROM users WHERE UPPER(name)=UPPER(?) OR UPPER(displayname)=UPPER(?)", [$name, $name]);
	if ($u)
		return userlink($u, null);
	else
		return 0;
}

function get_username_link($matches) {
	$x = str_replace('"', '', $matches[1]);
	$nl = userlink_by_name($x);
	if ($nl)
		return $nl;
	else
		return $matches[0];
}

function securityfilter($msg) {
	$tags = ':applet|b(?:ase|gsound)|embed|frame(?:set)?|i(?:frame|layer)|layer|meta|noscript|object|plaintext|script|title|textarea|xml|xmp';
	$msg = preg_replace("'<(/?)({$tags})'si", "&lt;$1$2", $msg);

	$msg = preg_replace('@(on)(\w+\s*)=@si', '$1$2&#x3D;', $msg);

	$msg = preg_replace("'-moz-binding'si", ' -mo<z>z-binding', $msg);
	$msg = str_ireplace("expression", "ex<z>pression", $msg);
	$msg = preg_replace("'filter:'si", 'filter&#58;>', $msg);
	$msg = preg_replace("'javascript:'si", 'javascript&#58;>', $msg);
	$msg = preg_replace("'transform:'si", 'transform&#58;>', $msg);

	$msg = str_replace("<!--", "&lt;!--", $msg);

	return $msg;
}

function makecode($match) {
	$code = esc($match[1]);
	$list = ["[", ":", ")", "_", "@", "-"];
	$list2 = ["&#91;", "&#58;", "&#41;", "&#95;", "&#64;", "&#45;"];
	return '<code class="microlight">' . str_replace($list, $list2, $code) . '</code>';
}

function makeirc($match) {
	$code = esc($match[1]);
	$list = ["\r\n", "[", ":", ")", "_", "@", "-"];
	$list2 = ["<br>", "&#91;", "&#58;", "&#41;", "&#95;", "&#64;", "&#45;"];
	return '<table style="width:90%;min-width:90%;"><tr><td class="b n3"><code>' . str_replace($list, $list2, $code) . '</code></table>';
}

function filterstyle($match) {
	$style = $match[2];

	// remove newlines.
	// this will prevent them being replaced with <br> tags and breaking the CSS
	$style = str_replace("\n", '', $style);

	return $match[1] . $style . $match[3];
}

function postfilter($msg) {
	global $smilies;

	//[code] tag
	$msg = preg_replace_callback("'\[code\](.*?)\[/code\]'si", 'makecode', $msg);

	//[irc] variant of [code]
	$msg = preg_replace_callback("'\[irc\](.*?)\[/irc\]'si", 'makeirc', $msg);

	$msg = preg_replace_callback("@(<style.*?>)(.*?)(</style.*?>)@si", 'filterstyle', $msg);

	$msg = securityfilter($msg);

	$msg = str_replace("\n", '<br>', $msg);

	foreach ($smilies as $smiley)
		$msg = str_replace($smiley['text'], sprintf('<img src="%s" align=absmiddle alt="%s" title="%s">', $smiley['url'], $smiley['text'], $smiley['text']), $msg);

	//Relocated here due to conflicts with specific smilies.
	$msg = preg_replace("@(</?(?:table|caption|col|colgroup|thead|tbody|tfoot|tr|th|td|ul|ol|li|div|p|style|link).*?>)\r?\n@si", '$1', $msg);

	$msg = preg_replace("'\[(b|i|u|s)\]'si", '<\\1>', $msg);
	$msg = preg_replace("'\[/(b|i|u|s)\]'si", '</\\1>', $msg);
	$msg = preg_replace("'\[spoiler\](.*?)\[/spoiler\]'si", '<span class="spoiler1" onclick=""><span class="spoiler2">\\1</span></span>', $msg);
	$msg = preg_replace("'\[url\](.*?)\[/url\]'si", '<a href=\\1>\\1</a>', $msg);
	$msg = preg_replace("'\[url=(.*?)\](.*?)\[/url\]'si", '<a href=\\1>\\2</a>', $msg);
	$msg = preg_replace("'\[img\](.*?)\[/img\]'si", '<img src=\\1>', $msg);
	$msg = preg_replace("'\[quote\](.*?)\[/quote\]'si", '<blockquote><hr>\\1<hr></blockquote>', $msg);
	$msg = preg_replace("'\[color=([a-f0-9]{6})\](.*?)\[/color\]'si", '<span style="color: #\\1">\\2</span>', $msg);

	$msg = preg_replace("'\[pre\](.*?)\[/pre\]'si", '<code>\\1</code>', $msg);

	$msg = preg_replace_callback('\'@\"((([^"]+))|([A-Za-z0-9_\-%]+))\"\'si', "get_username_link", $msg);

	// Quotes
	$msg = preg_replace("'\[reply=\"(.*?)\" id=\"(.*?)\"\]'si", '<blockquote><span class="quotedby"><small><i><a href=showprivate.php?id=\\2>Sent by \\1</a></i></small></span><hr>', $msg);
	$msg = preg_replace("'\[quote=\"(.*?)\" id=\"(.*?)\"\]'si", '<blockquote><span class="quotedby"><small><i><a href=thread.php?pid=\\2#\\2>Posted by \\1</a></i></small></span><hr>', $msg);
	$msg = preg_replace("'\[quote=(.*?)\]'si", '<blockquote><span class="quotedby"><i>Posted by \\1</i></span>', $msg);
	$msg = str_replace('[/reply]', '<hr></blockquote>', $msg);
	$msg = str_replace('[/quote]', '<hr></blockquote>', $msg);

	$msg = preg_replace("'>>([0-9]+)'si", '>><a href=thread.php?pid=\\1#\\1>\\1</a>', $msg);

	$msg = preg_replace("'\[youtube\]([\-0-9_a-zA-Z]*?)\[/youtube\]'si", '<iframe width="427" height="240" src="https://www.youtube.com/embed/\\1" frameborder="0" allowfullscreen></iframe>', $msg);

	return $msg;
}

function esc($text) {
	return htmlspecialchars($text);
}

function posttoolbutton($name, $title, $leadin, $leadout) {
	return sprintf(
		'<td>
			<a href="javascript:toolBtn(\'%s\',\'%s\')"><button style="font-size:11pt;" title="%s">%s</button></a>
		</td>',
	$leadin, $leadout, $title, $name);
}

function posttoolbar() {
	return '<table class="postformatting"><tr>'
		. posttoolbutton("B", "Bold", "[b]", "[/b]")
		. posttoolbutton("I", "Italic", "[i]", "[/i]")
		. posttoolbutton("U", "Underline", "[u]", "[/u]")
		. posttoolbutton("S", "Strikethrough", "[s]", "[/s]")
		. "<td>&nbsp;</td>"
		. posttoolbutton("/", "URL", "[url]", "[/url]")
		. posttoolbutton("!", "Spoiler", "[spoiler]", "[/spoiler]")
		. posttoolbutton("&#133;", "Quote", "[quote]", "[/quote]")
		. posttoolbutton(";", "Code", "[code]", "[/code]")
		. "<td>&nbsp;</td>"
		. posttoolbutton("[]", "IMG", "[img]", "[/img]")
		. posttoolbutton("YT", "YouTube", "[youtube]", "[/youtube]")
		. '</tr></table>';
}

function LoadBlocklayouts() {
	global $blocklayouts, $loguser, $log, $sql;
	if (isset($blocklayouts) || !$log) return;

	$blocklayouts = [];
	$rBlocks = $sql->query("SELECT * FROM blockedlayouts WHERE blockee = ?",[$loguser['id']]);
	while ($block = $rBlocks->fetch())
		$blocklayouts[$block['user']] = 1;
}

function threadpost($post, $pthread = '') {
	global $loguser, $blocklayouts, $log;

	if (isset($post['deleted']) && $post['deleted']) {
		$postlinks = '';
		if ($loguser['powerlevel'] > 1) {
			$postlinks = sprintf(
				'<a href="thread.php?pid=%s&pin=%s&rev=%s#%s">Peek</a> | <a href="editpost.php?pid=%s&act=undelete">Undelete</a> | ',
			$post['id'], $post['id'], $post['revision'], $post['id'], $post['id']);
		}
		$postlinks .= 'ID: '.$post['id'];

		$ulink = userlink($post, 'u');
		return <<<HTML
<table class="c1"><tr>
	<td class="b n1" style="border-right:0;width:180px">$ulink</td>
	<td class="b n1" style="border-left:0">
		<table width="100%">
			<td class="nb sfont">(post deleted)</td>
			<td class="nb sfont right">$postlinks</td>
		</table>
	</td>
</tr></table>
HTML;
	}

	$post['uhead'] = str_replace("<!--", "&lt;!--", $post['uhead']);

	$post['ranktext'] = getrank($post['urankset'], $post['uposts']);
	$post['utitle'] = $post['ranktext']
			. ((strlen($post['ranktext']) >= 1) ? '<br>' : '')
			. $post['utitle']
			. ((strlen($post['utitle']) >= 1) ? '<br>' : '');

	// Blocklayouts, supports user/user ($blocklayouts) and user/world (token).
	LoadBlockLayouts(); //load the blocklayout data - this is just once per page.
	if ($log && $loguser['blocklayouts'])
		$isBlocked = true;
	else
		$isBlocked = isset($blocklayouts[$post['uid']]);

	if ($isBlocked)
		$post['usign'] = $post['uhead'] = '';

	$postheaderrow = $threadlink = $postlinks = $revisionstr = '';

	$post['id'] = $post['id'] ?? null;

	if ($pthread)
		$threadlink = ", in <a href=\"thread.php?id=$pthread[id]\">" . esc($pthread['title']) . "</a>";

	if ($post['id'])
		$postlinks = "<a href=\"thread.php?pid=$post[id]#$post[id]\">Link</a>"; // headlinks for posts

	if (isset($post['revision']) && $post['revision'] >= 2)
		$revisionstr = " (rev. {$post['revision']} of " . dateformat($post['ptdate']) . " by " . userlink_by_id($post['ptuser']) . ")";

	if (isset($post['thread']) && $log) {
		if (isset($post['thread']) && $post['id'])
			$postlinks .= " | <a href=\"newreply.php?id=$post[thread]&pid=$post[id]\">Reply</a>";

		// "Edit" link for admins or post owners, but not banned users
		if ($loguser['powerlevel'] > 2 || $loguser['id'] == $post['uid'])
			$postlinks .= " | <a href=\"editpost.php?pid=$post[id]\">Edit</a>";

		if ($loguser['powerlevel'] > 1)
			$postlinks .= " | <a href=\"editpost.php?pid=" . urlencode($post['id']) . "&act=delete\">Delete</a>";

		if ($loguser['powerlevel'] > 2)
			$postlinks .= " | IP: $post[ip]";

		if (isset($post['maxrevision']) && $loguser['powerlevel'] > 1 && $post['maxrevision'] > 1) {
			$revisionstr.=" | Revision ";
			for ($i = 1; $i <= $post['maxrevision']; $i++)
				$revisionstr .= "<a href=\"thread.php?pid=$post[id]&pin=$post[id]&rev=$i#$post[id]\">$i</a> ";
		}
	}

	if (isset($post['thread']))
		$postlinks .= " | ID: $post[id]";

	$tbar1 = "topbar".$post['uid']."_1";
	$tbar2 = "topbar".$post['uid']."_2";
	$sbar = "sidebar".$post['uid'];
	$mbar = "mainbar".$post['uid'];
	$ulink = userlink($post, 'u');
	$pdate = dateformat($post['date']);

	$regdate = date('Y-m-d', $post['uregdate']);
	$lastpost = ($post['ulastpost'] ? timeunits(time() - $post['ulastpost']) : 'none');
	$lastview = timeunits(time() - $post['ulastview']);

	$picture = ($post['uusepic'] ? "<img src=\"userpic/{$post['uid']}\">" : '');

	// TODO: be able to restrict custom layouts
	if ($post['usign']) {
		$signsep = $post['usignsep'] ? '' : '____________________<br>';

		if (!$post['uhead'])
			$post['usign'] = '<br><br><small>' . $signsep . $post['usign'] . '</small>';
		else
			$post['usign'] = '<br><br>' . $signsep . $post['usign'];
	}

	$usertitle = postfilter($post['utitle']);
	$posttext = postfilter($post['uhead'].$post['text'].$post['usign']);

	return <<<HTML
<table class="c1" id="{$post['id']}">
	$postheaderrow
	<tr>
		<td class="b n1 topbar_1 $tbar1" height="17">$ulink</td>
		<td class="b n1 topbar_2 $tbar2">
			<table style="width:100%">
				<tr><td class="nb sfont">Posted on $pdate$threadlink $revisionstr</td><td class="nb sfont right">$postlinks</td></tr>
			</table>
		</td>
	</tr><tr valign="top">
		<td class="b n1 sfont sidebar $sbar">
			$usertitle
			$picture
			<br>Posts: {$post['uposts']}
			<br>
			<br>Since: $regdate
			<br>
			<br>Last post: $lastpost
			<br>Last view: $lastview
		</td>
		<td class="b n2 mainbar $mbar" id="post_{$post['id']}">$posttext</td>
	</tr>
</table>
HTML;
}
