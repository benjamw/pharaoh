<?php

require_once 'includes/inc.global.php';

if (isset($_POST['submit'])) {
	test_token( );

	try {
		$GLOBALS['Player']->update( );

		Flash::store('Profile Updated Successfully !', false);
	}
	catch (MyException $e) {
		Flash::store('Profile Update FAILED !', false);
	}
}
$meta['title'] = 'Update Profile';
$meta['head_data'] = '
	<script type="text/javascript">//<![CDATA[
		var profile = 1;
	//]]></script>
	<script type="text/javascript" src="scripts/register.js"></script>
';

$hints = array(
	'<span class="notice">GLOBAL SETTINGS</span><br />These setting affect ALL iohelix games that also display the GLOBAL SETTINGS text.' ,
	'Here you can update your name, email address, and password.' ,
);

$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="errors" id="errors" />

		<div><label>Username</label><span class="input">{$GLOBALS['Player']->username}</span></div>

		<div><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" maxlength="20" value="{$GLOBALS['Player']->firstname}" tabindex="1" /></div>
		<div><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" maxlength="20" value="{$GLOBALS['Player']->lastname}" tabindex="2" /></div>

		<div><label for="email" class="req">Email</label><input type="text" id="email" name="email" maxlength="100" value="{$GLOBALS['Player']->email}" tabindex="3" /><span id="email_check" class="test"></span></div>

		<div class="info">Leave password fields blank to keep current password.</div>
		<div><label for="curpass">Current Password</label><input type="password" id="curpass" name="curpass" tabindex="4" /></div>
		<div><label for="password">New Password</label><input type="password" id="password" name="password" tabindex="5" /></div>
		<div><label for="passworda">Confirmation</label><input type="password" id="passworda" name="passworda" tabindex="6" /></div>

		<div><input type="submit" id="submit" name="submit" value="Update Profile" /></div>
	</div></form>

EOF;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

