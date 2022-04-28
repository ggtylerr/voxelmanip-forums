<?php
// ** Acmlmboard Configuration **
// Please look through the file and fill in the appropriate information.

$sqlhost = 'localhost';
$sqluser = 'sqlusername';
$sqlpass = 'sqlpassword';
$sqldb   = 'sqldatabase';

$trashid = 2; // Designates the id for your trash forum.

$boardtitle = "Insert title here"; // This is what will be displayed at the top of your browser window.
$defaultlogo = "theme/abII.png"; // Replace with the logo of your choice.
$favicon = "theme/fav.png"; // Replace with your favicon of choice
$boardlogo = '<img src="%s">'; // This defines the logo.
$meta = '<meta name="description" content="Stuff goes here!">'; // This is used for search engine keywords.

$defaulttheme = "0"; // Select the default theme to be used.
$defaulttimezone = "Europe/Stockholm"; // Default timezone if people do not select their own.

// Registration Bot Protection
$puzzle = true;
$puzzleQuestion = "What forum software does this board run?";
$puzzleAnswer = "Acmlmboard";

$override_theme = ''; // If you want to lock everyone to a specific theme.
$lockdown = true; // Put board in lockdown mode.

// List of bots (web crawlers)
$botlist = ['ia_archiver','baidu','yahoo','bot','spider'];

// List of smilies
$smilies = [
	['text' => '-_-', 'url' => 'img/smilies/annoyed.png'],
	['text' => 'o_O', 'url' => 'img/smilies/bigeyes.png'],
	['text' => ':D', 'url' => 'img/smilies/biggrin.png'],
	['text' => 'o_o', 'url' => 'img/smilies/blank.png'],
	['text' => ':x', 'url' => 'img/smilies/crossmouth.png'],
	['text' => ';_;', 'url' => 'img/smilies/cry.png'],
	['text' => '^_^', 'url' => 'img/smilies/cute.png'],
	['text' => '@_@', 'url' => 'img/smilies/dizzy.png'],
	['text' => ':@', 'url' => 'img/smilies/dropsmile.png'],
	['text' => 'O_O', 'url' => 'img/smilies/eek.png'],
	['text' => '>:]', 'url' => 'img/smilies/evil.png'],
	['text' => ':eyeshift:', 'url' => 'img/smilies/eyeshift.png'],
	['text' => ':(', 'url' => 'img/smilies/frown.png'],
	['text' => '8-)', 'url' => 'img/smilies/glasses.png'],
	['text' => ':LOL:', 'url' => 'img/smilies/lol.png'],
	['text' => '>:[', 'url' => 'img/smilies/mad.png'],
	['text' => '<_<', 'url' => 'img/smilies/shiftleft.png'],
	['text' => '>_>', 'url' => 'img/smilies/shiftright.png'],
	['text' => 'x_x', 'url' => 'img/smilies/sick.png'],
	['text' => ':|', 'url' => 'img/smilies/slidemouth.png'],
	['text' => ':)', 'url' => 'img/smilies/smile.png'],
	['text' => ':P', 'url' => 'img/smilies/tongue.png'],
	['text' => ':B', 'url' => 'img/smilies/vamp.png'],
	['text' => ';)', 'url' => 'img/smilies/wink.png'],
	['text' => ':-3', 'url' => 'img/smilies/wobble.png'],
	['text' => ':S', 'url' => 'img/smilies/wobbly.png'],
	['text' => '>_<', 'url' => 'img/smilies/yuck.png'],
	['text' => ':box:', 'url' => 'img/smilies/box.png'],
	['text' => ':yes:', 'url' => 'img/smilies/yes.png'],
	['text' => ':no:', 'url' => 'img/smilies/no.png'],
	['text' => 'OwO', 'url' => 'img/smilies/owo.png']
];

// Ranksets
//require('img/ranks/rankset.php'); // Default (Mario) rankset

// Random forum descriptions.
// It will be replacing the value %%%RANDOM%%% in the forum description.
$randdesc = [
	"Value1",
	"Value2"
];
