<?php

// $Id: inc.global.php 214 2009-11-02 23:59:44Z cchristensen $

$debug = false;

// set some ini stuff
ini_set('register_globals', 0); // you really should have this off anyways

date_default_timezone_set('UTC');

// deal with those lame magic quotes
if (get_magic_quotes_gpc( )) {
	function stripslashes_deep($value) {
		$value = is_array($value)
			? array_map('stripslashes_deep', $value)
			: stripslashes($value);

		return $value;
	}

	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}


/**
 *		GLOBAL INCLUDES
 * * * * * * * * * * * * * * * * * * * * * * * * * * */

$GLOBALS['__INCLUDE_ROOT'] = dirname(__FILE__).DIRECTORY_SEPARATOR;
$GLOBALS['__WEB_ROOT'] = realpath($GLOBALS['__INCLUDE_ROOT'].'..').DIRECTORY_SEPARATOR;
$GLOBALS['__CLASSES_ROOT'] = $GLOBALS['__WEB_ROOT'].'classes'.DIRECTORY_SEPARATOR;
$GLOBALS['__GAMES_ROOT'] = $GLOBALS['__WEB_ROOT'].'games'.DIRECTORY_SEPARATOR;
$GLOBALS['__LOG_ROOT'] = $GLOBALS['__WEB_ROOT'].'logs'.DIRECTORY_SEPARATOR;

ini_set('error_log', $GLOBALS['__LOG_ROOT'].'php.err');

if (is_file($GLOBALS['__INCLUDE_ROOT'].'config.php')) {
	require_once $GLOBALS['__INCLUDE_ROOT'].'config.php';
}

require_once $GLOBALS['__INCLUDE_ROOT'].'inc.version.php';
require_once $GLOBALS['__INCLUDE_ROOT'].'func.global.php';
require_once $GLOBALS['__INCLUDE_ROOT'].'html.general.php';
require_once $GLOBALS['__INCLUDE_ROOT'].'html.tables.php';

// MAKE SURE TO LOAD CLASS FILES BEFORE STARTING THE SESSION
// OR YOU END UP WITH INCOMPLETE OBJECTS PULLED FROM SESSION
spl_autoload_register('load_class');


/**
 *		GLOBAL DATA
 * * * * * * * * * * * * * * * * * * * * * * * * * * */

$GLOBALS['_&_DEBUG_QUERY'] = '';
$GLOBALS['_?_DEBUG_QUERY'] = '';

// make a list of all the color files available to use
$GLOBALS['_COLOR_LIST'] = array( );

$dh = opendir(realpath(dirname(__FILE__).'/../css'));
while (false !== ($file = readdir($dh))) {
	if (preg_match('/^c_(.+)\\.css$/i', $file, $match)) { // scanning for color files only
		$GLOBALS['_COLORS'][] = $match[1];
	}
}

// convert the full color file name to just the color portion
$GLOBALS['_DEFAULT_COLOR'] = '';
if (class_exists('Settings') && Settings::test( )) {
	$GLOBALS['_DEFAULT_COLOR'] = preg_replace('/c_(.+)\\.css/i', '$1', Settings::read('default_color'));
}

if ('' == $GLOBALS['_DEFAULT_COLOR']) {
	if (in_array('blue_white', $GLOBALS['_COLORS'])) {
		$GLOBALS['_DEFAULT_COLOR'] = 'blue_white';
	}
	else {
		$GLOBALS['_DEFAULT_COLOR'] = $GLOBALS['_COLORS'][0];
	}
}

session_start( );

// make sure we don't cross site session steal in our own site
if ( ! isset($_SESSION['PWD']) || (__FILE__ != $_SESSION['PWD'])) {
	$_SESSION = array( );
}
$_SESSION['PWD'] = __FILE__;

// set a token, we'll be passing one around a lot
if ( ! isset($_SESSION['token'])) {
	$_SESSION['token'] = md5(uniqid(rand( ), true));
}
call($_SESSION['token']);

if (test_debug( )) {
	define('DEBUG', true); // DO NOT CHANGE THIS ONE
}
else {
	define('DEBUG', (bool) $debug); // set to true for output of debugging code
}

$GLOBALS['_LOGGING'] = DEBUG; // do not change, rather, change debug value

if (Mysql::test( )) {
	$Mysql = Mysql::get_instance( );
	$Mysql->set_settings(array(
		'log_errors' => Settings::read('DB_error_log'),
		'log_path' => $GLOBALS['__LOG_ROOT'],
		'email_errors' => Settings::read('DB_error_email'),
		'email_subject' => $GLOBALS['__GAME_NAME'].' Query Error',
		'email_from' => Settings::read('from_email'),
		'email_to' => Settings::read('to_email'),
	));
}

if (defined('DEBUG') && DEBUG) {
	ini_set('display_errors','On');
	error_reporting(E_ALL | E_STRICT); // all errors, notices, and strict warnings
	if (isset($Mysql)) {
		$Mysql->set_error(3);
	}
}
else { // do not edit the following
#	ini_set('display_errors','Off');
	error_reporting(E_ALL | E_STRICT);
#	error_reporting(E_ALL & ~ E_NOTICE); // show errors, but not notices
}

// log the player in
if (( ! isset($LOGIN) || $LOGIN) && isset($Mysql)) {
	$GLOBALS['Player'] = new GamePlayer( );
	// this will redirect to login if failed
	$GLOBALS['Player']->log_in( );

	if (0 != $_SESSION['player_id']) {
		$Message = new Message($_SESSION['player_id'], $GLOBALS['Player']->is_admin);
	}
}


// grab the list of players
if (isset($Mysql)) {
	$GLOBALS['_PLAYERS'] = Player::get_array( );
}

