<?php
require('lib/common.php');
pageheader('Management');

$mlinks = [];
if ($loguser['powerlevel'] > 2) {
	$mlinks[] = ['url' => "manageforums.php", 'title' => 'Manage forums'];
	$mlinks[] = ['url' => "ipbans.php", 'title' => 'Manage IP bans'];
	$mlinks[] = ['url' => "editattn.php", 'title' => 'Edit news box'];
}

if (!empty($mlinks)) {
	$mlinkstext = '';
	foreach ($mlinks as $l)
		$mlinkstext .= sprintf(' <a href="%s"><input type="submit" name="action" value="%s"></a> ', $l['url'], $l['title']);
} else {
	$mlinkstext = "You don't have permission to access any management tools.";
}

?>
<table class="c1">
	<tr class="h"><td class="b">Board management tools</td></tr>
	<tr><td class="b n1 center">
		<br><?=$mlinkstext ?><br><br>
	</td></tr>
</table>
<?php pagefooter();