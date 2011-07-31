<?php

require_once 'includes/inc.global.php';

$setups = Game::get_setup_stats_list( );
$setup_selection = '<option value="0">Random</option>';
$setup_javascript = '';
foreach ($setups as $setup) {
	$setup_selection .= '
		<option value="'.$setup['setup_id'].'">'.$setup['name'].'</option>';
	$setup_javascript .= "'".$setup['setup_id']."' : '".expandFEN($setup['board'])."',\n";
}
$setup_javascript = substr(trim($setup_javascript), 0, -1); // remove trailing comma

$meta['title'] = 'Statistics';
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

	<script type="text/javascript">//<![CDATA[
		var setups = {
			'.$setup_javascript.'
		};
	/*]]>*/</script>

	<script type="text/javascript" src="scripts/board.js">></script>
	<script type="text/javascript" src="scripts/stats.js">></script>
';

$hints = array(
	'View '.GAME_NAME.' Player and Setup statistics.' ,
	'Click on Setup table row to view setup.' ,
);

$contents = '';

// grab the wins and losses for the players
$list = Game::get_player_stats_list( );

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no player stats to show</p>' ,
	'caption' => 'Player Stats' ,
	'init_sort_column' => array(1 => 1) ,
);
$table_format = array(
	array('Player', 'username') ,
	array('<abbr title="Silver | Red | Total">Wins</abbr>', '[[[white_wins]]] | [[[black_wins]]] | [[[wins]]]') ,
	array('<abbr title="Silver | Red | Total">Draws</abbr>', '[[[white_draws]]] | [[[black_draws]]] | [[[draws]]]') ,
	array('<abbr title="Silver | Red | Total">Losses</abbr>', '[[[white_losses]]] | [[[black_losses]]] | [[[losses]]]') ,
	array('Win-Loss', '###([[[wins]]] - [[[losses]]])', null, ' class="color"') ,
	array('Win %', '###((0 != ([[[wins]]] + [[[losses]]])) ? perc([[[wins]]] / ([[[wins]]] + [[[losses]]]), 1) : 0)') ,
	array('Last Online', '###date(Settings::read(\'long_date\'), strtotime(\'[[[last_online]]]\'))', null, ' class="date"') ,
);
$contents .= get_table($table_format, $list, $table_meta);


// setups already pulled above

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no setup stats to show</p>' ,
	'caption' => 'Setup Stats' ,
);
$table_format = array(
	array('SPECIAL_CLASS', 'true', 'setup') ,
	array('SPECIAL_HTML', 'true', ' id="s_[[[setup_id]]]"') ,

	array('Setup', 'name') ,
	array('Used', 'used') ,
	array('Silver Wins', 'white_wins') ,
	array('Red Wins', 'black_wins') ,
	array('Draws', 'draws') ,
	array('Reflection', 'reflection') ,
	array('Created', '###date(Settings::read(\'long_date\'), strtotime(\'[[[created]]]\'))', null, ' class="date"') ,
	array('Creator', '###((0 == [[[created_by]]]) ? \'Admin\' : $GLOBALS[\'_PLAYERS\'][[[[created_by]]]])') ,
);
$contents .= get_table($table_format, $setups, $table_meta);


$contents .= <<< EOF

	<div id="setup_display"></div>

EOF;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

