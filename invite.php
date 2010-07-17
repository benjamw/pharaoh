<?php

// $Id: invite.php 22 2009-12-05 07:11:35Z cchristensen $

require_once 'includes/inc.global.php';

// this has nothing to do with creating a game
// but I'm running it here to prevent long load
// times on other pages where it would be ran more often
GamePlayer::delete_inactive(Settings::read('expire_users'));
Game::delete_finished(Settings::read('expire_finished_games'));
Game::delete_inactive(Settings::read('expire_games'));

$Game = new Game( );

if (isset($_POST['invite'])) {
	// make sure this user is not full
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !');
	}

	test_token( );

	try {
		$game_id = $Game->invite( );
		Flash::store('Invitation Sent Successfully');
	}
	catch (MyException $e) {
		Flash::store('Invitation FAILED !', false);
	}
}

// grab the full list of players
$players_full = GamePlayer::get_list(true);
$invite_players = array_shrink($players_full, 'player_id');

// grab the players who's max game count has been reached
$players_maxed = GamePlayer::get_maxed( );
$players_maxed[] = $_SESSION['player_id'];

// remove the maxed players from the invite list
$players = array_diff($invite_players, $players_maxed);

$opponent_selection = '<option value="">-- Open --</option>';
foreach ($players_full as $player) {
	if (in_array($player['player_id'], $players)) {
		$opponent_selection .= '
			<option value="'.$player['player_id'].'">'.$player['username'].'</option>';
	}
}

$setups = Setup::get_list( );
$setup_selection = '<option value="">-- Choose Setup --</option>';
$setup_javascript = '';
foreach ($setups as $setup) {
	$setup_selection .= '
		<option value="'.$setup['setup_id'].'">'.$setup['name'].'</option>';
	$setup_javascript .= "'".$setup['setup_id']."' : '".Game::expandFEN($setup['board'])."',\n";
}
$setup_javascript = substr(trim($setup_javascript), 0, -1);

$meta['title'] = 'Send Game Invitation';
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

	<script type="text/javascript">//<![CDATA[
		var setups = {
			'.$setup_javascript.'
		};
	/*]]>*/</script>

	<script type="text/javascript" src="scripts/invite.js"></script>
	<script type="text/javascript" src="scripts/board.js"></script>
';

$hints = array(
	'Invite a player to a game by filling out your desired game options.' ,
	'<span class="highlight">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
);

// make sure this user is not full
$submit_button = '<div><input type="submit" name="invite" value="Send Invitation" /></div>';
$warning = '';
if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
	$submit_button = $warning = '<p class="warning">You have reached your maximum allowed games, you can not create this game !</p>';
}

$contents = '';

$contents .= <<< EOF

	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="send"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		<div>
			{$warning}
			<div><label for="opponent">Opponent</label><select id="opponent" name="opponent">{$opponent_selection}</select></div>
			<div><label for="setup">Setup</label><select id="setup" name="setup">{$setup_selection}</select> <a href="#setup_display" id="show_setup">Show Setup</a></div>
			{$submit_button}
		</div>

	</div></form>

	<div id="setup_display"></div>

EOF;

// create our invitation tables
list($in_vites, $out_vites, $open_vites) = Game::get_invites($_SESSION['player_id']);

$contents .= <<< EOT
	<hr class="clear" />
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv" id="invites">
EOT;

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no received invites to show</p>' ,
	'caption' => 'Invitations Recieved' ,
);
$table_format = array(
	array('Invitor', 'invitor') ,
	array('Setup', 'setup') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[invite_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[invite_id]]]" value="Accept" /><input type="button" id="decline-[[[invite_id]]]" value="Decline" />', false) ,
);
$contents .= get_table($table_format, $in_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no sent invites to show</p>' ,
	'caption' => 'Invitations Sent' ,
);
$table_format = array(
	array('Invitee', '###ife(\'[[[invitee]]]\', \'-- OPEN --\')') ,
	array('Setup', 'setup') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[invite_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="withdraw-[[[invite_id]]]" value="Withdraw" />', false) ,
);
$contents .= get_table($table_format, $out_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no open invites to show</p>' ,
	'caption' => 'Open Games' ,
);
$table_format = array(
	array('Invitor', 'invitor') ,
	array('Setup', 'setup') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[invite_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[invite_id]]]" value="Accept" />', false) ,
);
$contents .= get_table($table_format, $open_vites, $table_meta);

$contents .= <<< EOT
	</div></form>
EOT;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
echo get_footer( );

