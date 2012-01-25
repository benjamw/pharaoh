<?php

require_once 'includes/inc.global.php';

// this has nothing to do with creating a game
// but I'm running it here to prevent long load
// times on other pages where it would be run more often
GamePlayer::delete_inactive(Settings::read('expire_users'));
Game::delete_inactive(Settings::read('expire_games'));
Game::delete_finished(Settings::read('expire_finished_games'));

if (isset($_POST['invite'])) {
	// make sure this user is not full
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !', false);
	}

	test_token( );

	try {
		Game::invite( );
		Flash::store('Invitation Sent Successfully', true);
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

$opponent_selection = '';
$opponent_selection .= '<option value="">-- Open --</option>';
foreach ($players_full as $player) {
	if ($_SESSION['player_id'] == $player['player_id']) {
		continue;
	}

	if (in_array($player['player_id'], $players)) {
		$opponent_selection .= '
			<option value="'.$player['player_id'].'">'.$player['username'].'</option>';
	}
}

$groups = array(
	'Normal' => array(0, 0),
	'Eye of Horus' => array(0, 1),
	'Sphynx' => array(1, 0),
	'Sphynx & Horus' => array(1, 1),
);
$group_names = array_keys($groups);
$group_markers = array_values($groups);

$setups = Setup::get_list( );
$setup_selection = '<option value="0">Random</option>';
$setup_javascript = '';
$cur_group = false;
$group_open = false;
foreach ($setups as $setup) {
	$marker = array((int) $setup['has_sphynx'], (int) $setup['has_horus']);
	$group_index = array_search($marker, $group_markers, true);

	if ($cur_group !== $group_names[$group_index]) {
		if ($group_open) {
			$setup_selection .= '</optgroup>';
			$group_open = false;
		}

		$cur_group = $group_names[$group_index];
		$setup_selection .= '<optgroup label="'.$cur_group.'">';
		$group_open = true;
	}

	$setup_selection .= '
		<option value="'.$setup['setup_id'].'">'.$setup['name'].'</option>';
	$setup_javascript .= "'".$setup['setup_id']."' : '".expandFEN($setup['board'])."',\n";
}
$setup_javascript = substr(trim($setup_javascript), 0, -1);

if ($group_open) {
	$setup_selection .= '</optgroup>';
}

$meta['title'] = 'Send Game Invitation';
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

	<script type="text/javascript">//<![CDATA[
		var setups = {
			'.$setup_javascript.'
		};
	/*]]>*/</script>

	<script type="text/javascript" src="scripts/board.js"></script>
	<script type="text/javascript" src="scripts/invite.js"></script>
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

$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="send"><div class="formdiv">

		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		{$warning}

		<div><label for="opponent">Opponent</label><select id="opponent" name="opponent">{$opponent_selection}</select></div>
		<div><label for="setup">Setup</label><select id="setup" name="setup">{$setup_selection}</select> <a href="#setup_display" id="show_setup" class="options">Show Setup</a></div>
		<div><label for="color">Your Color</label><select id="color" name="color"><option value="random">Random</option><option value="white">Silver</option><option value="black">Red</option></select></div>

		<div class="random_convert options">
			<fieldset>
				<legend>Conversion</legend>

				<p>
					If your random game is a v1.0 game, you can convert it to play v2.0 Pharaoh.<br />
					Or, if your random game is a v2.0 game, you can convert it to play v1.0 Pharaoh.<br />
				</p>
				<p>
					When you select to convert, more options may be shown below.
				</p>

				<div><label class="inline"><input type="checkbox" id="rand_convert_to_1" name="rand_convert_to_1" /> Convert to 1.0</label></div>
				<div><label class="inline"><input type="checkbox" id="rand_convert_to_2" name="rand_convert_to_2" /> Convert to 2.0</label></div>

			</fieldset>
		</div> <!-- .random_convert -->

		<div class="pharaoh_1 options">
			<fieldset>
				<legend>v1.0 Options</legend>
				<p class="conversion">
					Here you can convert v1.0 setups to play v2.0 Pharaoh.<br />
					The conversion places a Sphynx in your lower-right corner facing upwards (and opposite for your opponent)
					and converts any double-stacked Obelisks to Anubises which face forward.
				</p>
				<p class="conversion">
					When you select to convert, more options will be shown below, as well as the "Show Setup" link will show the updated setup.
				</p>

				<div class="conversion"><label class="inline"><input type="checkbox" id="convert_to_2" name="convert_to_2" /> Convert to 2.0</label></div>

			</fieldset>
		</div> <!-- .pharaoh_1 -->

		<div class="pharaoh_2 p2_box options">
			<fieldset>
				<legend>v2.0 Options</legend>
				<p class="conversion">
					Here you can convert the v2.0 setups to play v1.0 Pharaoh.<br />
					The conversion removes any Sphynxes from the board and converts any Anubises to double-stacked Obelisks.
				</p>
				<p class="conversion">
					When you select to convert, the "Show Setup" link will show the updated setup.
				</p>

				<div class="conversion"><label class="inline"><input type="checkbox" id="convert_to_1" name="convert_to_1" /> Convert to 1.0</label></div>

				<div class="pharaoh_2"><label class="inline"><input type="checkbox" id="move_sphynx" name="move_sphynx" /> Sphynx is movable</label></div>

			</fieldset>
		</div> <!-- .pharaoh_2 -->

		<fieldset>
			<legend><label class="inline"><input type="checkbox" name="laser_battle_box" id="laser_battle_box" class="fieldset_box" /> Laser Battle</label></legend>
			<div id="laser_battle" class="options">
				<p>
					When a laser gets shot by the opponents laser, it will be disabled for a set number of turns, making that laser unable to shoot until those turns have passed.<br />
					After those turns have passed, and the laser has recovered, it will be immune from further shots for a set number of turns.<br />
					After the immunity turns have passed, whether or not the laser was shot again, it will now be susceptible to being shot again.
					<span class="pharaoh_2"><br />You can also select if the Sphynx is hittable only in the front, or on all four sides.</span>
				</p>

				<div><label for="battle_dead">Dead for:</label><input type="text" id="battle_dead" name="battle_dead" size="4" /> <span class="info">(Default: 1; Minimum: 1)</span></div>
				<div><label for="battle_immune">Immune for:</label><input type="text" id="battle_immune" name="battle_immune" size="4" /> <span class="info">(Default: 1; Minimum: 0)</span></div>
				<div class="pharaoh_2"><label class="inline"><input type="checkbox" id="battle_front_only" name="battle_front_only" checked="checked" /> Only front hits on Sphynx count</label></div>
				<!-- <div class="pharaoh_2"><label class="inline"><input type="checkbox" id="battle_hit_self" name="battle_hit_self" /> Hit Self</label></div> -->

				<p>You can set the "Immune for" value to 0 to allow a laser to be shot continuously, but the minimum value for the "Dead for" value is 1, as it makes no sense otherwise.</p>
			</div> <!-- #laser_battle -->
		</fieldset>

		{$submit_button}

		<div class="clr"></div>
	</div></form>

	<div id="setup_display"></div>

EOF;

// create our invitation tables
list($in_vites, $out_vites, $open_vites) = Game::get_invites($_SESSION['player_id']);

$contents .= <<< EOT
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv" id="invites">
EOT;

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no received invites to show</p>' ,
	'caption' => 'Invitations Received' ,
);
$table_format = array(
	array('Invitor', 'invitor') ,
	array('Setup', '<a href="#[[[board]]]" class="setup" id="s_[[[setup_id]]]">[[[setup]]]</a>') ,
	array('Color', 'color') ,
	array('Extra', '<abbr title="[[[hover_text]]]">Hover</abbr>') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" /><input type="button" id="decline-[[[game_id]]]" value="Decline" />', false) ,
);
$contents .= get_table($table_format, $in_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no sent invites to show</p>' ,
	'caption' => 'Invitations Sent' ,
);
$table_format = array(
	array('Invitee', '###ifenr(\'[[[invitee]]]\', \'-- OPEN --\')') ,
	array('Setup', '<a href="#[[[board]]]" class="setup" id="s_[[[setup_id]]]">[[[setup]]]</a>') ,
	array('Color', 'color') ,
	array('Extra', '<abbr title="[[[hover_text]]]">Hover</abbr>') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '###\'<input type="button" id="withdraw-[[[game_id]]]" value="Withdraw" />\'.((strtotime(\'[[[create_date]]]\') >= strtotime(\'[[[resend_limit]]]\')) ? \'\' : \'<input type="button" id="resend-[[[game_id]]]" value="Resend" />\')', false) ,
);
$contents .= get_table($table_format, $out_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no open invites to show</p>' ,
	'caption' => 'Open Invitations' ,
);
$table_format = array(
	array('Invitor', 'invitor') ,
	array('Setup', '<a href="#[[[board]]]" class="setup" id="s_[[[setup_id]]]">[[[setup]]]</a>') ,
	array('Color', 'color') ,
	array('Extra', '<abbr title="[[[hover_text]]]">Hover</abbr>') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" />', false) ,
);
$contents .= get_table($table_format, $open_vites, $table_meta);

$contents .= <<< EOT
	</div></form>
EOT;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

