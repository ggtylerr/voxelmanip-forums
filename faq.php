<?php
require('lib/common.php');

//Smilies List
$smilietext = '';
$x = 0;
foreach ($smilies as $smily) {
	if ($x == 0) $smilietext .= "<tr>";
	$smilietext .= sprintf('<td class="b n1"><img src="%s"> %s</td>', $smily['url'], esc($smily['text']));
	$x++;
	$x %= ceil(sqrt(sizeof($smilies)));
	if ($x == 0) $smilietext .= "</tr>";
}

// Rank colours
$nctable = '';
foreach ($powerlevels as $id => $title) {
	$nctable .= sprintf('<td class="b n1" width="140"><b><span style="color:#%s">%s</span></b></td>', powIdToColour($id), $title);
}

$faq = [[
	'id' => 'disclaimer',
	'title' => 'General Disclaimer',
	'content' => <<<HTML
<p>The site does not own and cannot be held responsible for statements made by members on the forum. This site is offered as-is to the user. Any statements made on the board may be altered or removed at the discretion of the staff. Furthermore, all users are expected to have read, understood, and agreed to The Rules before posting.
</p>

<p><strong>We do <em>not</em> sell, distribute or otherwise disclose member information like IP addresses or e-mail addresses to any third party.</strong> If you have questions about any information contained in this FAQ, please send a private message with your question to an administrator <em>before</em> posting.</p>

<p>We only use a token cookie to keep you logged in, no cookies is placed on your device when not logged in. We do not use tracking cookies, you can verify this by looking at the cookie storage for this site in your respective web browser.</p>
HTML
], [
	'id' => 'gpg',
	'title' => 'General Posting Guidelines',
	'content' => <<<HTML
<p>Posting on a message forum is generally relaxed. There are, however, a few things to keep in mind when posting.</p>
<ul>
	<li><b>No trolling, flaming, harrassment or drama</b><br>
		This behavior is unacceptable and will be dealt with accordingly to the severity, to make the board a pleasant experience for everyone.</li>

	<li><b>No spamming</b><br>
		Spam is a pretty broad area. Spam can be generalised as multiple posts with no real meaning to the topic or what anyone else is talking about. Also applies to registering with the sole intent of advertising something completely irrelevant.</li>

	<li><b>Do not mention sensitive subjects such as politics or religion.</b><br>
		It is irrelevant to this site and risks creating unnecessary conflict and tension.</li>

	<li><b>The forum's main language is English</b><br>
		English is a language we all understand relatively well, including the staff. Keep non-English text to an absolute minimum.</li>

	<li><b>Do not back-seat moderate or "minimod"</b><br>
		While this may depend on the circumstances, you may do more harm than good and stir up drama. If you see an issue please contact a staff member privately and they can properly handle it.</li>

	<li><b>No explicit material</b><br>
		If it is something people normally would look at to pleasure themselves, you should not post it here.</li>

	<li><b>Please proofread your posts and use proper grammar and punctuation.</b><br>
		To a certain extent of course, you are not required to write like you are writing a formal academic paper and have full perfect grammars or speeling, but please read through whatever you are writing so that it looks sane and reasonably readable.</li>

	<li><b>In general, use common sense...</b><br>
		Really goes a long way.</li>
</ul>

<p>Staff have the final say in interpretation of the rules, and may act in any way they see fit to keep the forum a pleasant experience for everyone.</p>
HTML
], [
	'id' => 'move',
	'title' => 'I just made a thread, where did it go?',
	'content' => <<<HTML
<p>It was probably moved or deleted by a staff member. If it was deleted, please make sure your thread meets the criteria we have established. If it was moved, look into the other forums and consider why it was moved there. If you have any questions, PM a staff member.</p>
HTML
], [
	'id' => 'rude',
	'title' => 'An user is being rude to me. What do I do?',
	'content' => <<<HTML
<p>Stay cool. Don't further disrupt the thread by responding <b>at all</b> to the rudeness. Let a member of staff know with a link to the offending post(s). Please note that responding to the rudeness is promoting flaming, which is a punishable offense.</p>
HTML
], [
	'id' => 'banned',
	'title' => "I've been banned. What do I do now?",
	'content' => <<<HTML
<p>Check your title as it will usually show the reason as to why you were banned and an expiration date. If there is no expiration date you will need to prove to the staff why you should be unbanned, or if you would want more information please PM a staff member calmly.</p>
HTML
], [
	'id' => 'smile',
	'title' => 'Are smilies and BBCode supported?',
	'content' => <<<HTML
<p>Here's a table with all available smileys.</p>
<table class="smileytbl">$smilietext</table>

<p>Likewise, some BBCode is supported. See the table below.</p>
<table class="c1" style="width:auto">
	<tr class="h">
		<td class="b h">Tag</td>
		<td class="b h">Effect</td>
	</tr><tr>
		<td class="b n1">[b]<i>text</i>[/b]</td>
		<td class="b n2"><b>Bold Text</b></td>
	</tr><tr>
		<td class="b n1">[i]<i>text</i>[/i]</td>
		<td class="b n2"><i>Italic Text</i></td>
	</tr><tr>
		<td class="b n1">[u]<i>text</i>[/u]</td>
		<td class="b n2"><u>Underlined Text</u></td>
	</tr><tr>
		<td class="b n1">[s]<i>text</i>[/s]</td>
		<td class="b n2"><s>Striked-out Text</s></td>
	</tr><tr>
		<td class="b n1">[color=<b>hexcolor</b>]<i>text</i>[/color]</td>
		<td class="b n2"><span style="color:#BCDE9A">Custom color Text</span></td>
	</tr><tr>
		<td class="b n1">[img]<i>URL of image to display</i>[/img]</td>
		<td class="b n2">Displays an image.</td>
	</tr><tr>
		<td class="b n1">[spoiler]<i>text</i>[/spoiler]</td>
		<td class="b n2">Used for hiding spoiler text.</td>
	</tr><tr>
		<td class="b n1">[code]<i>code text</i>[/code]</td>
		<td class="b n2">Displays code in a formatted box.</td>
	</tr><tr>
		<td class="b n1">[url]<i>URL of site or page to link to</i>[/url]<br>[url=<i>URL</i>]<i>Link title</i>[/url]</td>
		<td class="b n2">Creates a link with or without a title.</td>
	</tr><tr>
		<td class="b n1">@"<i>User Name</i>"</td>
		<td class="b n2">Creates a link to a user's profile complete with name colour.</td>
	</tr><tr>
		<td class="b n1">[youtube]<i>video id</i>[/youtube]</td>
		<td class="b n2">Creates an embeded YouTube video.</td>
	</tr>
</table>
<p>Also, most HTML tags are able to be used in your posts.</p>
HTML
], [
	'id' => 'reg',
	'title' => 'Can I register more than one account?',
	'content' => <<<HTML
<p>No. Most uses for a secondary account tend to be to bypass bans, sockpuppet or in other ways cause havoc. All is expressly forbidden and you will be punished when found out (you most likely will).</p>

<p>Another use is to have a different name, and we have a displayname system to allow this cleanly. Feel free to ask an admin to set a custom displayname for you if that is the case.</p>
HTML
], [
	'id' => 'css',
	'title' => 'What are we not allowed to do in our custom CSS layouts?',
	'content' => <<<HTML
<p>While we allow very open and customizable layouts and side bars, we have a few rules that will be strictly enforced. Please read them over and follow them. Loss of post layout privileges will be enacted for those who are repeat offenders. If in doubt ask a staff member. Staff has discretion in deciding violations.</p>

<p>The following are not allowed:</p>
<ul>
	<li>Modification of anyone else's post layout <b>for any reason</b>.</li>
	<li>Modification of any tables, images, themes, etc outside of your personal layout.</li>
	<li>Altering your Nick color in any way. Nick color is an indicator of staff, and it will be considered impersonation of staff.</li>
</ul>

<p>Obnoxious, bandwidth- or resource-intensive or plain bad layouts are not allowed either, which is up to interpretation by staff when reviewing post layouts.</p>
HTML
], [
	'id' => 'usercols',
	'title' => 'What do the username colours mean?',
	'content' => <<<HTML
<p>They reflect the rank of the user, which are:</p>
<table class="center"><tr>$nctable</tr></table>
<p>Keep in mind that some users might have a custom colour assigned to them, usually if they are staff or in bed with one.</p>
HTML
]];

pageheader("FAQ");

?>
<table class="c1 faq">
	<tr class="h"><td class="b h">FAQ</td></tr>
	<tr><td class="b n1"><ol class="toc">
<?php foreach ($faq as $faqitem) printf('<li><a href="#%s">%s</a></li>', $faqitem['id'], $faqitem['title']); ?>
	</ol></td></tr>

<?php
foreach ($faq as $faqitem) {
	printf('<tr class="h"><td class="b h" id="%s">%s</td></tr><tr><td class="b n1">%s</td></tr>',
		$faqitem['id'], $faqitem['title'], $faqitem['content']);
}
?>
</table>
<?php pagefooter();
