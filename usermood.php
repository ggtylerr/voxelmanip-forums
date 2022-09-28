<?php

require 'lib/common.php';

pageheader('Mood avatars');

// Prevent infamous board2 mood avatar hax
if ($loguser['powerlevel'] < 1)
Error("You do not have permission to edit your mood avatars.");

// Queries! Woo-de-fucking-hoo. How interesting (not).
$qMoods = $sql->query("SELECT * FROM usermoods WHERE uid = $loguser[id]");

//Handling the forms

if (isset($_POST['submit']))
	{
	if ($_POST['action'] == "Upload")
	{
	$validatedFile = $_POST['newupload'];
	file_put_contents($validatedFile, 'userpic/moods/test');
	}

	if ($_POST['action'] == "Delete")
	{
	}
	else Error("Brown action thinks wedge all the time. You shouldn't be seeing this.");
}

?>

<table class="c1">
<tbody>
<tr>
<th colspan=3 class="b n1">Manage your mood avatars</th>
</tr>
<tr class="h">
<td class="b h">Avatar</td>
<td class="b h">Label</td>
<td class="b h">Manage</td>
</tr>
<?php
for ($i = 1; $mood = $qMoods->fetch(); $i++)
{
echo "
<tr>
<td class=\"n1 b\" align=\"center\"><img title=\"$mood[label]\" src=\"userpic/moods/$mood[id]\"></td>
<td class=\"n2 b\" align=\"center\"><input type=\"text\" value=\"$mood[label]\"></input></td>
<td class=\"n1 b\" align=\"center\"><button>Delete</button></td>
</tr>
";
}

if_empty_query($i, "No mood avatars found."); //make this pretty, it sucks rn

?>
<tr>
<th colspan=3 class="b n1">Upload a new mood avatar</th>
</tr>
<tr>
<td class="n1 b" align="center"><input type="file" name="newupload"></input></td>
<td class="n2 b" align="center"><input type="text" value="(Label)" name="newlabel"></input></td>
<td class="n1 b" align="center"><button>Upload</button></td>
</tr>
</tbody>
</table>
<?php

pagefooter();

?>
