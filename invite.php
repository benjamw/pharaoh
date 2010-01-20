<?php

// $Id: invite.php 22 2009-12-05 07:11:35Z cchristensen $

require_once 'includes/inc.global.php';

// this has nothing to do with creating a game
// but I'm running it here to prevent long load
// times on other pages where it would be ran more often
if (0) { // TODO
GamePlayer::delete_inactive(Settings::read('expire_users'));
Game::delete_inactive(Settings::read('expire_games'));
Game::delete_finished(Settings::read('expire_finished_games'));
}

$Game = new Game( );

if (isset($_POST['invite'])) {
	test_token( );

	try {
		$game_id = $Game->invite( );
		Flash::store('Invitation Sent Successfully', 'setup.php?id='.$game_id.$GLOBALS['_&_DEBUG_QUERY']);
	}
	catch (MyException $e) {
		Flash::store('Invitation FAILED !', false);
	}
}

$players = GamePlayer::get_list(true);
$opponent_selection = '';
foreach ($players as $player) {
	if ($_SESSION['player_id'] == $player['player_id']) {
		continue;
	}

	$opponent_selection .= '<option value="'.$player['player_id'].'">'.$player['username'].'</option>';
}

$setups = Setup::get_list( );
$setup_selection = '';
foreach ($setups as $setup) {
	$setup_selection .= '<option value="'.$setup['setup_id'].'">'.$setup['name'].'</option>';
}


$meta['title'] = 'Send Game Invitation';
$meta['head_data'] = '
	<script type="text/javascript" src="scripts/invite.js"></script>
';

$hints = array(
	'Invite a player to a game by filling out your desired game options.' ,
	'<span class="highlight">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
);

$contents = '';

$contents .= <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		<ul>
			<li><label for="opponent">Opponent</label><select id="opponent" name="opponent">{$opponent_selection}</select></li>
			<li><label for="setup">Setup</label><select id="setup" name="setup">{$setup_selection}</select></li>
			<li><input type="submit" name="invite" value="Send Invitation" /></li>
		</ul>

	</div></form>

	<div id="setup_display"></div>

EOF;

// create our invitation tables
list($in_vites, $out_vites, $open_vites) = Game::get_invites($_SESSION['player_id']);

$contents .= <<< EOT
	<hr />
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv" id="invites">
EOT;

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no received invites to show</p>' ,
	'caption' => 'Invitations Recieved' ,
);
$table_format = array(
	array('ID', 'game_id') ,
	array('Invitor', 'invitor_id') ,
	array('Setup', 'setup') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[invite_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" /><input type="button" id="decline-[[[game_id]]]" value="Decline" />', false) ,
);
$contents .= get_table($table_format, $in_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no sent invites to show</p>' ,
	'caption' => 'Invitations Sent' ,
);
$table_format = array(
	array('ID', 'game_id') ,
	array('Invitee', 'invitee_id') ,
	array('Setup', 'setup') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[invite_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="withdraw-[[[game_id]]]" value="Withdraw" />', false) ,
);
$contents .= get_table($table_format, $out_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no open invites to show</p>' ,
	'caption' => 'Open Games' ,
);
$table_format = array(
	array('ID', 'game_id') ,
	array('Invitor', 'invitor_id') ,
	array('Setup', 'setup') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[invite_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" /><input type="button" id="decline-[[[game_id]]]" value="Decline" />', false) ,
);
$contents .= get_table($table_format, $open_vites, $table_meta);

$contents .= <<< EOT
	</div></form>
EOT;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
echo get_footer( );

