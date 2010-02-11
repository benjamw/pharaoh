<?php

// $Id: game.php 214 2009-11-02 23:59:44Z cchristensen $

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
	$players = $Game->get_players( );
	$Chat = new Chat($_SESSION['player_id'], $_SESSION['game_id']);
	$chat_data = $Chat->get_box_list( );

	$chat_html = '
			<div id="chatbox">
				<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
					<input id="chat" type="text" name="chat" />
					<!-- <label for="private" class="inline"><input type="checkbox" name="private" id="private" value="yes" /> Private</label> -->
				</div></form>';

	if (is_array($chat_data)) {
		$chat_html .= '
				<dl id="chats">';

		foreach ($chat_data as $chat) {
			if ('' == $chat['username']) {
				$chat['username'] = '[deleted]';
			}

			$chat_html .= '
					<dt class="'.substr($players[$chat['player_id']]['color'], 0, 3).'"><span>'.$chat['create_date'].'</span> '.$chat['username'].'</dt>
					<dd'.($chat['private'] ? ' class="private"' : '').'>'.htmlentities($chat['message'], ENT_QUOTES, 'ISO-8859-1', false).'</dd>';
		}

		$chat_html .= '
				</dl> <!-- #chats -->';
	}

	$chat_html .= '
			</div> <!-- #chatbox -->';
}

$meta['title'] = htmlentities($Game->name, ENT_QUOTES, 'ISO-8859-1', false).' - #'.$_SESSION['game_id'];
$meta['show_menu'] = false;
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/game.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

	<script type="text/javascript">/*<![CDATA[*/
		var state = "'.(( ! $Game->watch_mode) ? (( ! $Game->paused) ? strtolower($Game->state) : 'paused') : 'watching').'";
		var board = "'.$Game->get_board($expanded = true).'";
		var prev_turn = ["'.$Game->get_prev_turn( ).'", '.$Game->get_prev_laser( ).'];
	/*]]>*/</script>
	<script type="text/javascript" src="scripts/board.js"></script>
	<script type="text/javascript" src="scripts/game.js"></script>
';

echo get_header($meta);

?>

		<div id="contents">
			<ul id="buttons">
				<li><a href="index.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Main Page</a></li>
				<li><a href="game.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Reload Game Board</a></li>
			</ul>
			<h2>Game #<?php echo $_SESSION['game_id'].': '.htmlentities($Game->name, ENT_QUOTES, 'ISO-8859-1', false); ?></h2>

			<div id="board"></div> <!-- #board -->

			<div id="controls">
				<div id="history">
					TODO: build this
				</div> <!-- #history -->
				<hr />
				<?php echo $chat_html; ?>
			</div> <!-- #controls -->

		</div> <!-- #contents -->

<?php

call($GLOBALS);
echo get_footer( );

