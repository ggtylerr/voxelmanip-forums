<?php

require 'lib/common.php';

pageheader('Mood avatars');

/* ROllerBoard 2.2 - Preliminary mood avatar system by NeppySH2

TODO
- Fix the queries because they're probably dogshit and don't work properly.
- Check the uploaded file properly for any issues like bad file size (against $maxavatarsize in config.php for instance)
- $mid is undefined entirely.
- The forms aren't implemented and the HTML is slightly unfinished.
*/

if ($loguser['powerlevel'] < 1)
Error("You do not have permission to edit mood avatars.");

if (!$_GET['uid'])
	$uid = $loguser['id'];

if ($uid != $loguser['id'] && $loguser['powerlevel'] < 3)
Error("You do not have permission to edit other people's mood avatars.");

// Queries! Woo-de-fucking-hoo. How interesting (not).
$qMoods = $sql->query("SELECT * FROM usermoods WHERE uid = $uid ORDER BY mid DESC");

if (isset($_POST['submit']))
	{

	if ($_POST['action'] == "Upload")
	{
		$label = htmlspecialchars($_POST['newlabel']);
		$filteredAvatar = $_POST['newupload']; // Elaborate on this later. I'm lazy.
		file_put_contents('userpic/moods/$uid_$mid', $filteredAvatar);
		$finalQuery = $sql->query("INSERT INTO usermoods (mid,uid,label) VALUES ($mid,$uid,$label");
	}

	if ($_POST['action'] == "Delete")
	{
		unlink("userpic/moods/$uid_$mid");
		$preDeleteQuery = $sql->query("UPDATE posts SET avatar=0 WHERE mid=$mid AND uid=$uid");
		$finalQuery = $sql->query("DELETE FROM usermoods WHERE mid=$mid AND uid=$uid");

	}

	if ($_POST['action'] == "Edit")
	{
		$label = htmlspecialchars($_POST['label']);
		$finalQuery = $sql->query("UPDATE usermoods SET label=$label WHERE mid=$mid");
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
<td class=\"n2 b\" align=\"center\"><input type=\"text\" name=\"label\" value=\"$mood[label]\"></input></td>
<td class=\"n1 b\" align=\"center\"><button>Delete</button></td>
</tr>
";
}

if_empty_query($i, "No mood avatars found.");

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
