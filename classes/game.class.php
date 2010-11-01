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

require_once INCLUDE_DIR.'func.array.php';

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


	/** const property INVITE_TABLE
	 *		Holds the invite table name
	 *
	 * @var string
	 */
	const INVITE_TABLE = T_INVITE;


	/** static protected property _EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *
	 * @var array
	 */
	static protected $_EXTRA_INFO_DEFAULTS = array(
			'custom_rules' => '',
		);


	/** public property id
	 *		Holds the game's id
	 *
	 * @var int
	 */
	public $id;


	/** public property state
	 *		Holds the game's current state
	 *		can be one of 'Playing', 'Finished', 'Draw'
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


	/** protected property _extra_info
	 *		Holds the extra game info
	 *
	 * @var array
	 */
	protected $_extra_info;


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
			throw new MyException(__METHOD__.': Trying to access non-existant property ('.$property.')', 2);
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
			throw new MyException(__METHOD__.': Trying to access non-existant property ('.$property.')', 3);
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
	 * @return int invite id
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
		$_P['invitor_id'] = (int) $_SESSION['player_id'];
		$_P['invitee_id'] = (int) $_P['opponent'];
		$_P['setup_id'] = (int) $_P['setup'];

		call($_P);

		$extra_info = array( );
#		$extra_info = array(
#			'custom_rules' => htmlentities($_P['custom_rules'], ENT_QUOTES, 'ISO-8859-1', false),
#		);
#		call($extra_info);

		$diff = array_compare($extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$extra_info = $diff[0];
		ksort($extra_info);

		call($extra_info);
		if ( ! empty($extra_info)) {
			$_P['extra_info'] = serialize($extra_info);
		}

		// check for random setup
		$query = "
			SELECT setup_id
			FROM ".Setup::SETUP_TABLE."
		";
		$setup_ids = $Mysql->fetch_value_array($query);
		call($setup_ids);

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

		// create the game
		$required = array(
			'invitor_id' ,
			'setup_id' ,
		);

		$key_list = array_merge($required, array(
			'invitee_id' ,
		));

		try {
			$_DATA = array_clean($_P, $key_list, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['invite_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo

		$insert_id = $Mysql->insert(self::INVITE_TABLE, $_DATA);

		if (empty($insert_id)) {
			throw new MyException(__METHOD__.': Invite could not be created');
		}

		// send the email
		if ($_DATA['invitee_id']) {
			Email::send('invite', $_DATA['invitee_id'], array('name' => $GLOBALS['_PLAYERS'][$_DATA['invitor_id']]));
		}

		return $insert_id;
	}


	/** static public function accept_invite
	 *		Creates the game from invite data
	 *
	 * @param int invite id
	 * @action creates a game
	 * @return int game id
	 */
	static public function accept_invite($invite_id)
	{
		call(__METHOD__);

		$invite_id = (int) $invite_id;

		$Mysql = Mysql::get_instance( );

		// basically all we do, is copy the invite to the game table
		// and randomly set the player order
		$query = "
			SELECT *
			FROM ".self::INVITE_TABLE."
			WHERE invite_id = '{$invite_id}'
		";
		$invite = $Mysql->fetch_assoc($query);

		$game = array(
			'extra_info' => $invite['extra_info'],
			'setup_id' => $invite['setup_id'],
			'create_date ' => 'NOW( )',  // note the trailing space in the field name, this is not a typo
		);

		if (mt_rand(0, 1)) {
			$game['white_id'] = $_SESSION['player_id'];
			$game['black_id'] = $invite['invitor_id'];
		}
		else {
			$game['black_id'] = $_SESSION['player_id'];
			$game['white_id'] = $invite['invitor_id'];
		}

		$insert_id = $Mysql->insert(self::GAME_TABLE, $game);

		if (empty($insert_id)) {
			throw new MyException(__METHOD__.': Game could not be created');
		}

		// add the first entry in the history table
		$query = "
			INSERT INTO ".self::GAME_HISTORY_TABLE."
				(game_id, board)
			VALUES
				('{$insert_id}', (
					SELECT board
					FROM ".Setup::SETUP_TABLE."
					WHERE setup_id = '{$game['setup_id']}'
				))
		";
		$Mysql->query($query);

		// add a used count to the setup table
		Setup::add_used($game['setup_id']);

		// delete the invite
		$Mysql->delete(self::INVITE_TABLE, " WHERE invite_id = '{$invite_id}' ");

		// send the email
		Email::send('start', $invite['invitor_id'], array('name' => $GLOBALS['_PLAYERS'][$_SESSION['player_id']]));

		return $insert_id;
	}


	/** static public function delete_invite
	 *		Deletes the given invite
	 *
	 * @param int invite id
	 * @action deletes the invite
	 * @return void
	 */
	static public function delete_invite($invite_id)
	{
		call(__METHOD__);

		$invite_id = (int) $invite_id;

		$Mysql = Mysql::get_instance( );

		$Mysql->delete(self::INVITE_TABLE, " WHERE invite_id = '{$invite_id}' ");
	}


	/** static public function has_invite
	 *		Tests if the given player has the given invite
	 *
	 * @param int invite id
	 * @param int player id
	 * @param bool optional player can accept invite
	 * @action deletes the invite
	 * @return void
	 */
	static public function has_invite($invite_id, $player_id, $accept = false)
	{
		call(__METHOD__);

		$invite_id = (int) $invite_id;
		$player_id = (int) $player_id;
		$accept = (bool) $accept;

		$Mysql = Mysql::get_instance( );

		$open = "";
		if ($accept) {
			$open = " OR invitee_id IS NULL
				OR invitee_id = FALSE ";
		}

		$query = "
			SELECT COUNT(*)
			FROM ".self::INVITE_TABLE."
			WHERE invite_id = '{$invite_id}'
				AND (invitor_id = '{$player_id}'
					OR invitee_id = '{$player_id}'
					{$open}
				)
		";
		$has_invites = (bool) $Mysql->fetch_value($query);

		return $has_invites;
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
			$hits = $this->_pharaoh->do_move($move);
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
				Email::send('draw', $this->_players['opponent']['player_id'], array('player' => $this->_players['player']['object']->username));
			}
			else {
				$this->state = 'Finished';
				$this->_players[$winner]['object']->add_win( );
				$this->_players[$this->_players[$winner]['opp_color']]['object']->add_loss( );

				// send the email
				$type = (($this->_players[$winner]['player_id'] == $_SESSION['player_id']) ? 'defeated' : 'won');
				Email::send($type, $this->_players['opponent']['player_id'], array('player' => $this->_players['player']['object']->username));
			}
		}
		else {
			// send the email
			Email::send('turn', $this->_players['opponent']['player_id'], array('name' => $this->_players['player']['object']->username));
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

		// we need to edit the board of the person resigning if it is not their turn
		// why?  i forget
		if ( ! $this->get_turn($player_id)) {
			$this->_boards['player']->board = str_replace('0', 'W', $this->_boards['player']->board);
		}

		$this->_players['opponent']['object']->add_win( );
		$this->_players['player']['object']->add_loss( );
		$this->state = 'Finished';
		Email::send('resigned', $this->_players['opponent']['player_id'], array('name' => $this->_players['player']['object']->username));
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


	/** public function get_board
	 *		Returns the current board
	 *
	 * @param bool optional return expanded FEN
	 * @param int optional history index
	 * @return string board FEN (or xFEN)
	 */
	public function get_board($index = 0, $expanded = false)
	{
		call(__METHOD__);

		$index = (int) $index;
		$expanded = (bool) $expanded;

		$board = $this->_history[$index]['board'];

		if ($expanded) {
			return $this->expandFEN($board);
		}

		return $board;
	}


	/** public function get_history
	 *		Returns the game move history
	 *
	 * @param void
	 * @return array game history
	 */
	public function get_history( )
	{
		call(__METHOD__);

		$history = array_reverse($this->_history);
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


	/** public function get_move
	 *		Returns the data for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or string previous turn
	 */
	public function get_move($index = 0, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($json);

		$index = (int) $index;
		$json = (bool) $json;

		$turn = $this->_history[$index];

		if ( ! $turn['move']) {
			if ($json) {
				return 'false';
			}

			return false;
		}

		$move = array( );

		$move[0][0] = Pharaoh::target_to_index(substr($turn['move'], 0, 2));

		if ('-' == $turn['move'][2]) {
			$move[0][1] = $move[0][0];
			$move[0][2] = $turn['move'][3];
		}
		else {
			$move[0][1] = Pharaoh::target_to_index(substr($turn['move'], 3, 2));
			$move[0][2] = (int) (':' == $turn['move'][2]);
		}

		$move[1] = array_trim($turn['hits'], 'int');

		if ($json) {
			return json_encode($move);
		}

		$old = $move;
		$move = array( );

		$move['move']['from'] = $old[0][0];
		$move['move']['to'] = $old[0][1];
		$move['move']['extra'] = $old[0][2];

		$move['hits'] = $old[1];

		return $move;
	}


	/** public function get_laser_path
	 *		Returns the laser path for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or JSON string laser path
	 */
	public function get_laser_path($index = 0, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($json);

		$index = (int) $index;
		$json = (bool) $json;

		$count = count($this->_history);
		call($this->_history[$index]);

		if ((1 == $count) || ($index >= $count)) {
			if ($json) {
				return '[]';
			}

			return false;
		}

		$same = 'silver';
		$oppo = 'red';
		if (0 == ($count % 2)) {
			$same = 'red';
			$oppo = 'silver';
		}

		$color = ((0 == ($count - $index)) ? $same : $oppo);

		// here we need to do the move, store the board as is
		// and then fire the laser.
		// the reason being: if we just fire the laser at the previous board,
		// if the piece hit was rotated into the beam, it will not display correctly
		// and if we try and fire the laser at the current board, it will pass through
		// any hit piece because it's no longer there (unless it's a stacked obelisk, of course)

		// create a dummy pharaoh class and set the board as it was prior to the laser (+1)
		$PH = new Pharaoh( );
		$PH->set_board(self::expandFEN($this->_history[$index + 1]['board']));

		// do the move, but do not fire the laser, and store the board
		$pre_board = $PH->do_move($this->_history[$index]['move'], false);

		// now fire the laser at that board
		$laser = $this->_pharaoh->fire_laser($color, $pre_board);

		if ($json) {
			return json_encode($laser);
		}

		return $laser;
	}


	/** public function get_hit_data
	 *		Returns the turn data for the given move index
	 *
	 * @param int optional move history index
	 * @param bool optional return as JSON string
	 * @return array or string previous turn
	 */
	public function get_hit_data($index = 0, $json = false)
	{
		call(__METHOD__);
		call($index);
		call($json);

		$index = (int) $index;
		$json = (bool) $json;

		$move_data = array( );

		if ($this->_history[$index]['hits']) {
			// we need to grab the previous board here, and perform the move
			// without firing the laser so we get the proper orientation
			// of the pieces as they were hit in case any pieces were rotated
			// into the beam

			// create a dummy pharaoh class and set the board as it was prior to the laser (+1)
			$PH = new Pharaoh( );
			$PH->set_board(self::expandFEN($this->_history[$index + 1]['board']));

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

		$nudger = $this->_players['player']['object']->username;

		if ($this->test_nudge( )) {
			Email::send('nudge', $this->_players['opponent']['player_id'], array('id' => $this->id, 'name' => $this->name, 'player' => $nudger));
			$this->_mysql->delete(self::GAME_NUDGE_TABLE, " WHERE game_id = '{$this->id}' ");
			$this->_mysql->insert(self::GAME_NUDGE_TABLE, array('game_id' => $this->id, 'player_id' => $this->_players['opponent']['player_id']));
			return true;
		}

		return false;
	}


	/** public function test_nudge
	 *		Tests if the current player can be nudged or not
	 *
	 * @param void
	 * @return bool player can be nudged
	 */
	public function test_nudge( )
	{
		call(__METHOD__);

		$player_id = (int) $this->_players['opponent']['player_id'];

		if ($this->is_turn( ) || ! in_array($this->state, array('Finished', 'Draw')) || $this->paused) {
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

		$count = count($this->_history);
		$winner = (0 == ($count % 2)) ? 'white' : 'black';

		if ($player_id == $this->_players[$winner]['player_id']) {
			return array('You Won !', 'won');
		}
		else {
			return array($this->_players[$winner]['object']->username.' Won', 'lost');
		}
	}


	/** public function write_game_file
	 *		TODO
	 *
	 * @param void
	 * @action void
	 * @return bool true
	 */
	public function write_game_file( )
	{
		// TODO: build a logging system to log game data
		return true;
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

		// set the properties
		$this->state = $result['state'];
		$this->paused = (bool) $result['paused'];
		$this->create_date = strtotime($result['create_date']);
		$this->modify_date = strtotime($result['modify_date']);

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
			ORDER BY move_date DESC
		";
		$result = $this->_mysql->fetch_array($query);
		call($result);

		if ($result) {
			$this->_history = $result;
			$this->turn = ((0 == (count($this->_history) % 2)) ? 'black' : 'white');
			$this->last_move = strtotime($result[0]['move_date']);

			try {
				$this->_pharaoh = new Pharaoh( );
				$this->_pharaoh->set_board($this->expandFEN($this->_history[0]['board']));
			}
			catch (MyException $e) {
				throw $e;
			}
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
Log::write('SAVE ATTEMPT', 'save', true);

		// make sure we don't have a MySQL error here, it may be causing the issues
		$run_once = false;
		do {
			if ($run_once) {
				// pause for 3 seconds, then try again
				sleep(3);
			}

			// update the game data
			$query = "
				SELECT state
					, modify_date
				FROM ".self::GAME_TABLE."
				WHERE game_id = '{$this->id}'
			";
			$game = $this->_mysql->fetch_assoc($query);
			call($game);

			// make sure we don't have a MySQL error here, it may be causing the issues
			$error = $this->_mysql->error;
			$errno = preg_replace('/(\\d+)/', '$1', $error);

			$run_once = true;
		}
		while (2006 == $errno || 2013 == $errno);

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
		}

		if ($update_game) {
Log::write('UPDATE GAME', 'save', true);
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

		$new_board = $this->packFEN($this->_pharaoh->get_board( ));
		call($new_board);
		list($new_move, $new_hits) = $this->_pharaoh->get_move( );
		call($new_move);
		call($new_hits);

		if (is_array($new_hits)) {
			$new_hits = implode(',', $new_hits);
		}

		if ($new_board != $board) {
Log::write(var_export($this, true), 'save', true);
			call('UPDATED BOARD');
			$update_modified = true;
			$this->_mysql->insert(self::GAME_HISTORY_TABLE, array('board' => $new_board, 'move' => $new_move, 'hits' => $new_hits, 'game_id' => $this->id));
		}

		// update the game modified date
		if ($update_modified) {
			$this->_mysql->insert(self::GAME_TABLE, array('modify_date' => NULL), " WHERE game_id = '{$this->id}' ");
		}
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

		$WHERE = " WHERE 1 = 1 ";
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
				, last_move ASC
		";
		$list = $Mysql->fetch_array($query);

		if (0 != $player_id) {
			// run though the list and find games the user needs action on
			foreach ($list as $key => $game) {
				$game['turn'] = (0 == ($game['count'] % 2)) ? 'black' : 'white';

				$game['in_game'] = (int) (($player_id == $game['white_id']) || ($player_id == $game['black_id']));
				$game['my_turn'] = (int) ( ! empty($game['turn']) && ($player_id == $game[$game['turn'].'_id']));

				if (in_array($game['state'], array('Finished', 'Draw'))) {
					$game['my_turn'] = 0;
				}

				$game['my_color'] = ($player_id == $game['white_id']) ? 'white' : 'black';
				$game['opp_color'] = ($player_id == $game['white_id']) ? 'black' : 'white';

				$game['opponent'] = ($player_id == $game['white_id']) ? $game['black'] : $game['white'];

				$list[$key] = $game;
			}
		}

		return $list;
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
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		$query = "
			SELECT I.*
				, R.username AS invitor
				, E.username AS invitee
				, S.name AS setup
			FROM ".self::INVITE_TABLE." AS I
				LEFT JOIN ".Setup::SETUP_TABLE." AS S
					ON S.setup_id = I.setup_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS R
					ON R.player_id = I.invitor_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS E
					ON E.player_id = I.invitee_id
			WHERE I.invitor_id = {$player_id}
				OR I.invitee_id = {$player_id}
				OR I.invitee_id IS NULL
				OR I.invitee_id = FALSE
			ORDER BY invite_date DESC
		";
		$list = $Mysql->fetch_array($query);

		$in_vites = $out_vites = $open_vites = array( );
		foreach ($list as $item) {
			if ($player_id == $item['invitor_id']) {
				$out_vites[] = $item;
			}
			elseif ($player_id == $item['invitee_id']) {
				$in_vites[] = $item;
			}
			else {
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
			FROM ".self::INVITE_TABLE."
			WHERE invitee_id = {$player_id}
		";
		$in_vites = (int) $Mysql->fetch_value($query);

		$query = "
			SELECT COUNT(*)
			FROM ".self::INVITE_TABLE."
			WHERE invitor_id = {$player_id}
		";
		$out_vites = (int) $Mysql->fetch_value($query);

		$query = "
			SELECT COUNT(*)
			FROM ".self::INVITE_TABLE."
			WHERE invitor_id <> '{$player_id}'
				AND (invitee_id IS NULL
					OR invitee_id = FALSE
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
			WHERE state NOT IN ('Finished', 'Draw')
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
	 *		TODO
	 *
	 * @param void
	 * @action deletes the inactive games
	 * @return void
	 */
	static public function delete_inactive( )
	{
		// TODO: delete the inactive games
	}


	/** public function delete_finished
	 *		TODO
	 *
	 * @param void
	 * @action deletes the finished games
	 * @return void
	 */
	static public function delete_finished( )
	{
		// TODO: delete the finished games
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
			self::write_game_file($id);
		}

		$tables = array(
			self::GAME_HISTORY_TABLE ,
			self::GAME_TABLE ,
		);

		$Mysql->multi_delete($tables, " WHERE game_id IN (".implode(',', $ids).") ");

		$query = "
			OPTIMIZE TABLE ".self::GAME_TABLE."
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

		self::delete($game_ids);
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


	static public function expandFEN($FEN)
	{
		$FEN = preg_replace('/\s+/', '', $FEN); // remove spaces

		$FEN = preg_replace('/([1-9]0?)/e', "str_repeat('0', \\1)", $FEN); // unpack the 0s
		$xFEN = str_replace('/', '', $FEN); // remove the row separators

		return $xFEN;
	}


	static public function packFEN($xFEN, $row_length = 10)
	{
		$xFEN = preg_replace('/\s+/', '', $xFEN); // remove spaces

		$xFEN = trim(chunk_split($xFEN, $row_length, '/'), '/'); // add the row separaters
		$FEN = preg_replace('/(0+)/e', "strlen('\\1')", $xFEN); // pack the 0s

		return $FEN;
	}


} // end of Game class


/*		schemas
// ===================================

Game table
----------------------
CREATE TABLE IF NOT EXISTS `bs_game` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `white_id` int(10) unsigned DEFAULT NULL,
  `black_id` int(10) unsigned DEFAULT NULL,
  `state` enum('Playing', 'Finished', 'Draw') NOT NULL DEFAULT 'Playing',
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
CREATE TABLE IF NOT EXISTS `bs_game_history` (
  `game_id` int(10) unsigned NOT NULL DEFAULT 0,
  `move` VARCHAR( 255 ) NULL DEFAULT NULL,
  `hits` VARCHAR( 255 ) NULL DEFAULT NULL,
  `board` varchar(87) NOT NULL,
  `move_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY `game_id` (`game_id`),
  KEY `move_date` (`move_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


Invite Table
----------------------
CREATE TABLE IF NOT EXISTS `ph_invite` (
  `invite_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invitor_id` int(10) unsigned NOT NULL,
  `invitee_id` int(10) unsigned NOT NULL DEFAULT '0',
  `setup_id` int(10) unsigned NOT NULL,
  `extra_info` text COLLATE latin1_general_ci,
  `invite_date` datetime NOT NULL,

  PRIMARY KEY (`invite_id`),
  KEY `invitor_id` (`invitor_id`),
  KEY `invitee_id` (`invitee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_nudge`
--

DROP TABLE IF EXISTS `bs_game_nudge`;
CREATE TABLE IF NOT EXISTS `bs_game_nudge` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `nudged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

*/

