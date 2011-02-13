<?php

// Game Controller
// this is stuff that usually resides in ajax_refresh, but does not need ajax

// init our game
if ( ! isset($Game)) {
	$Game = new Game((int) $_SESSION['game_id']);
}


// do some validity checking

if (empty($DEBUG) && empty($_POST['notoken'])) {
	test_token( ! empty($_POST['keep_token']));
}

if ($_POST['game_id'] != $_SESSION['game_id']) {
	throw new MyException('ERROR: Incorrect game id given');
}


// make sure we are the player we say we are
// unless we're an admin, then it's ok
$player_id = (int) $_POST['player_id'];
if (($player_id != $_SESSION['player_id']) && ! $GLOBALS['Player']->is_admin) {
	throw new MyException('ERROR: Incorrect player id given');
}


// run the 'Nudge' button
if (isset($_POST['nudge'])) {
	try {
		$Game->nudge($player_id);
	}
	catch (MyException $e) {
		throw $e;
	}
}


// run the 'Resign' button
if (isset($_POST['resign'])) {
	try {
		$Game->resign($_SESSION['player_id']);
	}
	catch (MyException $e) {
		throw $e;
	}
}


// run the 'Offer Draw' button
if (isset($_POST['offer_draw'])) {
	try {
		$Game->offer_draw($_SESSION['player_id']);
	}
	catch (MyException $e) {
		throw $e;
	}
}


// run the 'Accept Draw' button
if (isset($_POST['accept_draw'])) {
	try {
		$Game->accept_draw($_SESSION['player_id']);
	}
	catch (MyException $e) {
		throw $e;
	}
}


// run the 'Reject Draw' button
if (isset($_POST['reject_draw'])) {
	try {
		$Game->reject_draw($_SESSION['player_id']);
	}
	catch (MyException $e) {
		throw $e;
	}
}


// run the game actions
if (isset($_POST['turn'])) {
	try {
		if (false !== strpos($_POST['to'], 'split')) { // splitting obelisk
			$to = substr($_POST['to'], 0, 2);
			call($to);

			$from = Pharaoh::index_to_target($_POST['from']);
			$to = Pharaoh::index_to_target($to);
			call($from.'.'.$to);

			$Game->do_move($from.'.'.$to);
		}
		elseif ((string) $_POST['to'] === (string) (int) $_POST['to']) { // moving
			$to = $_POST['to'];
			call($to);

			$from = Pharaoh::index_to_target($_POST['from']);
			$to = Pharaoh::index_to_target($to);
			call($from.':'.$to);

			$Game->do_move($from.':'.$to);
		}
		else { // rotating
			$target = Pharaoh::index_to_target($_POST['from']);
			$dir = (int) ('r' == strtolower($_POST['to']));
			call($target.'-'.$dir);

			$Game->do_move($target.'-'.$dir);
		}
	}
	catch (MyException $e) {
		throw $e;
	}

	// the game doesn't recognize the move unless it's a fresh pull
	// so kill the game and load again
	// (basically a round-about way of doing _save then _pull)
	unset($Game);
	$Game = new Game((int) $_SESSION['game_id']);
}

