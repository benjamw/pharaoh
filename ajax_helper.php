<?php

$GLOBALS['NODEBUG'] = true;
$GLOBALS['AJAX'] = true;


// don't require log in when testing for used usernames and emails
if (isset($_POST['validity_test']) || (isset($_GET['validity_test']) && isset($_GET['DEBUG']))) {
	define('LOGIN', false);
}


require_once 'includes/inc.global.php';


// if we are debugging, change some things for us
// (although REQUEST_METHOD may not always be valid)
if (('GET' == $_SERVER['REQUEST_METHOD']) && test_debug( )) {
	$GLOBALS['NODEBUG'] = false;
	$GLOBALS['AJAX'] = false;
	$_GET['token'] = $_SESSION['token'];
	$_GET['keep_token'] = true;
	$_POST = $_GET;
	$DEBUG = true;
	call('AJAX HELPER');
	call($_POST);
}


// run the index page refresh checks
if (isset($_POST['timer'])) {
	$message_count = (int) Message::check_new($_SESSION['player_id']);
	$turn_count = (int) Game::check_turns($_SESSION['player_id']);
	echo $message_count + $turn_count;
	exit;
}


// run registration checks
if (isset($_POST['validity_test'])) {
#	if (('email' == $_POST['type']) && ('' == $_POST['value'])) {
#		echo 'OK';
#		exit;
#	}

	$player_id = 0;
	if ( ! empty($_POST['profile'])) {
		$player_id = (int) $_SESSION['player_id'];
	}

	switch ($_POST['validity_test']) {
		case 'username' :
		case 'email' :
			$username = '';
			$email = '';
			${$_POST['validity_test']} = sani($_POST['value']);

			$player_id = (isset($_POST['player_id']) ? (int) $_POST['player_id'] : 0);

			try {
				Player::check_database($username, $email, $player_id);
			}
			catch (MyException $e) {
				echo $e->getCode( );
				exit;
			}
			break;

		default :
			break;
	}

	echo 'OK';
	exit;
}


// run the in game chat
if (isset($_POST['chat'])) {
	try {
		if ( ! isset($_SESSION['game_id'])) {
			$_SESSION['game_id'] = 0;
		}

		$Chat = new Chat((int) $_SESSION['player_id'], (int) $_SESSION['game_id']);
		$Chat->send_message($_POST['chat'], isset($_POST['private']), isset($_POST['lobby']));
		$return = $Chat->get_box_list(1);
		$return = $return[0];
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run setup validation
if (isset($_POST['test_setup'])) {
	try {
		$Setup = new Setup( );
		$Setup->validate($_POST['setup']);

		$return['valid'] = true;
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run the invites stuff
if (isset($_POST['invite'])) {
	if ('delete' == $_POST['invite']) {
		// make sure we are one of the two people in the invite
		if (Game::has_invite($_POST['invite_id'], $_SESSION['player_id'])) {
			Game::delete_invite($_POST['invite_id']);
			echo 'Invite Deleted';
		}
		else {
			echo 'ERROR: Not your invite';
		}
	}
	else {
		// make sure we are one of the two people in the invite
		if (Game::has_invite($_POST['invite_id'], $_SESSION['player_id'], $accept = true)) {
			if ($game_id = Game::accept_invite($_POST['invite_id'])) { // single equals intended
				echo $game_id;
			}
			else {
				echo 'ERROR: Could not create game';
			}
		}
		else {
			echo 'ERROR: Not your invite';
		}
	}
	exit;
}


// init our game
$Game = new Game((int) $_SESSION['game_id']);


// do some more validity checking for the rest of the functions

if (empty($DEBUG) && empty($_POST['notoken'])) {
	test_token( ! empty($_POST['keep_token']));
}

if ($_POST['game_id'] != $_SESSION['game_id']) {
	echo 'ERROR: Incorrect game id given';
	exit;
}


// make sure we are the player we say we are
// unless we're an admin, then it's ok
$player_id = (int) $_POST['player_id'];
if (($player_id != $_SESSION['player_id']) && ! $GLOBALS['Player']->is_admin) {
	echo 'ERROR: Incorrect player id given';
	exit;
}


// run the 'Nudge' button
if (isset($_POST['nudge'])) {
	$return = array( );
	$return['token'] = $_SESSION['token'];

	try {
		$Game->nudge($player_id);
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run the 'Resign' button
if (isset($_POST['resign'])) {
	$return = array( );
	$return['token'] = $_SESSION['token'];

	try {
		$Game->resign($_SESSION['player_id']);
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run the game actions
if (isset($_POST['turn'])) {
	$return = array( );
	$return['token'] = $_SESSION['token'];

	try {
		if (false !== strpos($_POST['to'], 'split')) { // splitting obelisk
			$to = substr($_POST['to'], 0, 2);
			call($to);

			$from = Pharaoh::index_to_target($_POST['from']);
			$to = Pharaoh::index_to_target($to);
			call($from.'.'.$to);

			$return['hits'] = $Game->do_move($from.'.'.$to);
		}
		elseif ((string) $_POST['to'] === (string) (int) $_POST['to']) { // moving
			$to = $_POST['to'];
			call($to);

			$from = Pharaoh::index_to_target($_POST['from']);
			$to = Pharaoh::index_to_target($to);
			call($from.':'.$to);

			$return['hits'] = $Game->do_move($from.':'.$to);
		}
		else { // rotating
			$target = Pharaoh::index_to_target($_POST['from']);
			$dir = (int) ('r' == strtolower($_POST['to']));
			$return['hits'] = $Game->do_move($target.'-'.$dir);
		}
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}

