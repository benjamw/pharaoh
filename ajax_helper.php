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


// run setup laser test fire
if (isset($_POST['test_fire'])) {
	try {
		// returns laser_path and hits arrays
		$return = Pharaoh::fire_laser($_POST['color'], $_POST['board']);
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
	else if ('resend' == $_POST['invite']) {
		// make sure we are one of the two people in the invite
		if (Game::has_invite($_POST['invite_id'], $_SESSION['player_id'])) {
			if (Game::resend_invite($_POST['invite_id'])) {
				echo 'Invite Resent';
			}
			else {
				echo 'Could not resend invite';
			}
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
if ( ! isset($Game)) {
	$Game = new Game((int) $_SESSION['game_id']);
}


// run the game refresh check
if (isset($_POST['refresh'])) {
	echo $Game->last_move;
	exit;
}

