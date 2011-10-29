<?php
/*
+---------------------------------------------------------------------------
|
|   game.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to facilitate the game Pharaoh, it doesn't really
|	care about how to play, or the deep goings on of the game, only about
|	database structure and how to allow players to interact with the game.
|
+---------------------------------------------------------------------------
|
|   > Pharaoh (Khet) Game module
|   > Date started: 2008-02-28
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

if (defined('INCLUDE_DIR')) {
	require_once INCLUDE_DIR.'func.array.php';
}

class Game
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property GAME_TABLE
	 *		Holds the game table name
	 *
	 * @var string
	 */
	const GAME_TABLE = T_GAME;


	/** const property GAME_HISTORY_TABLE
	 *		Holds the game board table name
	 *
	 * @var string
	 */
	const GAME_HISTORY_TABLE = T_GAME_HISTORY;


	/** const property GAME_NUDGE_TABLE
	 *		Holds the game nudge table name
	 *
	 * @var string
	 */
	const GAME_NUDGE_TABLE = T_GAME_NUDGE;


	/** const property GAME_STATS_TABLE
	 *		Holds the game stats table name
	 *
	 * @var string
	 */
	const GAME_STATS_TABLE = T_STATS;


	/** static protected property _EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *
	 * @var array
	 */
	static protected $_EXTRA_INFO_DEFAULTS = array(
			'white_color' => 'random',
			'battle_dead' => false,
			'battle_immune' => false,
			'battle_front_only' => true,
			'battle_hit_self' => false,
			'move_sphynx' => false,
			'draw_offered' => false,
			'undo_requested' => false,
			'custom_rules' => '',
			'invite_setup' => '', // this is to hold the setup for invites only
		);


	/** static protected property _HISTORY_EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data for the move history
	 *
	 * @var array
	 */
	static protected $_HISTORY_EXTRA_INFO_DEFAULTS = array(
			'laser_hit' => false,
			'laser_fired' => true,
			'dead_for' => 0,
			'immune_for' => 0,
		);


	/** public property id
	 *		Holds the game's id
	 *
	 * @var int
	 */
	public $id;


	/** public property state
	 *		Holds the game's current state
	 *		can be one of 'Waiting', 'Playing', 'Finished', 'Draw'
	 *
	 * @var string (enum)
	 */
	public $state;


	/** public property turn
	 *		Holds the game's current turn
	 *		can be one of 'white', 'black'
	 *
	 * @var string
	 */
	public $turn;


	/** public property paused
	 *		Holds the game's current pause state
	 *
	 * @var bool
	 */
	public $paused;


	/** public property create_date
	 *		Holds the game's create date
	 *
	 * @var int (unix timestamp)
	 */
	public $create_date;


	/** public property modify_date
	 *		Holds the game's modified date
	 *
	 * @var int (unix timestamp)
	 */
	public $modify_date;


	/** public property last_move
	 *		Holds the game's last move date
	 *
	 * @var int (unix timestamp)
	 */
	public $last_move;


	/** public property watch_mode
	 *		Lets us know if we are just visiting this game
	 *
	 * @var bool
	 */
	public $watch_mode = false;


	/** protected property _setup
	 *		Holds the game's initial setup
	 *		as an associative array
	 *		array(
	 *			'id' => [setup_id],
	 *			'name' => [setup_name],
	 *		);
	 *
	 * @var array
	 */
	protected $_setup;


	/** protected property _laser_fired
	 *		Holds the laser fired flag
	 *
	 * @var bool did we fire the laser?
	 */
	protected $_laser_fired = false;


	/** protected property _extra_info
	 *		Holds the extra game info
	 *
	 * @var array
	 */
	protected $_extra_info;


	/** protected property _current_move_extra_info
	 *		Holds the extra current move info
	 *
	 * @var array
	 */
	protected $_current_move_extra_info;


	/** protected property _players
	 *		Holds our player's object references
	 *		along with other game data
	 *
	 * @var array of player data
	 */
	protected $_players;


	/** protected property _pharaoh
	 *		Holds the pharaoh object reference
	 *
	 * @var Pharaoh object reference
	 */
	protected $_pharaoh;


	/** protected property _history
	 *		Holds the board history
	 *
	 * @var array of pharaoh boards
	 */
	protected $_history;


	/** protected property _mysql
	 *		Stores a reference to the Mysql class object
	 *
	 * @param Mysql object
	 */
	protected $_mysql;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param int optional game id
	 * @param Mysql optional object reference
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($id = 0, Mysql $Mysql = null)
	{
		call(__METHOD__);

		$this->id = (int) $id;
		call($this->id);

		$this->_pharaoh = new Pharaoh($this->id);

		if (is_null($Mysql)) {
			$Mysql = Mysql::get_instance( );
		}

		$this->_mysql = $Mysql;

		try {
			$this->_pull( );
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function __destruct
	 *		Class destructor
	 *		Gets object ready for destruction
	 *
	 * @param void
	 * @action saves changed data
	 * @action destroys object
	 * @return void
	 */
	public function __destruct( )
	{
		// save anything changed to the database
		// BUT... only if PHP didn't die because of an error
		$error = error_get_last( );

		if ($this->id && (0 == ((E_ERROR | E_WARNING | E_PARSE) & $error['type']))) {
			try {
				$this->_save( );
			}
			catch (MyException $e) {
				// do nothing, it will be logged
			}
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'name' :
				return $this->_players['white']['object']->username.' vs '.$this->_players['black']['object']->username;
				break;

			case 'first_name' :
				if ($_SESSION['player_id'] == $this->_players['player']['player_id']) {
					return 'Your';
				}
				else {
					return $this->_players['white']['object']->username.'\'s';
				}
				break;

			case 'second_name' :
				if ($_SESSION['player_id'] == $this->_players['player']['player_id']) {
					return $this->_players['opponent']['object']->username.'\'s';
				}
				else {
					return $this->_players['black']['object']->username.'\'s';
				}
				break;

			default :
				// go to next step
				break;
		}

		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		return $this->$property;
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
	 */
	public function __set($property, $value)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		$this->$property = $value;
	}


	/** static public function invite
	 *		Creates the game from _POST data
	 *
	 * @param void
	 * @action creates an invite
	 * @return int game id
	 */
	static public function invite( )
	{
		call(__METHOD__);
		call($_POST);

		$Mysql = Mysql::get_instance( );

		// DON'T sanitize the data
		// it gets sani'd in the MySQL->insert method
		$_P = $_POST;

		// translate (filter/sanitize) the data
		$_P['white_id'] = (int) $_SESSION['player_id'];
		$_P['black_id'] = (int) $_P['opponent'];
		$_P['setup_id'] = (int) $_P['setup'];
		$_P['laser_battle'] = is_checked($_P['laser_battle_box']);
		$_P['battle_front_only'] = is_checked($_P['battle_front_only']);
		$_P['battle_hit_self'] = is_checked($_P['battle_hit_self']);
		$_P['move_sphynx'] = is_checked($_P['move_sphynx']);

		call($_P);

		// grab the setup
		$query = "
			SELECT setup_id
			FROM ".Setup::SETUP_TABLE."
		";
		$setup_ids = $Mysql->fetch_value_array($query);
		call($setup_ids);

		// check for random setup
		if (0 == $_P['setup_id']) {
			shuffle($setup_ids);
			shuffle($setup_ids);
			$_P['setup_id'] = (int) reset($setup_ids);
			sort($setup_ids);
		}

		// make sure the setup id is valid
		if ( ! in_array($_P['setup_id'], $setup_ids)) {
			throw new MyException(__METHOD__.': Setup is not valid');
		}

		$query = "
			SELECT board
			FROM ".Setup::SETUP_TABLE."
			WHERE setup_id = '{$_P['setup_id']}'
		";
		$setup = $Mysql->fetch_value($query);

		// laser battle cleanup
		// only run this if the laser battle box was open
		if ($_P['laser_battle']) {
			ifer($_P['battle_dead'], 1, false);
			ifer($_P['battle_immune'], 1);

			// we can only hit ourselves in the sides or back, never front
			if ($_P['battle_front_only']) {
				$_P['battle_hit_self'] = false;
			}

			$extra_info = array(
				'battle_dead' => (int) max((int) $_P['battle_dead'], 1),
				'battle_immune' => (int) max((int) $_P['battle_immune'], 0),
				'battle_front_only' => (bool) $_P['battle_front_only'],
				'battle_hit_self' => (bool) $_P['battle_hit_self'],
			);
		}

		$extra_info['white_color'] = $_P['color'];
		$extra_info['move_sphynx'] = $_P['move_sphynx'];

		$setup = expandFEN($setup);

		try {
			if (is_checked($_P['convert_to_1']) || is_checked($_P['rand_convert_to_1'])) {
				$setup = Setup::convert_to_1($setup);
			}
			elseif (is_checked($_P['convert_to_2']) || is_checked($_P['rand_convert_to_2'])) {
				$setup = Setup::convert_to_2($setup);
			}
		}
		catch (MyException $e) {
			throw $e;
		}

		$extra_info['invite_setup'] = packFEN($setup);

		call($extra_info);

		$diff = array_compare($extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$extra_info = $diff[0];
		ksort($extra_info);

		call($extra_info);
		if ( ! empty($extra_info)) {
			$_P['extra_info'] = serialize($extra_info);
		}

		// create the game
		$required = array(
			'white_id' ,
			'setup_id' ,
		);

		$key_list = array_merge($required, array(
			'black_id' ,
			'extra_info' ,
		));

		try {
			$_DATA = array_clean($_P, $key_list, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['state'] = 'Waiting';
		$_DATA['create_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo
		$_DATA['modify_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo

		$insert_id = $Mysql->insert(self::GAME_TABLE, $_DATA);

		if (empty($insert_id)) {
			throw new MyException(__METHOD__.': Invite could not be created');
		}

		// send the email
		if ($_DATA['black_id']) {
			Email::send('invite', $_DATA['black_id'], array('opponent' => $GLOBALS['_PLAYERS'][$_DATA['white_id']], 'page' => 'invite.php'));
		}

		return $insert_id;
	}


	/** static public function resend_invite
	 *		Resends the invite email (if allowed)
	 *
	 * @param int game id
	 * @action resends an invite email
	 * @return bool invite email sent
	 */
	static public function resend_invite($game_id)
	{
		call(__METHOD__);

		$game_id = (int) $game_id;
		$white_id = (int) $_SESSION['player_id'];

		$Mysql = Mysql::get_instance( );

		// grab the invite from the database
		$query = "
			SELECT *
				, DATE_ADD(NOW( ), INTERVAL -1 DAY) AS resend_limit
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$game_id}'
				AND state = 'Waiting'
		";
		$invite = $Mysql->fetch_assoc($query);

		if ( ! $invite) {
			throw new MyException(__METHOD__.': Player (#'.$white_id.') trying to resend a non-existant invite (#'.$game_id.')');
		}

		if ((int) $invite['white_id'] !== (int) $white_id) {
			throw new MyException(__METHOD__.': Player (#'.$white_id.') trying to resend an invite (#'.$game_id.') that is not theirs');
		}

		if ( ! (int) $invite['black_id']) {
			throw new MyException(__METHOD__.': Player (#'.$white_id.') trying to resend an invite (#'.$game_id.') that is open');
		}

		if (strtotime($invite['modify_date']) >= strtotime($invite['resend_limit'])) {
			throw new MyException(__METHOD__.': Player (#'.$white_id.') trying to resend an invite (#'.$game_id.') that is too new');
		}

		// if we get here, all is good...

		$sent = Email::send('invite', $invite['black_id'], array('opponent' => $GLOBALS['_PLAYERS'][$invite['white_id']], 'page' => 'invite.php'));

		if ($sent) {
			// update the modify_date to prevent invite resend flooding
			$_DATA['modify_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo
			$Mysql->insert(self::GAME_TABLE, $_DATA, " WHERE game_id = '{$game_id}' AND state = 'Waiting' ");
		}

		return $sent;
	}


	/** static public function accept_invite
	 *		Creates the game from invite data
	 *
	 * @param int game id
	 * @action creates a game
	 * @return int game id
	 */
	static public function accept_invite($game_id)
	{
		call(__METHOD__);

		$game_id = (int) $game_id;

		$Mysql = Mysql::get_instance( );

		// basically all we do, is set the state to Playing
		// and set the player order based on the invite data
		$query = "
			SELECT *
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$game_id}'
				AND state = 'Waiting'
		";
		$game = $Mysql->fetch_assoc($query);

		$invitor_id = $game['white_id']; // for use later
		$extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, unserialize($game['extra_info']));

		if ((('random' == $extra_info['white_color']) && mt_rand(0, 1)) || ('white' == $extra_info['white_color'])) {
			$game['white_id'] = $game['white_id'];
			$game['black_id'] = $_SESSION['player_id'];
		}
		else {
			$game['black_id'] = $game['white_id'];
			$game['white_id'] = $_SESSION['player_id'];
		}

		call($extra_info);
		unset($extra_info['white_color']);

		$board = false;
		if ( ! empty($extra_info['invite_setup'])) {
			$board = $extra_info['invite_setup'];
		}
		$extra_info['invite_setup'] = '';

		$diff = array_compare($extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$extra_info = $diff[0];
		ksort($extra_info);

		call($extra_info);
		unset($game['extra_info']);
		if ( ! empty($extra_info)) {
			$game['extra_info'] = serialize($extra_info);
		}

		$game['state'] = 'Playing';

		$Mysql->insert(self::GAME_TABLE, $game, " WHERE game_id = '{$game_id}' ");

		// add the first entry in the history table
		if (empty($board)) {
			$query = "
				INSERT INTO ".self::GAME_HISTORY_TABLE."
					(game_id, board)
				VALUES
					('{$game_id}', (
						SELECT board
						FROM ".Setup::SETUP_TABLE."
						WHERE setup_id = '{$game['setup_id']}'
					))
			";
			$Mysql->query($query);
		}
		else {
			$query = "
				INSERT INTO ".self::GAME_HISTORY_TABLE."
					(game_id, board)
				VALUES
					('{$game_id}', '{$board}')
			";
			$Mysql->query($query);
		}

		// add a used count to the setup table
		Setup::add_used($game['setup_id']);

		// send the email
		Email::send('start', $invitor_id, array('opponent' => $GLOBALS['_PLAYERS'][$_SESSION['player_id']], 'game_id' => $game_id));

		return $game_id;
	}


	/** static public function delete_invite
	 *		Deletes the given invite
	 *
	 * @param int game id
	 * @action deletes the invite
	 * @return void
	 */
	static public function delete_invite($game_id)
	{
		call(__METHOD__);

		$game_id = (int) $game_id;

		$Mysql = Mysql::get_instance( );

		$Mysql->delete(self::GAME_TABLE, " WHERE game_id = '{$game_id}' AND state = 'Waiting' ");
	}


	/** static public function has_invite
	 *		Tests if the given player has the given invite
	 *
	 * @param int game id
	 * @param int player id
	 * @param bool optional player can accept invite
	 * @return bool player has invite
	 */
	static public function has_invite($game_id, $player_id, $accept = false)
	{
		call(__METHOD__);

		$game_id = (int) $game_id;
		$player_id = (int) $player_id;
		$accept = (bool) $accept;

		$Mysql = Mysql::get_instance( );

		$open = "";
		if ($accept) {
			$open = " OR black_id IS NULL
				OR black_id = FALSE ";
		}

		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$game_id}'
				AND state = 'Waiting'
				AND (white_id = '{$player_id}'
					OR black_id = '{$player_id}'
					{$open}
				)
		";
		$has_invite = (bool) $Mysql->fetch_value($query);

		return $has_invite;
	}


	public function can_fire_laser( )
	{
		call(__METHOD__);

		if ( ! $this->_extra_info['battle_dead']) {
			return true;
		}

		return ! $this->_current_move_extra_info['dead_for'];
	}


	public function prev_laser_fired( )
	{
		$last = end($this->_history);
		return (bool) $last['laser_fired'];
	}


	/** public function do_move
	 *		Do the given move and send out emails
	 *
	 * @param string move code
	 * @action performs the move
	 * @return array indexes hit
	 */
	public function do_move($move)
	{
		call(__METHOD__);

		try {
			$this->_laser_fired = $this->can_fire_laser( );
			$hits = $this->_pharaoh->do_move($move, $this->_laser_fired);
			$winner = $this->_pharaoh->winner;
		}
		catch (MyException $e) {
			throw $e;
		}

		if ($winner) {
			if ('draw' == $winner) {
				$this->state = 'Draw';
				$this->_players['silver']['object']->add_draw( );
				$this->_players['red']['object']->add_draw( );

				// send the email
				Email::send('draw', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username));
			}
			else {
				$this->state = 'Finished';
				$this->_players[$winner]['object']->add_win( );
				$this->_players[$this->_players[$winner]['opp_color']]['object']->add_loss( );

				// send the email
				$type = (($this->_players[$winner]['player_id'] == $_SESSION['player_id']) ? 'defeated' : 'won');
				Email::send($type, $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username));
			}
		}
		else {
			// send the email
			Email::send('turn', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
		}

		return $hits;
	}


	/** public function resign
	 *		Resigns the given player from the game
	 *
	 * @param int player id
	 * @return void
	 */
	public function resign($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to resign from a game (#'.$this->id.') they are not playing in');
		}

		if ($this->_players['player']['player_id'] != $player_id) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to resign opponent from a game (#'.$this->id.')');
		}

		$this->_players['opponent']['object']->add_win( );
		$this->_players['player']['object']->add_loss( );
		$this->state = 'Finished';
		$this->_pharaoh->winner = 'opponent';
		Email::send('resigned', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
	}


	/** public function offer_draw
	 *		Offers a draw to the given player's apponent
	 *
	 * @param int player id
	 * @return void
	 */
	public function offer_draw($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to offer draw in a game (#'.$this->id.') they are not playing in');
		}

		if ($this->_players['player']['player_id'] != $player_id) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to offer draw for an opponent in game (#'.$this->id.')');
		}

		$this->_extra_info['draw_offered'] = $player_id;

		Email::send('draw_offered', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
	}


	/** public function draw_offered
	 *		Returns the state of the game draw for the given player
	 *		or in general if no player given
	 *
	 * @param int [optional] player id
	 * @return bool draw state
	 */
	public function draw_offered($player_id = false)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		// if the draw was offered AND player is blank or player is not the one who offered the draw
		if (($this->_extra_info['draw_offered']) && ( ! $player_id || ($player_id != $this->_extra_info['draw_offered']))) {
			return true;
		}

		return false;
	}


	/** public function accept_draw
	 *		Accepts a draw offered to the given player
	 *
	 * @param int player id
	 * @return void
	 */
	public function accept_draw($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to accept draw in a game (#'.$this->id.') they are not playing in');
		}

		if (($this->_players['player']['player_id'] != $player_id) || ($this->_extra_info['draw_offered'] == $player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to accept draw for an opponent in game (#'.$this->id.')');
		}

		$this->_players['opponent']['object']->add_draw( );
		$this->_players['player']['object']->add_draw( );
		$this->state = 'Draw';
		$this->_extra_info['draw_offered'] = false;

		Email::send('draw', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
	}


	/** public function reject_draw
	 *		Rejects a draw offered to the given player
	 *
	 * @param int player id
	 * @return void
	 */
	public function reject_draw($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to reject draw in a game (#'.$this->id.') they are not playing in');
		}

		if (($this->_players['player']['player_id'] != $player_id) || ($this->_extra_info['draw_offered'] == $player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to reject draw for an opponent in game (#'.$this->id.')');
		}

		$this->_extra_info['draw_offered'] = false;
	}


	/** public function request_undo
	 *		Requests an undo from the given player's apponent
	 *
	 * @param int player id
	 * @return void
	 */
	public function request_undo($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to request undo in a game (#'.$this->id.') they are not playing in');
		}

		if ($this->_players['player']['player_id'] != $player_id) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to request undo from an opponent in game (#'.$this->id.')');
		}

		$this->_extra_info['undo_requested'] = $player_id;

		Email::send('undo_requested', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
	}


	/** public function undo_requested
	 *		Returns the state of the game undo for the given player
	 *		or in general if no player given
	 *
	 * @param int [optional] player id
	 * @return bool draw state
	 */
	public function undo_requested($player_id = false)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		// if the undo was requested AND player is blank or player is not the one who offered the draw
		if (($this->_extra_info['undo_requested']) && ( ! $player_id || ($player_id != $this->_extra_info['undo_requested']))) {
			return true;
		}

		return false;
	}


	/** public function accept_undo
	 *		Accepts an undo requested to the given player
	 *
	 * @param int player id
	 * @return void
	 */
	public function accept_undo($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to accept undo in a game (#'.$this->id.') they are not playing in');
		}

		if (($this->_players['player']['player_id'] != $player_id) || ($this->_extra_info['draw_offered'] == $player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to accept undo for an opponent in game (#'.$this->id.')');
		}

		// we need to adjust the database here
		// it's not really possible via the _save function
		$this->_mysql->delete(self::GAME_HISTORY_TABLE, "
			WHERE `game_id` = '{$this->id}'
			ORDER BY `move_date` DESC
			LIMIT 1
		");

		// and fix up the game data
		$this->_pull( );
		$this->_extra_info['undo_requested'] = false;

		Email::send('undo_accepted', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
	}


	/** public function reject_undo
	 *		Rejects an undo requested to the given player
	 *
	 * @param int player id
	 * @return void
	 */
	public function reject_undo($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! $this->is_player($player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to reject undo in a game (#'.$this->id.') they are not playing in');
		}

		if (($this->_players['player']['player_id'] != $player_id) || ($this->_extra_info['draw_offered'] == $player_id)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to reject undo for an opponent in game (#'.$this->id.')');
		}

		$this->_extra_info['undo_requested'] = false;
	}


	/** public function is_player
	 *		Tests if the given ID is a player in the game
	 *
	 * @param int player id
	 * @return bool player is in game
	 */
	public function is_player($player_id)
	{
		$player_id = (int) $player_id;

		return ((isset($this->_players['white']['player_id']) && ($player_id == $this->_players['white']['player_id']))
			|| (isset($this->_players['black']['player_id']) && ($player_id == $this->_players['black']['player_id'])));
	}


	/** public function get_color
	 *		Returns the requested player's color
	 *
	 * @param bool current player is requested player
	 * @return string requested player's color (or false on failure)
	 */
	public function get_color($player = true)
	{
		$request = (((bool) $player) ? 'player' : 'opponent');
		return ((isset($this->_players[$request]['color'])) ? $this->_players[$request]['color'] : false);
	}


	/** public function is_turn
	 *		Returns the requested player's turn
	 *
	 * @param bool current player is requested player
	 * @return bool is the requested player's turn
	 */
	public function is_turn($player = true)
	{
		if ('Playing' != $this->state) {
			return false;
		}

		if ($this->_extra_info['draw_offered']) {
			return false;
		}

		$request = (((bool) $player) ? 'player' : 'opponent');
		return ((isset($this->_players[$request]['turn'])) ? (bool) $this->_players[$request]['turn'] : false);
	}


	/** public function get_turn
	 *		Returns the name of the player who's turn it is
	 *
	 * @param void
	 * @return string current player's name
	 */
	public function get_turn( )
	{
		return ((isset($this->turn) && isset($this->_players[$this->turn]['object'])) ? $this->_players[$this->turn]['object']->username : false);
	}


	/** public function get_extra
	 *		Returns details of the extra_info var
	 *
	 * @param void
	 * @return string extra info details
	 */
	public function get_extra( )
	{
		call(__METHOD__);
		$return = array( );

		if ($this->_extra_info['battle_dead']) {
			$front_abbr = $front_text = '';
			if ($this->_pharaoh->has_sphynx) {
				$front_abbr = ', Front Only';
				$front_text = ($this->_extra_info['battle_front_only'] ? ', Yes' : ', No');
			}

			$return[] = '<span>Laser Battle (<abbr title="Dead, Immune'.$front_abbr.'">'.$this->_extra_info['battle_dead'].', '.$this->_extra_info['battle_immune'].$front_text.'</abbr>)</span>';
		}

		if ($this->_pharaoh->has_sphynx && $this->_extra_info['move_sphynx']) {
			$return[] = '<span>Movable Sphynx</span>';
		}

		return implode(' | ', $return);
	}


	/** public function get_extra_info
	 *		Returns the extra_info var
	 *
	 * @param void
	 * @return array extra info
	 */
	public function get_extra_info( )
	{
		return $this->_extra_info;
	}


	/** public function get_board
	 *		Returns the current board
	 *
	 * @param bool optional return expanded FEN
	 * @param int optional history index
	 * @return string board FEN (or xFEN)
	 */
	public function get_board($index = null, $expanded = false)
	{
		call(__METHOD__);

		if (is_null($index)) {
			$index = count($this->_history) - 1;
		}

		$index = (int) $index;
		$expanded = (bool) $expanded;

		if (isset($this->_history[$index])) {
			$board = $this->_history[$index]['board'];
		}
		else {
			return false;
		}

		if ($expanded) {
			return expandFEN($board);
		}

		return $board;
	}


	/** public function get_move_history
	 *		Returns the game move history
	 *
	 * @param void
	 * @return array game history
	 */
	public function get_move_history( )
	{
		call(__METHOD__);

		$history = $this->_history;
		array_shift($history); // remove the empty first move

		$return = array( );
		foreach ($history as $i => $ply) {
			if (false !== strpos($ply['move'], '-')) {
				$ply['move'] = str_replace(array('-0','-1'), array('-L','-R'), $ply['move']);
			}

			$return[floor($i / 2)][$i % 2] = $ply['move'];
		}

		if (isset($i) && (0 == ($i % 2))) {
			++$i;
			$return[floor($i / 2)][$i % 2] = '';
		}

		return $return;
	}


	/** public function get_history
	 *		Returns the game history
	 *
	 * @param bool optional return as JSON string
	 * @return array or string game history
	 */
	public function get_history($json = false)
	{
		call(__METHOD__);
		call($json);

		$json = (bool) $json;

		if ( ! $json) {
			return $this->_history;
		}

		$history = array( );
		foreach ($this->_history as $i => $node) {
			$move = $this->get_move($i);
			if ($move) {
				$move = array_unique(array_values($move));
			}

			$history[] = array(
				expandFEN($node['board']),
				$move,
				(($this->_history[$i]['laser_fired']) ? $this->get_laser_path($i) : array( )),
				$this->get_hit_data($i),
				$this->get_battle_data($i, true),
			);
		}

		return json_encode($history);
	}


	/** public function get_move
	 *		Returns the data for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or string previous turn
	 */
	public function get_move($index = null, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($json);

		if (is_null($index)) {
			$index = count($this->_history) - 1;
		}

		$index = (int) $index;
		$json = (bool) $json;

		$turn = $this->_history[$index];
		$board = expandFEN($turn['board']);
		if ( ! empty($this->_history[$index - 1])) {
			$board = expandFEN($this->_history[$index - 1]['board']);
		}

		if ( ! $turn['move']) {
			if ($json) {
				return 'false';
			}

			return false;
		}

		$move = array( );

		$move[0] = Pharaoh::target_to_index(substr($turn['move'], 0, 2));

		if ('-' == $turn['move'][2]) {
			$move[1] = $move[0][0];
			$move[2] = $turn['move'][3];
		}
		else {
			$move[1] = Pharaoh::target_to_index(substr($turn['move'], 3, 2));
			$move[2] = (int) (':' == $turn['move'][2]);
		}

		$move[3] = Pharaoh::get_piece_color($board[$move[0]]);

		if ($json) {
			return json_encode($move);
		}

		$move['from'] = $move[0];
		$move['to'] = $move[1];
		$move['extra'] = $move[2];
		$move['color'] = $move[3];

		return $move;
	}


	/** public function get_laser_path
	 *		Returns the laser path for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or JSON string laser path
	 */
	public function get_laser_path($index = null, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($json);

		if (is_null($index)) {
			$index = count($this->_history) - 1;
		}

		$index = (int) $index;
		$json = (bool) $json;

		$count = count($this->_history);
		call($count);
		call($this->_history[$index]);

		if ((1 > $index) || ($index > ($count - 1))) {
			if ($json) {
				return '[]';
			}

			return false;
		}

		$color = ((0 != ($index % 2)) ? 'silver' : 'red');
		call($color);

		// here we need to do the move, store the board as is
		// and then fire the laser.
		// the reason being: if we just fire the laser at the previous board,
		// if the piece hit was rotated into the beam, it will not display correctly
		// and if we try and fire the laser at the current board, it will pass through
		// any hit piece because it's no longer there (unless it's a stacked obelisk, of course)

		// create a dummy pharaoh class and set the board as it was prior to the laser (-1)
		$PH = new Pharaoh( );
		$PH->set_board(expandFEN($this->_history[$index - 1]['board']));
		$PH->set_extra_info($this->_extra_info);

		// do the move, but do not fire the laser, and store the board
		$pre_board = $PH->do_move($this->_history[$index]['move'], false);

		// now fire the laser at that board
		$return = Pharaoh::fire_laser($color, $pre_board, $this->_pharaoh->get_extra_info( ));

		if ($json) {
			return json_encode($return['laser_path']);
		}

		return $return['laser_path'];
	}


	/** public function get_hit_data
	 *		Returns the turn data for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or string previous turn
	 */
	public function get_hit_data($index = null, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($json);

		if (is_null($index)) {
			$index = count($this->_history) - 1;
		}

		$index = (int) $index;
		$json = (bool) $json;

		$move_data = array( );

		// because we may have only hit the piece at A8 (idx: 0), this will
		// return false unless we test for that location specifically
		if ($this->_history[$index]['hits'] || ('0' === $this->_history[$index]['hits'])) {
			// we need to grab the previous board here, and perform the move
			// without firing the laser so we get the proper orientation
			// of the pieces as they were hit in case any pieces were rotated
			// into the beam

			// create a dummy pharaoh class and set the board as it was prior to the laser (-1)
			$PH = new Pharaoh( );
			$PH->set_board(expandFEN($this->_history[$index - 1]['board']));
			$PH->set_extra_info($this->_extra_info);

			// do the move and store that board
			$prev_board = $PH->do_move($this->_history[$index]['move'], false);

			$pieces = array( );
			$hits = array_trim($this->_history[$index]['hits'], 'int');
			foreach ($hits as $hit) {
				$pieces[] = $prev_board[$hit];
			}

			$move_data = compact('hits', 'pieces');
		}

		if ($json) {
			return json_encode($move_data);
		}

		return $move_data;
	}


	/** public function get_battle_data
	 *		Returns the battle data for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or string battle data
	 */
	public function get_battle_data($index = null, $simple = false, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($simple);
		call($json);

		if (is_null($index)) {
			$index = count($this->_history) - 1;
		}

		$index = (int) $index;
		$simple = (bool) $simple;
		$json = (bool) $json;

		$battle_data = array(
			'silver' => array(
				'dead' => 0,
				'immune' => 0,
			),
			'red' => array(
				'dead' => 0,
				'immune' => 0,
			),
		);

		$color = ((0 != ($index % 2)) ? 'silver' : 'red');
		$other = (('silver' == $color) ? 'red' : 'silver');
		call($color);

		if (isset($this->_history[$index])) {
			// grab the current data
			$battle_data[$color]['dead'] = $this->_history[$index]['dead_for'];
			$battle_data[$color]['immune'] = $this->_history[$index]['immune_for'];

			// for the game display, we want to decrease the value here
			$dead = false;
			if ($battle_data[$color]['dead']) {
				$dead = true;
				--$battle_data[$color]['dead'];
			}

			if ($battle_data[$color]['immune']) {
				--$battle_data[$color]['immune'];
			}

			// if we recently came back to life, show that
			if ($dead && ! $battle_data[$color]['dead']) {
				$battle_data[$color]['immune'] = $this->_extra_info['battle_immune'];
			}

			// grab the future data for the other player
			if (isset($this->_history[$index + 1])) {
				$battle_data[$other]['dead'] = $this->_history[$index + 1]['dead_for'];
				$battle_data[$other]['immune'] = $this->_history[$index + 1]['immune_for'];
			}
			else {
				// there is no next move, we need to calculate it based on the previous one
				// if there is no previous move data, the values will stay as defaults
				if (isset($this->_history[$index - 1])) {
					$m_extra_info = $this->_gen_move_extra_info($this->_history[$index], $this->_history[$index - 1]);
					$battle_data[$other]['dead'] = $m_extra_info['dead_for'];
					$battle_data[$other]['immune'] = $m_extra_info['immune_for'];
				}
			}
		}

		if ($simple) {
			$battle_data = array(
				array($battle_data['silver']['dead'], $battle_data['silver']['immune']),
				array($battle_data['red']['dead'], $battle_data['red']['immune']),
			);
		}

		if ($json) {
			return json_encode($battle_data);
		}

		return $battle_data;
	}


	/** public function get_setup_name
	 *		Returns the name of the initial setup
	 *		used for this game
	 *
	 * @param void
	 * @return string initial setup name
	 */
	public function get_setup_name( )
	{
		return $this->_setup['name'];
	}


	/** public function get_setup
	 *		Returns the board of the initial setup
	 *		used for this game
	 *
	 * @param void
	 * @return string initial setup name
	 */
	public function get_setup( )
	{
		return $this->_history[0]['board'];
	}


	/** public function nudge
	 *		Nudges the given player to take their turn
	 *
	 * @param void
	 * @return bool success
	 */
	public function nudge( )
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		if ($this->test_nudge( )) {
			Email::send('nudge', $this->_players['opponent']['player_id'], array('opponent' => $this->_players['player']['object']->username, 'game_id' => $this->id));
			$this->_mysql->delete(self::GAME_NUDGE_TABLE, " WHERE game_id = '{$this->id}' ");
			$this->_mysql->insert(self::GAME_NUDGE_TABLE, array('game_id' => $this->id, 'player_id' => $this->_players['opponent']['player_id']));
			return true;
		}

		return false;
	}


	/** public function test_nudge
	 *		Tests if the current player can nudge or not
	 *
	 * @param void
	 * @return bool player can be nudged
	 */
	public function test_nudge( )
	{
		call(__METHOD__);

		$player_id = (int) $this->_players['opponent']['player_id'];

		if ( ! $this->is_player($player_id) || $this->is_turn( ) || ('Playing' != $this->state) || $this->paused) {
			return false;
		}

		try {
			$nudge_time = Settings::read('nudge_flood_control');
		}
		catch (MyException $e) {
			return false;
		}

		if (-1 == $nudge_time) {
			return false;
		}
		elseif (0 == $nudge_time) {
			return true;
		}

		// check the nudge status for this game/player
		// 'now' is taken from the DB because it may
		// have a different time from the PHP server
		$query = "
			SELECT NOW( ) AS now
				, G.modify_date AS move_date
				, GN.nudged
			FROM ".self::GAME_TABLE." AS G
				LEFT JOIN ".self::GAME_NUDGE_TABLE." AS GN
					ON (GN.game_id = G.game_id
						AND GN.player_id = '{$player_id}')
			WHERE G.game_id = '{$this->id}'
				AND G.state = 'Playing'
		";
		$dates = $this->_mysql->fetch_assoc($query);

		if ( ! $dates) {
			return false;
		}

		// check the dates
		// if the move date is far enough in the past
		//  AND the player has not been nudged
		//   OR the nudge date is far enough in the past
		if ((strtotime($dates['move_date']) <= strtotime('-'.$nudge_time.' hour', strtotime($dates['now'])))
			&& ((empty($dates['nudged']))
				|| (strtotime($dates['nudged']) <= strtotime('-'.$nudge_time.' hour', strtotime($dates['now'])))))
		{
			return true;
		}

		return false;
	}


	/** public function get_players
	 *		Grabs the player array
	 *
	 * @param void
	 * @return array player data
	 */
	public function get_players( )
	{
		$players = array( );

		foreach (array('white','black') as $color) {
			$player_id = $this->_players[$color]['player_id'];
			$players[$player_id] = $this->_players[$color];
			$players[$player_id]['username'] = $this->_players[$color]['object']->username;
			unset($players[$player_id]['object']);
		}

		return $players;
	}


	/** public function get_outcome
	 *		Returns the outcome string and outcome
	 *
	 * @param int id of observing player
	 * @return array (outcome text, outcome string)
	 */
	public function get_outcome($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ( ! in_array($this->state, array('Finished', 'Draw'))) {
			return false;
		}

		if ('Draw' == $this->state) {
			return array('Draw Game', 'lost');
		}

		if ( ! empty($this->_pharaoh->winner) && isset($this->_players[$this->_pharaoh->winner]['player_id'])) {
			$winner = $this->_players[$this->_pharaoh->winner]['player_id'];
		}
		else {
			$query = "
				SELECT G.winner_id
				FROM ".self::GAME_TABLE." AS G
				WHERE G.game_id = '{$this->id}'
			";
			$winner = $this->_mysql->fetch_value($query);
		}

		if ( ! $winner) {
			return false;
		}

		if ($player_id == $winner) {
			return array('You Won !', 'won');
		}
		else {
			return array($GLOBALS['_PLAYERS'][$winner].' Won', 'lost');
		}
	}


	/** static public function write_game_file
	 *		Writes the game to a text file for storage
	 *
	 * @param int game id
	 * @action writes the game PGN file
	 * @return string PGN
	 */
	static public function write_game_file($game_id)
	{
		// the PGN export format is very exact when it comes to what is allowed
		// and what is not allowed when creating a PGN file.
		// first, the only new line character that is allowed is a single line feed character
		// output in PHP as \n, this means that \r is not allowed, nor is \r\n
		// second, no tab characters are allowed, neither vertical, nor horizontal (\t)
		// third, comments do NOT nest, thus { { } } will be in error, as will { ; }
		// fourth, { } denotes an inline comment, where ; denotes a 'rest of line' comment
		// fifth, a percent sign (%) at the beginning of a line denotes a whole line comment
		// sixth, comments may not be included in the meta tags ( [Meta "data"] )

		$Mysql = Mysql::get_instance( );

		if (empty($game_id)) {
			throw new MyException(__METHOD__.': No game id given');
		}

		try {
			$Game = new Game($game_id);
		}
		catch (MyException $e) {
			throw $e;
		}

		if ( ! isset($Game->_history[1])) {
			return false;
		}

		$start_date = date('Y-m-d', strtotime($Game->_history[1]['move_date']));
		$white_name = $Game->_players['white']['object']->lastname.', '.$Game->_players['white']['object']->firstname.' ('.$Game->_players['white']['object']->username.')';
		$black_name = $Game->_players['black']['object']->lastname.', '.$Game->_players['black']['object']->firstname.' ('.$Game->_players['black']['object']->username.')';

		$extra_info = $Game->_extra_info;
		$diff = array_compare($extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$extra_info = $diff[0];

		unset($extra_info['white_color']);
		unset($extra_info['draw_offered']);
		unset($extra_info['undo_requested']);

		$xheadxtra = '';
		$xheader = "[Event \"Pharaoh (Khet) Casual Game #{$Game->id}\"]\n"
					. "[Site \"{$GLOBALS['_ROOT_URI']}\"]\n"
					. "[Date \"{$start_date}\"]\n"
					. "[Round \"-\"]\n"
					. "[White \"{$white_name}\"]\n"
					. "[Black \"{$black_name}\"]\n"
					. "[Setup \"{$Game->_history[0]['board']}\"]\n";

		if ($extra_info) {
			$options = serialize($extra_info);
			$xheadxtra .= "[Options \"{$options}\"]\n";
		}

		$xheadxtra .= "[Mode \"ICS\"]\n";

		$body = '';
		$line = '';
		$token = '';
		foreach ($Game->_history as $key => $move) {
			// skip the first entry
			if ( ! $key) {
				continue;
			}

			if (0 != ($key % 2)) {
				$token = floor(($key + 1) / 2) . '. ' . $move['move'];
			}
			else {
				$token .= ' ' . $move['move'];

				if ((strlen($line) + strlen($token)) > 79) {
					$body .= $line . "\n";
					$line = '';
				}
				elseif (strlen($line) > 0) {
					$line .= ' ';
				}

				$line .= $token;
				$token = '';
			}
		}

		if ($token) {
			if ((strlen($line) + strlen($token)) > 79) {
				$body .= $line . "\n";
				$line = '';
			}
			elseif (strlen($line) > 0) {
				$line .= ' ';
			}

			$line .= $token;
			$token = '';
		}

		// finish up the PGN with the game result
		$result = '*';
		if ('Finished' == $Game->state) {
			if ('white' == $Game->turn) {
				$result = '1-0';
			}
			else {
				$result = '0-1';
			}
		}
		elseif ('Draw' == $Game->state) {
			$result = '1/2-1/2';
		}

		$body .= $line;

		if ((strlen($line) + strlen($result)) > 79) {
			$body .= "\n";
		}
		elseif (strlen($line) > 0) {
			$body .= ' ';
		}

		$body .= $result . "\n";
		$xheader .= "[Result \"$result\"]\n";

		$data = $xheader . $xheadxtra . "\n" . $body;

		$filename = GAMES_DIR."/Pharoah_Game_{$Game->id}_".str_replace(array(' ','-',':'), '', $Game->_history[count($Game->_history) - 1]['move_date']).'.pgn';
		file_put_contents($filename, $data);

		return $data;
	}


	protected function _gen_move_extra_info($curr_move, $prev_move = null)
	{
		call(__METHOD__);

		$m_extra_info = self::$_HISTORY_EXTRA_INFO_DEFAULTS;
		if ( ! $this->_extra_info['battle_dead']) {
			return $m_extra_info;
		}

		if ( ! $curr_move) {
			throw new MyException(__METHOD__.': Move data not present for calculation');
		}

		// set our current move extra info
		if ($prev_move) {
			$m_extra_info['dead_for'] = $prev_move['dead_for'];
			$m_extra_info['immune_for'] = $prev_move['immune_for'];
		}

		$dead = false;
		if ($m_extra_info['dead_for']) {
			$dead = true;
		}

		if (0 < $m_extra_info['dead_for']) {
			--$m_extra_info['dead_for'];
		}

		// we are allowed to shoot now, set our immunity
		if ($dead && ! $m_extra_info['dead_for']) {
			$m_extra_info['immune_for'] = $this->_extra_info['battle_immune'];
		}

		if ( ! $dead && (0 < $m_extra_info['immune_for'])) {
			--$m_extra_info['immune_for'];
		}

		if ( ! $m_extra_info['dead_for'] && ! $m_extra_info['immune_for'] && $curr_move['laser_hit']) {
			$m_extra_info['dead_for'] = $this->_extra_info['battle_dead'];
		}

		return $m_extra_info;
	}


	/** protected function _pull
	 *		Pulls the data from the database
	 *		and sets up the objects
	 *
	 * @param void
	 * @action pulls the game data
	 * @return void
	 */
	protected function _pull( )
	{
		call(__METHOD__);

		if ( ! $this->id) {
			return false;
		}

		if ( ! $_SESSION['player_id']) {
			throw new MyException(__METHOD__.': Player id is not in session when pulling game data');
		}

		// grab the game data
		$query = "
			SELECT *
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$this->id}'
		";
		$result = $this->_mysql->fetch_assoc($query);
		call($result);

		if ( ! $result) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		if ('Waiting' == $result['state']) {
			throw new MyException(__METHOD__.': Game (#'.$this->id.') is still only an invite');
		}

		// set the properties
		$this->state = $result['state'];
		$this->paused = (bool) $result['paused'];
		$this->create_date = strtotime($result['create_date']);
		$this->modify_date = strtotime($result['modify_date']);

		$this->_extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, unserialize($result['extra_info']));

		// just empty this out, we don't need it anymore
		$this->_extra_info['invite_setup'] = '';

		// grab the initial setup
		// TODO: convert to the setup object
		// (need to build more in the setup object)
		$query = "
			SELECT *
			FROM ".Setup::SETUP_TABLE."
			WHERE setup_id = '{$result['setup_id']}'
		";
		$setup = $this->_mysql->fetch_assoc($query);
		call($setup);

		// the setup may have been deleted
		if ( ! $setup) {
			$this->_setup = array(
				'id' => $result['setup_id'],
				'name' => '[DELETED]',
			);
		}
		else {
			$this->_setup = array(
				'id' => $result['setup_id'],
				'name' => $setup['name'],
			);
		}

		// set up the players
		$this->_players['white']['player_id'] = $result['white_id'];
		$this->_players['white']['object'] = new GamePlayer($result['white_id']);
		$this->_players['silver'] = & $this->_players['white'];

		$this->_players['black']['player_id'] = $result['black_id'];
		if (0 != $result['black_id']) { // we may have an open game
			$this->_players['black']['object'] = new GamePlayer($result['black_id']);
			$this->_players['red'] = & $this->_players['black'];
		}

		// we test this first one against the black id, so if it fails because
		// the person viewing the game is not playing in the game (viewing it
		// after it's finished) we want "player" to be equal to "white"
		if ($_SESSION['player_id'] == $result['black_id']) {
			$this->_players['player'] = & $this->_players['black'];
			$this->_players['player']['color'] = 'black';
			$this->_players['player']['opp_color'] = 'white';
			$this->_players['opponent'] = & $this->_players['white'];
			$this->_players['opponent']['color'] = 'white';
			$this->_players['opponent']['opp_color'] = 'black';
		}
		else {
			$this->_players['player'] = & $this->_players['white'];
			$this->_players['player']['color'] = 'white';
			$this->_players['player']['opp_color'] = 'black';
			$this->_players['opponent'] = & $this->_players['black'];
			$this->_players['opponent']['color'] = 'black';
			$this->_players['opponent']['opp_color'] = 'white';
		}

		// set up the board
		$query = "
			SELECT *
			FROM ".self::GAME_HISTORY_TABLE."
			WHERE game_id = '{$this->id}'
			ORDER BY move_date ASC
		";
		$result = $this->_mysql->fetch_array($query);
		call($result);

		if ($result) {
			$count = count($result);

			// integrate the extra info into the history array
			foreach ($result as & $move) {
				$m_extra = array_merge_plus(self::$_HISTORY_EXTRA_INFO_DEFAULTS, unserialize($move['extra_info']));
				$move = array_merge($move, $m_extra);
			}
			unset($move); // kill the reference

			$this->_history = $result;
			$this->turn = ((0 == ($count % 2)) ? 'black' : 'white');
			$this->last_move = strtotime($result[$count - 1]['move_date']);

			try {
				$this->_pharaoh = new Pharaoh( );
				$this->_pharaoh->set_board(expandFEN($this->_history[$count - 1]['board']));
				$this->_pharaoh->set_extra_info($this->_extra_info);
			}
			catch (MyException $e) {
				throw $e;
			}

			$m_extra_info = $this->_gen_move_extra_info($this->_history[$count - 1], (isset($this->_history[$count - 2]) ? $this->_history[$count - 2] : null));
			$this->_current_move_extra_info = array_merge_plus(self::$_HISTORY_EXTRA_INFO_DEFAULTS, $m_extra_info);
		}
		else {
			$this->last_move = $this->create_date;
		}

		$this->_players[$this->turn]['turn'] = true;
	}


	/** protected function _save
	 *		Saves all changed data to the database
	 *
	 * @param void
	 * @action saves the game data
	 * @return void
	 */
	protected function _save( )
	{
		call(__METHOD__);

		// grab the base game data
		$query = "
			SELECT state
				, extra_info
				, modify_date
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$this->id}'
				AND state <> 'Waiting'
		";
		$game = $this->_mysql->fetch_assoc($query);
		call($game);

		$update_modified = false;

		if ( ! $game) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		$this->_log('DATA SAVE: #'.$this->id.' @ '.time( )."\n".' - '.$this->modify_date."\n".' - '.strtotime($game['modify_date']));

		// test the modified date and make sure we still have valid data
		call($this->modify_date);
		call(strtotime($game['modify_date']));
		if ($this->modify_date != strtotime($game['modify_date'])) {
			$this->_log('== FAILED ==');
			throw new MyException(__METHOD__.': Trying to save game (#'.$this->id.') with out of sync data');
		}

		$update_game = false;
		call($game['state']);
		call($this->state);
		if ($game['state'] != $this->state) {
			$update_game['state'] = $this->state;

			if ('Finished' == $this->state) {
				$update_game['winner_id'] = $this->_players[$this->_pharaoh->winner]['player_id'];
			}

			if (in_array($this->state, array('Finished', 'Draw'))) {
				try {
					$this->_add_stats( );
				}
				catch (MyException $e) {
					// do nothing, it gets logged
				}
			}
		}

		$diff = array_compare($this->_extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$update_game['extra_info'] = $diff[0];
		ksort($update_game['extra_info']);

		$update_game['extra_info'] = serialize($update_game['extra_info']);

		if ('a:0:{}' == $update_game['extra_info']) {
			$update_game['extra_info'] = null;
		}

		if (0 === strcmp($game['extra_info'], $update_game['extra_info'])) {
			unset($update_game['extra_info']);
		}

		if ($update_game) {
			$update_modified = true;
			$this->_mysql->insert(self::GAME_TABLE, $update_game, " WHERE game_id = '{$this->id}' ");
		}

		// update the board
		$color = $this->_players['player']['color'];
		call($color);
		call('IN-GAME SAVE');

		// grab the current board from the database
		$query = "
			SELECT *
			FROM ".self::GAME_HISTORY_TABLE."
			WHERE game_id = '{$this->id}'
			ORDER BY move_date DESC
			LIMIT 1
		";
		$move = $this->_mysql->fetch_assoc($query);
		$board = $move['board'];
		call($board);

		$new_board = packFEN($this->_pharaoh->get_board( ));
		call($new_board);
		list($new_move, $new_hits) = $this->_pharaoh->get_move( );
		call($new_move);
		call($new_hits);

		if (is_array($new_hits)) {
			$new_hits = implode(',', $new_hits);
		}

		if ($new_board != $board) {
			call('UPDATED BOARD');
			$update_modified = true;

			$this->_current_move_extra_info['laser_fired'] = (bool) $this->can_fire_laser( );
			$this->_current_move_extra_info['laser_hit'] = (bool) $this->_pharaoh->laser_hit;
			$diff = array_compare($this->_current_move_extra_info, self::$_HISTORY_EXTRA_INFO_DEFAULTS);
			$m_extra_info = $diff[0];
			ksort($m_extra_info);

			$m_extra_info = serialize($m_extra_info);

			if ('a:0:{}' == $m_extra_info) {
				$m_extra_info = null;
			}

			$this->_mysql->insert(self::GAME_HISTORY_TABLE, array('board' => $new_board, 'move' => $new_move, 'hits' => $new_hits, 'extra_info' => $m_extra_info, 'game_id' => $this->id));
		}

		// update the game modified date
		if ($update_modified) {
			$this->_mysql->insert(self::GAME_TABLE, array('modify_date' => NULL), " WHERE game_id = '{$this->id}' ");
		}
	}


	/** protected function _add_stats
	 *		Adds data to the stats table
	 *
	 * @param void
	 * @action adds stats to stats table
	 * @return void
	 */
	protected function _add_stats( )
	{
		call(__METHOD__);
		call($this);

		if ( ! in_array($this->state, array('Finished', 'Draw'))) {
			throw new MyException (__METHOD__.': Game (#'.$this->id.') is not finished ('.$this->state.')');
		}

		$count = count($this->_history) - 1;

		$start = $end = $this->_history[0]['move_date'];
		if ($count) {
			$start = $this->_history[1]['move_date'];
			$end = $this->_history[$count]['move_date'];
		}

		$start_unix = strtotime($start);
		$end_unix = strtotime($end);
		$hours = round(($end_unix - $start_unix) / (60 * 60), 3);

		$stat = array(
			'game_id' => $this->id,
			'setup_id' => $this->_setup['id'],
			'move_count' => $count,
			'start_date' => $start,
			'end_date' => $end,
			'hour_count' => $hours,
		);

		$white_outcome = $black_outcome = 0;
		if ('Finished' == $this->state) {
			$white_outcome = 1;
			$black_outcome = -1;

			if ('red' == $this->_pharaoh->winner) {
				$white_outcome = -1;
				$black_outcome = 1;
			}
		}

		$white = array_merge($stat, array(
			'player_id' => $this->_players['white']['player_id'],
			'color' => 'white',
			'win' => $white_outcome,
		));
		call($white);
		$this->_mysql->insert(self::GAME_STATS_TABLE, $white);

		$black = array_merge($stat, array(
			'player_id' => $this->_players['black']['player_id'],
			'color' => 'black',
			'win' => $black_outcome,
		));
		call($black);
		$this->_mysql->insert(self::GAME_STATS_TABLE, $black);
	}


	/** protected function _log
	 *		Report messages to a file
	 *
	 * @param string message
	 * @action log messages to file
	 * @return void
	 */
	protected function _log($msg)
	{
		// log the error
		if (false && class_exists('Log')) {
			Log::write($msg, __CLASS__);
		}
	}


	/** protected function _diff
	 *		Compares two boards are returns the
	 *		indexes of any differences
	 *
	 * @param string board
	 * @param string board
	 * @return array of difference indexes
	 */
	protected function _diff($board1, $board2)
	{
		$diff = array( );
		for ($i = 0, $length = strlen($board1); $i < $length; ++$i) {
			if ($board1[$i] != $board2[$i]) {
				$diff[] = $i;
			}
		}
		call($diff);

		return $diff;
	}


	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function get_list
	 *		Returns a list array of all the games in the database
	 *		with games which need the users attention highlighted
	 *
	 *		NOTE: $player_id is required when not pulling all games
	 *		(when $all is false)
	 *
	 * @param int optional player's id
	 * @param bool optional pull all games (vs only given player's games)
	 * @return array game list (or bool false on failure)
	 */
	static public function get_list($player_id = 0, $all = true)
	{
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		if ( ! $all && ! $player_id) {
			throw new MyException(__METHOD__.': Player ID required when not pulling all games');
		}

		$WHERE = " WHERE state <> 'Waiting' ";
		if ( ! $all) {
			$WHERE .= "
					AND (G.white_id = {$player_id}
						OR G.black_id = {$player_id})
			";
		}

		$query = "
			SELECT G.*
				, MAX(GH.move_date) AS last_move
				, COUNT(GH.move_date) AS count
				, 0 AS my_turn
				, 0 AS in_game
				, W.username AS white
				, B.username AS black
				, S.name AS setup_name
			FROM ".self::GAME_TABLE." AS G
				LEFT JOIN ".self::GAME_HISTORY_TABLE." AS GH
					ON GH.game_id = G.game_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS W
					ON W.player_id = G.white_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS B
					ON B.player_id = G.black_id
				LEFT JOIN ".Setup::SETUP_TABLE." AS S
					ON S.setup_id = G.setup_id
			{$WHERE}
			GROUP BY G.game_id
			ORDER BY G.state ASC
				, last_move DESC
		";
		$list = $Mysql->fetch_array($query);

		if (0 != $player_id) {
			// run though the list and find games the user needs action on
			foreach ($list as & $game) {
				$game['turn'] = (0 == ($game['count'] % 2)) ? 'black' : 'white';

				$game['in_game'] = (int) (($player_id == $game['white_id']) || ($player_id == $game['black_id']));
				$game['my_turn'] = (int) ( ! empty($game['turn']) && ($player_id == $game[$game['turn'].'_id']));

				if (in_array($game['state'], array('Finished', 'Draw'))) {
					$game['my_turn'] = 0;
				}

				$game['my_color'] = ($player_id == $game['white_id']) ? 'white' : 'black';
				$game['opp_color'] = ($player_id == $game['white_id']) ? 'black' : 'white';

				$game['opponent'] = ($player_id == $game['white_id']) ? $game['black'] : $game['white'];
			}
			unset($game); // kill the reference
		}

		// run through the games, and set the current player to the winner if the game is finished
		// and fix the setup name if it was deleted
		foreach ($list as & $game) {
			if ('Finished' == $game['state']) {
				if ($game['winner_id']) {
					$game['turn'] = ($game['winner_id'] == $game['white_id']) ? 'white' : 'black';
				}
				else {
					$game['turn'] = 'draw';
				}
			}

			if ( ! $game['setup_name']) {
				$game['setup_name'] = '[DELETED]';
			}
		}
		unset($game); // kill the reference

		return $list;
	}


	/** static public function get_player_stats_list
	 *		Returns a list array of all game players
	 *		in the database with additional stats added
	 *
	 * @param void
	 * @return array game player list (or bool false on failure)
	 */
	static public function get_player_stats_list( )
	{
		$Mysql = Mysql::get_instance( );

		$players = GamePlayer::get_list(true);

		if ($players) {
			// add some more stats
			$query = "
				SELECT o.player_id
					, COUNT(ww.win) AS white_wins
					, COUNT(wl.win) AS white_losses
					, COUNT(wd.win) AS white_draws
					, COUNT(bw.win) AS black_wins
					, COUNT(bl.win) AS black_losses
					, COUNT(bd.win) AS black_draws
				FROM ".self::GAME_STATS_TABLE." AS o
					LEFT JOIN ".self::GAME_STATS_TABLE." AS ww
						ON (o.player_id = ww.player_id
							AND o.game_id = ww.game_id
							AND ww.win = 1
							AND ww.color = 'white')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS wl
						ON (o.player_id = wl.player_id
							AND o.game_id = wl.game_id
							AND wl.win = -1
							AND wl.color = 'white')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS wd
						ON (o.player_id = wd.player_id
							AND o.game_id = wd.game_id
							AND wd.win = 0
							AND wd.color = 'white')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS bw
						ON (o.player_id = bw.player_id
							AND o.game_id = bw.game_id
							AND bw.win = 1
							AND bw.color = 'black')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS bl
						ON (o.player_id = bl.player_id
							AND o.game_id = bl.game_id
							AND bl.win = -1
							AND bl.color = 'black')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS bd
						ON (o.player_id = bd.player_id
							AND o.game_id = bd.game_id
							AND bd.win = 0
							AND bd.color = 'black')
				GROUP BY o.player_id
			";
			$results = $Mysql->fetch_array($query);

			$stats = array( );
			foreach ($results as $stat) {
				$stats[$stat['player_id']] = $stat;
			}

			$empty = array(
				'white_wins' => 0,
				'white_losses' => 0,
				'white_draws' => 0,
				'black_wins' => 0,
				'black_losses' => 0,
				'black_draws' => 0,
			);
			foreach ($players as & $player) { // be careful with the reference
				if (isset($stats[$player['player_id']])) {
					$player = array_merge($player, $stats[$player['player_id']]);
				}
				else {
					$player = array_merge($player, $empty);
				}
			}
			unset($player); // kill the reference
		}

		return $players;
	}


	/** static public function get_setup_stats_list
	 *		Gets the list of all the setups with
	 *		additional stats included in the list
	 *
	 * @param void
	 * @return array setups
	 */
	static public function get_setup_stats_list( )
	{
		$Mysql = Mysql::get_instance( );

		$setups = Setup::get_list(true);

		if ($setups) {
			// add some more stats
			$query = "
				SELECT o.setup_id
					, COUNT(ww.win) AS white_wins
					, COUNT(bw.win) AS black_wins
					, COUNT(d.win) AS draws
				FROM ".self::GAME_STATS_TABLE." AS o
					LEFT JOIN ".self::GAME_STATS_TABLE." AS ww
						ON (o.setup_id = ww.setup_id
							AND o.game_id = ww.game_id
							AND ww.win = 1
							AND ww.color = 'white')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS bw
						ON (o.setup_id = bw.setup_id
							AND o.game_id = bw.game_id
							AND bw.win = 1
							AND bw.color = 'black')
					LEFT JOIN ".self::GAME_STATS_TABLE." AS d
						ON (o.setup_id = d.setup_id
							AND o.game_id = d.game_id
							AND d.win = 0
							AND d.color = 'white')
				GROUP BY o.player_id
			";
			$results = $Mysql->fetch_array($query);

			$stats = array( );
			foreach ($results as $stat) {
				$stats[$stat['setup_id']] = $stat;
			}

			$empty = array(
				'white_wins' => 0,
				'black_wins' => 0,
				'draws' => 0,
			);
			foreach ($setups as & $setup) { // be careful with the reference
				if (isset($stats[$setup['setup_id']])) {
					$setup = array_merge($setup, $stats[$setup['setup_id']]);
				}
				else {
					$setup = array_merge($setup, $empty);
				}
			}
			unset($setup); // kill the reference
		}

		return $setups;
	}


	/** static public function get_invites
	 *		Returns a list array of all the invites in the database
	 *		for the given player
	 *
	 * @param int player's id
	 * @return 2D array invite list
	 */
	static public function get_invites($player_id)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		$query = "
			SELECT G.*
				, DATE_ADD(NOW( ), INTERVAL -1 DAY) AS resend_limit
				, R.username AS invitor
				, E.username AS invitee
				, S.name AS setup
			FROM ".self::GAME_TABLE." AS G
				LEFT JOIN ".Setup::SETUP_TABLE." AS S
					ON S.setup_id = G.setup_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS R
					ON R.player_id = G.white_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS E
					ON E.player_id = G.black_id
			WHERE G.state = 'Waiting'
				AND (G.white_id = {$player_id}
					OR G.black_id = {$player_id}
					OR G.black_id IS NULL
					OR G.black_id = FALSE
				)
			ORDER BY G.create_date DESC
		";
		$list = $Mysql->fetch_array($query);
		call($list);

		$in_vites = $out_vites = $open_vites = array( );
		foreach ($list as $item) {
			$extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, unserialize($item['extra_info']));
			$white_color = (('random' == $extra_info['white_color']) ? 'Random' : (('white' == $extra_info['white_color']) ? 'Silver' : 'Red'));
			$black_color = (('random' == $extra_info['white_color']) ? 'Random' : (('white' == $extra_info['white_color']) ? 'Red' : 'Silver'));

			$hover = array( );
			if ( ! empty($item['extra_info'])) {
				$hover = unserialize($item['extra_info']);
				unset($hover['invite_setup']);
				unset($hover['white_color']);
			}

			$hover_text = array( );
			foreach ($hover as $key => $value) {
				if (is_bool($value)) {
					$value = ($value ? 'Yes' : 'No');
				}

				$hover_text[] = humanize($key).': '.$value;
			}
			$item['hover_text'] = implode(' | ', $hover_text);

			$item['board'] = 'setup_display';
			if ( ! empty($extra_info['invite_setup'])) {
				$item['board'] = expandFEN($extra_info['invite_setup']);
			}

			if ($player_id == $item['white_id']) {
				$item['color'] = $white_color;
				$out_vites[] = $item;
			}
			elseif ($player_id == $item['black_id']) {
				$item['color'] = $black_color;
				$in_vites[] = $item;
			}
			else {
				$item['color'] = $black_color;
				$open_vites[] = $item;
			}
		}

		return array($in_vites, $out_vites, $open_vites);
	}


	/** static public function get_invite_count
	 *		Returns a count array of all the invites in the database
	 *		for the given player
	 *
	 * @param int player's id
	 * @return 2D array invite count
	 */
	static public function get_invite_count($player_id)
	{
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
			WHERE black_id = {$player_id}
				AND state = 'Waiting'
		";
		$in_vites = (int) $Mysql->fetch_value($query);

		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
			WHERE white_id = {$player_id}
				AND state = 'Waiting'
		";
		$out_vites = (int) $Mysql->fetch_value($query);

		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
			WHERE white_id <> '{$player_id}'
				AND state = 'Waiting'
				AND (black_id IS NULL
					OR black_id = FALSE
				)
		";
		$open_vites = (int) $Mysql->fetch_value($query);

		return array($in_vites, $out_vites, $open_vites);
	}


	/** static public function get_count
	 *		Returns a count of all games in the database,
	 *		as well as the highest game id (the total number of games played)
	 *
	 * @param void
	 * @return array (int current game count, int total game count)
	 */
	static public function get_count( )
	{
		$Mysql = Mysql::get_instance( );

		// games in play
		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
			WHERE state = 'Playing'
		";
		$count = (int) $Mysql->fetch_value($query);

		// total games
		$query = "
			SELECT MAX(game_id)
			FROM ".self::GAME_TABLE."
		";
		$next = (int) $Mysql->fetch_value($query);

		return array($count, $next);
	}


	/** static public function check_turns
	 *		Returns a count of all games
	 *		in which it is the user's turn
	 *
	 * @param int player id
	 * @return int number of games with player action
	 */
	static public function check_turns($player_id)
	{
		$list = self::get_list($player_id, false);
		$turn_count = array_sum_field($list, 'my_turn');
		return $turn_count;
	}


	/** static public function get_my_count
	 *		Returns a count of all given player's games in the database,
	 *		as well as the games in which it is the player's turn
	 *
	 * @param int player id
	 * @return array (int player game count, int turn game count)
	 */
	static public function get_my_count($player_id)
	{
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		// games in play
		$query = "
			SELECT game_id
				, IF(white_id = {$player_id}, 'silver', 'red') AS color
			FROM ".self::GAME_TABLE."
			WHERE state = 'Playing'
				AND (white_id = '{$player_id}'
					OR black_id = '{$player_id}'
				)
		";
		$games = $Mysql->fetch_array($query);
		$mine = count($games);

		// games with turns
		$turn = 0;
		foreach ($games as $game) {
			$query = "
				SELECT COUNT(*)
				FROM ".self::GAME_HISTORY_TABLE."
				WHERE game_id = '{$game['game_id']}'
			";
			$count = (int) $Mysql->fetch_value($query);
			$odd = (bool) ($count % 2);

			if ((('silver' == $game['color']) && $odd)
				|| (('red' == $game['color']) && ! $odd)) {
				++$turn;
			}
		}

		return array($mine, $turn);
	}


	/** public function delete_inactive
	 *		Deletes the inactive games from the database
	 *
	 * @param int age in days (0 = disable)
	 * @action deletes the inactive games
	 * @return void
	 */
	static public function delete_inactive($age)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$age = (int) $age;

		if ( ! $age) {
			return;
		}

		// don't auto delete paused games or invites
		$query = "
			SELECT game_id
			FROM ".self::GAME_TABLE."
			WHERE state <> 'Waiting'
				AND modify_date < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
				AND create_date < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
				AND paused = 0
		";
		$game_ids = $Mysql->fetch_value_array($query);

		if ($game_ids) {
			self::delete($game_ids);
		}
	}


	/** public function delete_finished
	 *		Deletes the finished games from the database
	 *
	 * @param int age in days (0 = disable)
	 * @action deletes the finished games
	 * @return void
	 */
	static public function delete_finished($age)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$age = (int) $age;

		if ( ! $age) {
			return;
		}

		$query = "
			SELECT game_id
			FROM ".self::GAME_TABLE."
			WHERE state IN ('Finished', 'Draw')
				AND modify_date < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
		";
		$game_ids = $Mysql->fetch_value_array($query);

		if ($game_ids) {
			self::delete($game_ids);
		}
	}


	/** static public function delete
	 *		Deletes the given game and all related data
	 *
	 * @param mixed array or csv of game ids
	 * @action deletes the game and all related data from the database
	 * @return void
	 */
	static public function delete($ids)
	{
		$Mysql = Mysql::get_instance( );

		array_trim($ids, 'int');

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No game ids given');
		}

		foreach ($ids as $id) {
			try {
				self::write_game_file($id);
			}
			catch (MyException $e) { }
		}

		$tables = array(
			self::GAME_HISTORY_TABLE ,
			self::GAME_NUDGE_TABLE ,
			self::GAME_TABLE ,
		);

		$Mysql->multi_delete($tables, " WHERE game_id IN (".implode(',', $ids).") ");

		$query = "
			OPTIMIZE TABLE ".self::GAME_TABLE."
				, ".self::GAME_NUDGE_TABLE."
				, ".self::GAME_HISTORY_TABLE."
		";
		$Mysql->query($query);
	}


	/** static public function player_deleted
	 *		Deletes the games the given players are in
	 *
	 * @param mixed array or csv of player ids
	 * @action deletes the players games
	 * @return void
	 */
	static public function player_deleted($ids)
	{
		$Mysql = Mysql::get_instance( );

		array_trim($ids, 'int');

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No player ids given');
		}

		$query = "
			SELECT DISTINCT(game_id)
			FROM ".self::GAME_TABLE."
			WHERE white_id IN (".implode(',', $ids).")
				OR black_id IN (".implode(',', $ids).")
		";
		$game_ids = $Mysql->fetch_value_array($query);

		if ($game_ids) {
			self::delete($game_ids);
		}
	}


	/** static public function pause
	 *		Pauses the given games
	 *
	 * @param mixed array or csv of game ids
	 * @param bool optional pause game (false = unpause)
	 * @action pauses the games
	 * @return void
	 */
	static public function pause($ids, $pause = true)
	{
		$Mysql = Mysql::get_instance( );

		array_trim($ids, 'int');

		$pause = (int) (bool) $pause;

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No game ids given');
		}

		$Mysql->insert(self::GAME_TABLE, array('paused' => $pause), " WHERE game_id IN (".implode(',', $ids).") ");
	}


} // end of Game class


/*		schemas
// ===================================

Game table
----------------------
DROP TABLE IF EXISTS `ph_game`;
CREATE TABLE IF NOT EXISTS `ph_game` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `white_id` int(10) unsigned DEFAULT NULL,
  `black_id` int(10) unsigned DEFAULT NULL,
  `state` enum('Playing','Finished','Draw') COLLATE latin1_general_ci NOT NULL DEFAULT 'Playing',
  `extra_info` text COLLATE latin1_general_ci,
  `winner_id` int(10) unsigned DEFAULT NULL,
  `setup_id` int(10) unsigned NOT NULL,
  `paused` tinyint(1) NOT NULL DEFAULT '0',
  `create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`game_id`),
  KEY `state` (`state`),
  KEY `white_id` (`white_id`),
  KEY `black_id` (`black_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


History Table
----------------------
DROP TABLE IF EXISTS `ph_game_history`;
CREATE TABLE IF NOT EXISTS `ph_game_history` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `move` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `hits` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `board` varchar(87) COLLATE latin1_general_ci NOT NULL,
  `extra_info` text COLLATE latin1_general_ci NULL DEFAULT NULL,
  `move_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY `game_id` (`game_id`),
  KEY `move_date` (`move_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `ph_game_nudge`
--

DROP TABLE IF EXISTS `ph_game_nudge`;
CREATE TABLE IF NOT EXISTS `bs_game_nudge` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `nudged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


--
-- Table structure for table `ph_stats`
--

DROP TABLE IF EXISTS `ph_stats`;
CREATE TABLE IF NOT EXISTS `ph_stats` (
  `player_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `setup_id` int(10) unsigned NOT NULL,
  `color` enum('white','black') COLLATE latin1_general_ci NOT NULL,
  `win` tinyint(1) NOT NULL DEFAULT '0',
  `move_count` int(10) unsigned NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `hour_count` float(8,3) NOT NULL DEFAULT '0.000',
  UNIQUE KEY `player_id` (`player_id`,`game_id`,`setup_id`),
  KEY `move_count` (`move_count`),
  KEY `hour_count` (`hour_count`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;


*/

