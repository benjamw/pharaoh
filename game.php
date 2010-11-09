<?php

require_once 'includes/inc.global.php';

// grab the game id
if (isset($_GET['id'])) {
	$_SESSION['game_id'] = (int) $_GET['id'];
}
elseif ( ! isset($_SESSION['game_id'])) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('No Game Id Given !');
	}
	else {
		call('NO GAME ID GIVEN');
	}

	exit;
}

// ALL GAME FORM SUBMISSIONS ARE AJAXED THROUGH /scripts/game.js

// load the game
// always refresh the game data, there may be more than one person online
try {
	$Game = new Game((int) $_SESSION['game_id']);
	$players = $Game->get_players( );
}
catch (MyException $e) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('Error Accessing Game !');
	}
	else {
		call('ERROR ACCESSING GAME :'.$e->outputMessage( ));
	}

	exit;
}


if ( ! $Game->is_player($_SESSION['player_id'])) {
	$Game->watch_mode = true;
	$chat_html = '';
	unset($Chat);
}

if ( ! $Game->watch_mode || $GLOBALS['Player']->is_admin) {
	$Chat = new Chat($_SESSION['player_id'], $_SESSION['game_id']);
	$chat_data = $Chat->get_box_list( );

	$chat_html = '
			<div id="chatbox">
				<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
					<input id="chat" type="text" name="chat" />
					<label for="private" class="inline"><input type="checkbox" name="private" id="private" value="yes" /> Private</label>
				</div></form>
				<dl id="chats">';

	if (is_array($chat_data)) {
		foreach ($chat_data as $chat) {
			if ('' == $chat['username']) {
				$chat['username'] = '[deleted]';
			}

			$color = 'blue';
			if ( ! empty($players[$chat['player_id']]['color']) && ('white' == $players[$chat['player_id']]['color'])) {
				$color = 'silver';
			}

			if ( ! empty($players[$chat['player_id']]['color']) && ('black' == $players[$chat['player_id']]['color'])) {
				$color = 'red';
			}

			// preserve spaces in the chat text
			$chat['message'] = htmlentities($chat['message'], ENT_QUOTES, 'ISO-8859-1', false);
			$chat['message'] = str_replace("\t", '    ', $chat['message']);
			$chat['message'] = str_replace('  ', ' &nbsp;', $chat['message']);

			$chat_html .= '
					<dt class="'.substr($color, 0, 3).'"><span>'.$chat['create_date'].'</span> '.$chat['username'].'</dt>
					<dd'.($chat['private'] ? ' class="private"' : '').'>'.$chat['message'].'</dd>';
		}
	}

	$chat_html .= '
				</dl> <!-- #chats -->
			</div> <!-- #chatbox -->';
}

// build the history table
$history_html = '';
foreach ($Game->get_move_history( ) as $i => $move) {
	if ( ! is_array($move)) {
		break;
	}

	$id = ($i * 2) + 1;

	$history_html .= '
						<tr>
							<td>'.($i + 1).'</td>
							<td id="mv_'.$id.'">'.$move[0].'</td>
							<td'.( ! empty($move[1]) ? ' id="mv_'.($id + 1).'"' : '').'>'.$move[1].'</td>
						</tr>';
}

$turn = $Game->get_turn( );
if ($GLOBALS['Player']->username == $turn) {
	$turn = '<span class="'.$Game->get_color( ).'">Your turn</span>';
}
elseif ( ! $turn) {
	$turn = '';
}
else {
	$turn = '<span class="'.$Game->get_color(false).'">'.$turn.'\'s turn</span>';
}

if (in_array($Game->state, array('Finished', 'Draw'))) {
	list($win_text, $win_class) = $Game->get_outcome($_SESSION['player_id']);
	$turn = '<span class="'.$win_class.'">Game Over: '.$win_text.'</span>';
}

$meta['title'] = htmlentities($Game->name, ENT_QUOTES, 'ISO-8859-1', false).' - #'.$_SESSION['game_id'];
$meta['show_menu'] = false;
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/game.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

	<script type="text/javascript" src="scripts/board.js"></script>
	<script type="text/javascript">/*<![CDATA[*/
		var color = "'.(isset($players[$_SESSION['player_id']]) ? (('white' == $players[$_SESSION['player_id']]['color']) ? 'silver' : 'red') : '').'";
		var state = "'.(( ! $Game->watch_mode) ? (( ! $Game->paused) ? strtolower($Game->state) : 'paused') : 'watching').'";
		var invert = '.(( ! empty($players[$_SESSION['player_id']]['color']) && ('black' == $players[$_SESSION['player_id']]['color'])) ? 'true' : 'false').';
		var my_turn = '.($Game->is_turn( ) ? 'true' : 'false').';
		var game_history = '.$Game->get_history(true).';
		var move_count = game_history.length;
		var move_index = (move_count - 1);
	/*]]>*/</script>
	<script type="text/javascript" src="scripts/game.js"></script>
';

echo get_header($meta);

?>

		<div id="contents">
			<ul id="buttons">
				<li><a href="index.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Main Page</a></li>
				<li><a href="game.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Reload Game Board</a></li>
			</ul>
			<h2>Game #<?php echo $_SESSION['game_id'].': '.htmlentities($Game->name, ENT_QUOTES, 'ISO-8859-1', false); ?> <span class="turn"><?php echo $turn; ?></span></h2>

			<div id="history">
				<div class="review">
					<span id="first">|&lt;</span>
					<span id="prev5">&lt;&lt;</span>
					<span id="prev">&lt;</span>
					<span id="next">&gt;</span>
					<span id="next5">&gt;&gt;</span>
					<span id="last">&gt;|</span>
				</div>
				<table class="history">
					<thead>
						<tr>
							<th>#</th>
							<th>Silver</th>
							<th>Red</th>
						</tr>
					</thead>
					<tbody>
						<?php echo $history_html; ?>
					</tbody>
				</table>
			</div> <!-- #history -->

			<div id="board_wrapper">
				<div id="board"></div> <!-- #board -->
				<div class="buttons">
					<a href="javascript:;" id="show_full">Show Full Move</a> ||
					<a href="javascript:;" id="show_move">Show Move</a> |
					<a href="javascript:;" id="clear_move">Clear Move</a> |
					<a href="javascript:;" id="fire_laser">Fire Laser</a> |
					<a href="javascript:;" id="clear_laser">Clear Laser</a>
				</div> <!-- .buttons -->
			</div> <!-- #board_wrapper -->

			<?php echo $chat_html; ?>

			<form id="game" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"><div class="formDiv">
				<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
				<input type="hidden" name="game_id" value="<?php echo $_SESSION['game_id']; ?>" />
				<input type="hidden" name="player_id" value="<?php echo $_SESSION['player_id']; ?>" />
				<input type="hidden" name="from" id="from" value="" />
				<input type="hidden" name="to" id="to" value="" />
				<?php if ('Playing' == $Game->state) { ?>
					<input type="button" name="resign" id="resign" value="Resign" />
				<?php } ?>
				<?php if ($Game->test_nudge( )) { ?>
					<input type="button" name="nudge" id="nudge" value="Nudge" />
				<?php } ?>
			</div></form>

		</div> <!-- #contents -->

<?php

call($GLOBALS);
echo get_footer( );

