<?php

// $Id: config.php.sample 162 2009-07-16 10:21:42Z cchristensen $

// ----------------------------------------------------------
// DO NOT MODIFY THIS FILE UNLESS YOU KNOW WHAT YOU ARE DOING
// ----------------------------------------------------------
	$GLOBALS['__GAME_NAME'] = 'Pharaoh';


/* Database settings */
/* ----------------- */
	$GLOBALS['_DEFAULT_DATABASE']['hostname'] = 'localhost'; // the URI of the MySQL server host
	$GLOBALS['_DEFAULT_DATABASE']['username'] = 'username'; // the MySQL user's name
	$GLOBALS['_DEFAULT_DATABASE']['password'] = 'password'; // the MySQL user's password
	$GLOBALS['_DEFAULT_DATABASE']['database'] = 'iohelix_games'; // the MySQL database name
	$GLOBALS['_DEFAULT_DATABASE']['log_path'] = $GLOBALS['__LOG_ROOT']; // the MySQL log path


/* Root settings */
/* ------------- */
	$GLOBALS['_ROOT_ADMIN'] = 'benjam'; // Permanent admin username (case-sensitive)
	$GLOBALS['_ROOT_URI']   = 'http://hexa/pharaoh/'; // The URL of the pharaoh script root (include closing / )
	$GLOBALS['_USEEMAIL']   = false; // mail( ) operations.  Test it before putting it into production


/* Table settings */
/* -------------- */
	$master_prefix = ''; // master database table prefix
	$game_prefix   = 'ph_'; // game table name prefix

	// note this table does not have the same prefix as the other tables
	define('T_PLAYER'     , $master_prefix . 'player'); // the player data table (NOTE: THERE IS NO GAME PREFIX)

	define('T_CHAT'       , $master_prefix . $game_prefix . 'chat'); // the in-game chat/personal notes table
	define('T_GAME'       , $master_prefix . $game_prefix . 'game'); // the game data table
	define('T_GAME_BOARD' , $master_prefix . $game_prefix . 'game_board'); // the game board history table
	define('T_GAME_NUDGE' , $master_prefix . $game_prefix . 'game_nudge'); // the game nudge table
	define('T_INVITE'     , $master_prefix . $game_prefix . 'invite'); // the invite table
	define('T_MESSAGE'    , $master_prefix . $game_prefix . 'message'); // the message table
	define('T_MSG_GLUE'   , $master_prefix . $game_prefix . 'message_glue'); // the player messaging glue table
	define('T_SETTINGS'   , $master_prefix . $game_prefix . 'settings'); // the settings table
	define('T_SETUP'      , $master_prefix . $game_prefix . 'setup'); // the board setups table
	define('T_PHARAOH'    , $master_prefix . $game_prefix . 'ph_player'); // the pharaoh player data table

