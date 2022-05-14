<?php

function redirect($url) {
	header("Location: ".$url);
	die();
}

function rendernewstatus($type) {
	if (!$type) return '';

	switch ($type) {
		case "n":
			$text = "NEW";
			$statusimg = "new.png";
		break;
		case "o":
			$text = "OFF";
			$statusimg = "off.png";
		break;
		case "on":
			$text = "OFF";
			$statusimg = "offnew.png";
		break;
	}

	return "<img src=\"img/status/$statusimg\" alt=\"$text\">";
}

function RenderActions($actions, $ret = false) {
	$out = '';
	$i = 0;
	foreach ($actions as $action) {
		if ($i++) $out .= ' | ';

		if (isset($action['href']))
			$out .= sprintf('<a href="%s">%s</a>', htmlentities($action['href'], ENT_QUOTES), $action['title']);
		else
			$out .= $action['title'];
	}
	if ($ret)
		return $out;
	else
		echo $out;
}

function RenderBreadcrumb($breadcrumb) {
	foreach ($breadcrumb as $action)
		printf('<a href=%s>%s</a> - ', '"'.htmlentities($action['href'], ENT_QUOTES).'"', $action['title']);
}

function RenderPageBar($pagebar) {
	if (empty($pagebar)) return;

	echo "<table width=100%><td class=nb>";
	if (!empty($pagebar['breadcrumb']))
		RenderBreadcrumb($pagebar['breadcrumb']);
	echo $pagebar['title'].'</td><td class="nb right">';
	if (!empty($pagebar['actions']))
		RenderActions($pagebar['actions']);
	else
		echo "&nbsp;";
	echo "</td></table>";
}

function catheader($title) {
	return sprintf('<tr class="h"><td class="b h" colspan="2">%s</td>', $title);
}

function fieldrow($title, $input) {
	return sprintf('<tr><td class="b n1 center">%s:</td><td class="b n2">%s</td>', $title, $input);
}

function fieldinput($size, $max, $field, $value = null) {
	global $user;
	$val = str_replace('"', '&quot;', ($value ?? $user[$field]));
	return sprintf('<input type="text" name="%s" size="%s" maxlength="%s" value="%s">', $field, $size, $max, $val);
}

function fieldtext($rows, $cols, $field) {
	global $user;
	return sprintf('<textarea wrap="virtual" name="%s" rows=%s cols=%s>%s</textarea>', $field, $rows, $cols, esc($user[$field]));
}

function fieldoption($field, $checked, $choices) {
	$text = '';
	foreach ($choices as $k => $v)
		$text .= sprintf('<label><input type="radio" name="%s" value="%s" %s>%s </label>', $field, $k, ($k == $checked ? ' checked' : ''), $v);
	return $text;
}

function fieldcheckbox($field, $checked, $label) {
	return sprintf('<label><input type="checkbox" name="%s" value="1" %s>%s </label>', $field, ($checked ? ' checked' : ''), $label);
}

function fieldselect($field, $checked, $choices, $onchange = '') {
	if ($onchange != '')
		$onchange = ' onchange="'.$onchange.'"';
	$text = sprintf('<select name="%s"%s>', $field, $onchange);
	foreach ($choices as $k => $v)
		$text .= sprintf('<option value="%s"%s>%s</option>', $k, ($k == $checked ? ' selected' : ''), $v);
	$text .= '</select>';
	return $text;
}

function bantimeselect($name) {
	$selector = [
		"0"			=> "Never",
		"3600"		=> "1 hour",
		"10800"		=> "3 hours",
		"86400"		=> "1 day",
		"172800"	=> "2 days",
		"259200"	=> "3 days",
		"604800"	=> "1 week",
		"1209600"	=> "2 weeks",
		"2419200"	=> "1 month",
		"4838400"	=> "2 months",
		"14515200"	=> "6 months",
	];
	return fieldselect($name, 0, $selector);
}

function pagelist($total, $limit, $url, $sel = 0, $showall = false, $tree = false) {
	$pagelist = '';
	$pages = ceil($total / $limit);
	if ($pages < 2) return '';
	for ($i = 1; $i <= $pages; $i++) {
		if (	$showall	// If we don't show all the pages, show:
			|| ($i < 7 || $i > $pages - 7)		// First / last 7 pages
			|| ($i > $sel - 5 && $i < $sel + 5)	// 10 choices around the selected page
			|| !($i % 10)						// Show 10, 20, etc...
		) {
			if ($i == $sel)
				$pagelist .= " $i";
			else
				$pagelist .= " <a href=\"$url&page=$i\">$i</a>";
		} else if (substr($pagelist, -1) != '.') {
			$pagelist .= ' ...';
		}
	}

	if ($tree)
		$listhtml = '<span class="sfont">(pages: %s)</span>';
	else
		$listhtml = '<div class="pagelist">Pages: %s</div>';

	return sprintf($listhtml, $pagelist);
}

function themelist() {
	$themes = glob('theme/*', GLOB_ONLYDIR);
	sort($themes);
	foreach ($themes as $f) {
		$themename = explode("/",$f)[1];
		if (file_exists("theme/$themename/$themename.css")) {
			if (preg_match("~/* META\n(.*?)\n~s", file_get_contents("theme/$themename/$themename.css"), $matches)) {
				$themelist[$themename] = $matches[1];
			}
		}
	}

	return $themelist;
}

function ranklist() {
	global $rankset_names;
	foreach ($rankset_names as $rankset) {
		$rlist[] = $rankset;
	}
	return $rlist;
}

function announcement_row() {
	global $dateformat, $sql, $loguser;

	$anc = $sql->fetch("SELECT t.title,t.user,t.lastdate date,".userfields('u')." FROM threads t JOIN users u ON t.user = u.id WHERE t.announce = 1 ORDER BY lastdate DESC LIMIT 1");

	$cancreate = $loguser['powerlevel'] > 2;

	if (isset($anc['title']) || $cancreate) {
		$anlink = 'No announcements';
		if (isset($anc['title'])) {
			$anlink = sprintf(
				'<a href="thread.php?announce=1">%s</a> - by %s on %s',
			$anc['title'], userlink($anc), date($dateformat, $anc['date']));
		}
		?><table class="c1">
			<tr class="h"><td class="b" colspan="2">Announcements</td></tr>
			<tr class="n1 center"><td class="center b n1" width="32">&nbsp;</td>
			<td class="b left"><?=$anlink ?>
			<?=($cancreate ? '<span class="right" style="float:right"><a href=newthread.php?announce=1>New Announcement</a></span>' : '') ?>
		</td></tr></table><?php
	}
}

function forumlist($currentforum = -1) {
	global $sql, $loguser;

	$r = $sql->query("SELECT c.title ctitle,f.id,f.title,f.cat FROM forums f LEFT JOIN categories c ON c.id=f.cat WHERE ? >= f.minread ORDER BY c.ord,c.id,f.ord,f.id",
		[$loguser['powerlevel']]);
	$out = '<select id="forumselect">';
	$c = -1;
	while ($d = $r->fetch()) {
		if ($d['cat'] != $c) {
			if ($c != -1)
				$out .= '</optgroup>';
			$c = $d['cat'];
			$out .= '<optgroup label="'.$d['ctitle'].'">';
		}
		$out .= sprintf(
			'<option value="%s"%s>%s</option>',
		$d['id'], ($d['id'] == $currentforum ? ' selected="selected"' : ''), $d['title']);
	}
	$out .= "</optgroup></select>";

	return $out;
}

/**
 * Display $message if $result (the result of a SQL query) is empty (has no lines).
 */
function if_empty_query($result, $message, $colspan = 0, $table = false) {
	if ($result == 1) {
		if ($table) echo '<table class="c1">';
		echo '<tr><td class="b n1 center" '.($colspan != 0 ? "colspan=$colspan" : '')."><p>$message</p></td></tr>";
		if ($table) echo '</table>';
	}
}
