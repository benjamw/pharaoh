<?php

// $Id: register.php 193 2009-09-20 11:58:51Z cchristensen $

$LOGIN = false;
require_once 'includes/inc.global.php';

if ((false == Settings::read('new_users')) && ( ! $GLOBALS['Player']->is_admin)) {
	Flash::store('Sorry, be we are not accepting new applications at this time.');
}

if (isset($_SESSION['player_id'])) {
	$GLOBALS['Player'] = array( );
	$_SESSION['player_id'] = false;
	unset($_SESSION['player_id']);
	unset($GLOBALS['Player']);
}

if (isset($_POST['register'])) {
	test_token( );

	// die spammers
	if ('' != $_POST['website']) {
		header('Location: http://www.searchbliss.com/spambot/spambot-stopper.asp');
		exit;
	}

	try {
		$GLOBALS['Player'] = new GamePlayer( );
		$GLOBALS['Player']->register( );
		$Message = new Message($GLOBALS['Player']->id, $GLOBALS['Player']->is_admin);
		$Message->grab_global_messages( );

		Flash::store('Registration Successfull !', 'login.php');
	}
	catch (MyException $e) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			Flash::store('Registration Failed !\n\n'.$e->outputMessage( ), true);
		}
		else {
			call('REGISTRATION ATTEMPT REDIRECTED TO REGISTER AND QUIT');
			call($e->getMessage( ));
		}
	}

	exit;
}

$meta['title'] = 'Registration';
$meta['head_data'] = '
	<script type="text/javascript">//<![CDATA[
		var profile = false;
	//]]></script>
	<script type="text/javascript" src="scripts/register.js"></script>
';
$meta['show_menu'] = false;
echo get_header($meta);

$hints = array(
	'Please Register' ,
	'You must remember your username and password to be able to gain access to '.$GLOBALS['__GAME_NAME'].' later.' ,
	'<span class="notice">NOTE</span>: You will not be able to log in until your account has been approved.' ,
	'You should receive an email when your account has been approved.' ,
	'<span class="warning">WARNING!</span><br />Inactive accounts will be deleted after '.Settings::read('expire_users').' days.' ,
);

$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" id="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="errors" id="errors" />
		<ul>
			<li><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" maxlength="20" tabindex="1" /></li>
			<li><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" maxlength="20" tabindex="2" /></li>

			<li><label for="username" class="req">Username</label><input type="text" id="username" name="username" maxlength="20" tabindex="3" /><span id="username_check" class="test"></span></li>
			<li><label for="email" class="req">Email</label><input type="text" id="email" name="email" tabindex="4" /><span id="email_check" class="test"></span></li>

			<li style="text-indent:-9999em;">Leave the next field blank (anti-spam).</li>
			<li style="text-indent:-9999em;"><label for="website">Leave Blank</label><input type="text" id="website" name="website" /></li>

			<li><label for="password" class="req">Password</label><input type="password" id="password" name="password" tabindex="5" /></li>
			<li><label for="passworda" class="req">Confirmation</label><input type="password" id="passworda" name="passworda" tabindex="6" /></li>

			<li><input type="submit" name="register" value="Submit" tabindex="7" /></li>
		</ul>

	</div></form>

	<a href="login.php{$GLOBALS['_?_DEBUG_QUERY']}">Return to login</a>

EOF;

echo get_item($contents, $hints, $meta['title']);

// don't use the usual footer

?>

		<div id="footerspacer">&nbsp;</div>

		<div id="footer">&nbsp;</div>

	</div>
</body>
</html>
