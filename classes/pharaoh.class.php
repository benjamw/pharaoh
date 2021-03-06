<?php
/*
+---------------------------------------------------------------------------
|
|   pharaoh.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to play the game of Pharaoh (Khet), it cares not about
|	database structure or the goings on of the website, only about Pharaoh
|
+---------------------------------------------------------------------------
|
|   > Pharaoh game module
|   > Date started: 2009-12-22
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// set some constants for laser path directions
// directions are stored as values to add to
// current board index to get next board index
// when travelling in that direction
define('I_RIGHT', 1);
define('I_LEFT', -1);
define('I_UP', -10);
define('I_DOWN', 10);

class Pharaoh {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public property EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *		Values:
	 *		- move_sphynx (laser) - boolean default false
	 *		- battle_front_only (sphynx) - boolean default true - hit sphynx in front only (n/a for Khet 1.0)
	 *		- battle_hit_self (sphynx) - boolean default false - hit your own sphynx (from side only) (n/a for Khet 1.0)
	 *
	 * @var array
	 */
	static public $EXTRA_INFO_DEFAULTS = array(
			'move_sphynx' => false,
			'battle_front_only' => true,
			'battle_hit_self' => false,
		);


	/** public property players
	 *		Holds our player's data
	 *		format: (indexed by player_id then associative)
	 *		array(
	 *			player_id  => array('player_id', 'color', 'turn', 'extra_info' => array( ... )) ,
	 *			player_id  => array('player_id', 'color', 'turn', 'extra_info' => array( ... )) ,
	 *			'silver'   => reference to $this->players[silver_player_id] ,
	 *			'red'      => reference to $this->players[red_player_id] ,
	 *			'player'   => reference to $this->players[player_id] ,
	 *			'opponent' => reference to $this->players[opponent_id] ,
	 *		)
	 *
	 *		extra_info is an array that holds information about the current player state
	 *
	 * @var array of player data
	 * @info not used yet
	 */
	public $players;


	/** public property current_player
	 *		The current player's id
	 *
	 * @var int
	 * @info not used yet
	 */
	public $current_player;


	/** public property winner
	 *		Holds the winner
	 *		'silver' or 'red' (or 'draw')
	 *
	 * @var string
	 */
	public $winner;


	/** protected property _board
	 *		Holds the game board
	 *
	 * @var string
	 */
	protected $_board;


	/** public property has_sphynx
	 *		True if there is a sphynx on the board
	 *
	 * @var bool board has sphynx
	 */
	public $has_sphynx;


	/** protected property _move
	 *		Holds the last move
	 *
	 * @var string
	 */
	protected $_move;


	/** protected property _laser_path
	 *		Holds the path the laser took
	 *		around the board
	 *		array(
	 *			array([board index], [incoming direction]),
	 *			array([board index], [incoming direction]),
	 *			...
	 *		)
	 *
	 *		Path is not necessarily continuous
	 *		... but most likely
	 *
	 * @var array
	 */
	protected $_laser_path;


	/** protected property _hits
	 *		Holds the indexes of pieces hit
	 *
	 * @var array of int board indexes
	 */
	protected $_hits;


	/** protected property laser_hit
	 *		Holds the laser hit flag
	 *
	 * @var bool did we hit the laser?
	 */
	protected $laser_hit = false;


	/** protected property _extra_info
	 *		Holds the extra info for the game
	 *
	 * @see $EXTRA_INFO_DEFAULTS
	 * @var array
	 */
	protected $_extra_info;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param void
	 * @action instantiates object
	 * @return void
	 */
	public function __construct( )
	{
		call(__METHOD__);
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
		switch ($property) {
			case 'board' :
				try {
					$this->set_board($value);
				}
				catch (MyException $e) {
					throw $e;
				}
				return;
				break;

			default :
				// do nothing
				break;
		}

		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		$this->$property = $value;
	}


	/** public function __toString
	 *		Returns the ascii version of the board
	 *		when asked to output the object
	 *
	 * @param void
	 * @return string ascii version of the board
	 */
	public function __toString( )
	{
		return $this->_get_board_ascii( );
	}


	/** public function set_extra_info
	 *		Sets the extra info for the game
	 *
	 * @param array extra game info
	 * @return void
	 */
	public function set_extra_info($extra_info)
	{
		call(__METHOD__);
		$this->_extra_info = self::extra_info($extra_info);
	}


	/** public function get_extra_info
	 *		Returns the extra info for the game
	 *
	 * @param void
	 * @return array extra game info
	 */
	public function get_extra_info( )
	{
		call(__METHOD__);

		$diff = array_compare($this->_extra_info, self::$EXTRA_INFO_DEFAULTS);
		$extra_info = $diff[0];
		ksort($extra_info);

		return $extra_info;
	}


	/** public function do_move
	 *		Performs the given move
	 *		and then calls the laser firing method (if needed)
	 *		Move syntax:
	 *			-move: from:to e.g.- B4:B5
	 *				- if splitting an obelisk tower, replace : with .
	 *				- e.g.- B4.B5
	 *			-rotate: square-direction (0 = CCW, 1 = CW)) e.g.- B4-0
	 *
	 * @param string move
	 * @param bool whether or not to fire the laser after the move
	 * @return array pieces hit if laser fired or string board after move
	 */
	public function do_move($move, $fire_laser = true)
	{
		call(__METHOD__);
		call($move);

		$fire_laser = (bool) $fire_laser;
		call($fire_laser);

		$index = self::target_to_index(substr($move, 0, 2));

		// grab the color of the current piece
		$piece = $this->_board[$index];
		$color = self::get_piece_color($piece);

		// check for move or rotate
		try {
			if ('-' == $move[2]) { // rotate
				$this->_rotate_piece($index, $move[3]);
			}
			else { // move
				$this->_move_piece($index, self::target_to_index(substr($move, 3, 2)), (':' == $move[2]));
			}
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->_move = $move;

		// if we're not firing the laser, return the current board
		if ( ! $fire_laser) {
			call('NOT FIRING LASER');
			call($this->_board);
			return $this->_board;
		}

		$hits = $this->_fire_laser($color);
		$this->_hits = $hits;

		foreach ($hits as $hit) {
			// check if we hit a pharaoh
			if ('p' == $this->_board[$hit]) {
				if (in_array($this->winner, array('red', 'draw'))) {
					$this->winner = 'draw';
				}
				else {
					$this->winner = 'silver';
				}
			}
			elseif ('P' == $this->_board[$hit]) {
				if (in_array($this->winner, array('silver', 'draw'))) {
					$this->winner = 'draw';
				}
				else {
					$this->winner = 'red';
				}
			}

			// remove the piece
			// or remove one of the stacked obelisks
			if ('W' == $this->_board[$hit]) {
				$this->_board[$hit] = 'V';
			}
			elseif ('w' == $this->_board[$hit]) {
				$this->_board[$hit] = 'v';
			}
			else {
				$this->_board[$hit] = '0';
			}
		}
		call($this->_get_board_ascii( ));

		return $hits;
	}


	/** protected function _fire_laser
	 *		FIRE ZEE MISSILES !!!
	 *		But I am le tired
	 *
	 *		Fires the laser of the given color
	 *
	 * @param string color (red or silver)
	 * @action creates $this->_laser_path array
	 * @return array of squares hit (empty array if none)
	 */
	protected function _fire_laser($color)
	{
		try {
			$return = self::fire_laser($color, $this->_board, $this->_extra_info);
			$this->_laser_path = $return['laser_path'];
			$this->laser_hit = $return['laser_hit'];
			return $return['hits'];
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** static public function fire_laser
	 *		FIRE ZEE MISSILES !!!
	 *		But I am le tired
	 *
	 *		Fires the laser of the given color
	 *
	 * @param string color (red or silver)
	 * @param string board (expanded FEN)
	 * @param array [optional] extra info
	 * @return array (laser path array, and squares hit (empty array if none) array)
	 */
	static public function fire_laser($color, $board, $extra_info = false)
	{
		call(__METHOD__);

		// search for the sphynx in the board
		$has_sphynx = Setup::has_sphynx($board);

		$color = strtolower($color);
		$board = expandFEN($board);
		$extra_info = self::extra_info($extra_info);
		call($color);
		call($board);
		call($extra_info);

		if ( ! in_array($color, array('silver', 'red'))) {
			throw new MyException(__METHOD__.': Trying to fire laser for unknown color: '.$color);
		}

		$reflections = array(
			// pyramid
			'A' => array(I_LEFT  => I_UP,    I_DOWN => I_RIGHT), //  .\
			'B' => array(I_LEFT  => I_DOWN,  I_UP   => I_RIGHT), //  `/
			'C' => array(I_RIGHT => I_DOWN,  I_UP   => I_LEFT),  //  \`
			'D' => array(I_RIGHT => I_UP,    I_DOWN => I_LEFT),  //  /.

			// djed
			'X' => array( //  \
				I_RIGHT => I_DOWN,
				I_LEFT  => I_UP,
				I_DOWN  => I_RIGHT,
				I_UP    => I_LEFT,
			),
			'Y' => array( //  /
				I_RIGHT => I_UP,
				I_LEFT  => I_DOWN,
				I_DOWN  => I_LEFT,
				I_UP    => I_RIGHT,
			),

			// eye of horus
			'H' => array( //  \
				I_RIGHT => array(I_RIGHT, I_DOWN),
				I_LEFT  => array(I_LEFT,  I_UP),
				I_DOWN  => array(I_RIGHT, I_DOWN),
				I_UP    => array(I_LEFT,  I_UP),
			),
			'I' => array( //  /
				I_RIGHT => array(I_RIGHT, I_UP),
				I_LEFT  => array(I_LEFT,  I_DOWN),
				I_DOWN  => array(I_LEFT,  I_DOWN),
				I_UP    => array(I_RIGHT, I_UP),
			),
		);

		// fire the laser

		if ( ! $has_sphynx) {
			if ('silver' == $color) {
				$laser_path = array(array(array(79, I_UP)));
			}
			else { // red
				$laser_path = array(array(array(0, I_DOWN)));
			}
		}
		else {
			list($shoot_sphynx_idx, $shoot_sphynx_dir) = self::find_sphynx($board, ('silver' === $color));
			list($hit_sphynx_idx, $hit_sphynx_dir) = self::find_sphynx($board, ('silver' !== $color));
			$laser_path = array(array(array($shoot_sphynx_idx + $shoot_sphynx_dir, $shoot_sphynx_dir)));
		}

		// because we can now possibly move the laser around and rotate it
		// make sure we don't hit a wall right out of the gate
		// check if we hit a wall
		list($current, $dir) = $laser_path[0][0];
		$long_wall = (0 > $current) || (80 <= $current);
		$short_wall = (1 == abs($dir)) && (floor($current / 10) !== floor(($current - $dir) / 10));
		if ($long_wall || $short_wall) {
			// we hit the wall...  just stop
			$laser_path[0][0][0] = false;
			return array('laser_path' => $laser_path, 'hits' => array( ), 'laser_hit' => false);
		}

		// also check and make sure that the sphynx was not hit right out of the gate
		$hit_silver = $hit_red = false;
		$var_name = 'hit_'.(('silver' == $color) ? 'silver' : 'red');
		if ($has_sphynx && ($hit_sphynx_idx == $current)) {
			if ( ! $extra_info['battle_front_only']) {
				${$var_name} = true;
			}
			elseif ($hit_sphynx_dir == -$dir) {
				${$var_name} = true;
			}
		}

		if ($hit_red || $hit_silver) {
			// we hit the sphynx...  just stop
			$laser_path[] = array(array(true, $dir));
			return array('laser_path' => $laser_path, 'hits' => array( ), 'laser_hit' => true);
		}

		$i = 0; // infinite loop protection
		$paths = $laser_path[0];
		$used = array( );
		$next = array( );
		$hits = array( );
		$laser_hit = false;
		while ($i < 999) { // no ad infinitum here
			$split = 0;
			$continue = false;

			foreach ($paths as $key => $node) {
				if ((false === $node[0]) || (true === $node[0])) {
					unset($node[2]);
					$next[$key] = $node; // propagate the individual path indexes
					continue;
				}

				// let the loop know we still have valid nodes
				$continue = true;

				// current is the current board index
				// dir is the direction that the laser was heading
				// when it entered the current index
				list($current, $dir) = $node;

				// check the current location for a piece
				if ('0' !== ($piece = $board[$current])) {
					// check for hit or reflection
					if ( ! isset($reflections[strtoupper($piece)][$dir])) {
						// dont track the hit for the following situations:

						// is an anubis and hit the front
						$front_anubis = (preg_match('/[LMNO]/i', $piece) && ($dir == -self::get_anubis_dir($piece)));

						// hit the sphynx (laser hits get tracked elsewhere)
						$hit_sphynx = ($has_sphynx && (($current == $hit_sphynx_idx) || ($current == $shoot_sphynx_idx)));

						// if any of those hit, don't track the hit
						if ( ! ($front_anubis || $hit_sphynx)) {
							$hits[] = $current;
						}

						// but still change the path data to recognize the path stopped

						// we store dir here because it keeps the output format consistent
						// we don't really care at this point because we just hit a piece
						// ... the laser isn't going any further
						$next[$key] = array(true, $dir); // stop this path
						continue;
					}

					$dir = $reflections[strtoupper($piece)][$dir];
				}

				// this is where we split off in two directions through the beam splitter
				$do_split = false;
				if (is_array($dir)) {
					// add a new entry in the paths for the reflection
					// and change dir to be the pass-through beam

					// if we've already split a beam once,
					// we'll need to add a few more to the index
					// (it is possible to hit a single splitter from two sides,
					// as well as hit both splitters from two sides, so...)

					// also note: if there are no beam splitters, there is no way
					// of doubling back or going over the same path again, ever

					// before we add to $next, make sure we haven't been here, or hit any walls
					$do_split = true;
					$split_dir = $dir[0];
					$split_current = $current + $split_dir;

					// make sure we haven't been here before
					if (in_array(array($split_current, $split_dir), $used, true)) {
						// don't even create a new path
						$do_split = false;
					}
					else {
						// check if we hit the laser
						$hit_silver = $hit_red = false;
						if ( ! $has_sphynx) {
							$hit_red = (-10 == $split_current) && ('silver' == $color);
							$hit_silver = (89 == $split_current) && ('red' == $color);
						}
						else {
							// find the laser
							$var_name = 'hit_'.(('silver' == $color) ? 'silver' : 'red');

							if ($hit_sphynx_idx == $split_current) {
								if ( ! $extra_info['battle_front_only']) {
									${$var_name} = true;
								}
								elseif ($hit_sphynx_dir == -$split_dir) {
									${$var_name} = true;
								}
							}
						}

						if ($hit_red || $hit_silver) {
							$laser_hit = true;
						}

						// check if we hit a wall
						$long_wall = (0 > $split_current) || (80 <= $split_current);
						$short_wall = (1 == abs($split_dir)) && (floor($split_current / 10) !== floor(($split_current - $split_dir) / 10));
						if ($long_wall || $short_wall) {
							// set split_current to false, so we know we hit a wall,
							// but keep going, we need to store the dir so we can show any reflection properly
							// and we need to add the new path index below

							// we store dir here because we need to know in which direction the laser left the node
							// for edge/corner piece hits that send the laser through the wall
							$split_current = false;
						}

						$next[count($paths) + $split] = array($split_current, $split_dir);

						if (false !== $split_current) {
							$used[] = array($split_current, $split_dir);
						}

						$split += 1;
					}

					// now that the split is done, go back and keep processing the new path
					$dir = $dir[1];
				}

				// increment $current and run a few tests
				// so we don't shoot through walls
				// or loop back on ourselves forever
				$current += $dir;

				// make sure we haven't been here before
				if (in_array(array($current, $dir), $used, true)) {
					// we store dir here because it keeps the output format consistent
					// we don't really care at this point because we've been here before
					// and know what's going to happen
					$next[$key] = array(false, $dir); // stop this path
					continue;
				}

				// check if we hit the laser
				$hit_silver = $hit_red = false;
				if ( ! $has_sphynx) {
					$hit_red = (-10 == $current) && ('silver' == $color);
					$hit_silver = (89 == $current) && ('red' == $color);
				}
				else {
					$var_name = 'hit_'.(('silver' == $color) ? 'silver' : 'red');

					if ($hit_sphynx_idx == $current) {
						if ( ! $extra_info['battle_front_only']) {
							${$var_name} = true;
						}
						elseif ($hit_sphynx_dir == -$dir) {
							${$var_name} = true;
						}
					}
				}

				if ($hit_red || $hit_silver) {
					$laser_hit = true;
				}

				// check if we hit a wall
				$long_wall = (0 > $current) || (80 <= $current);
				$short_wall = (1 == abs($dir)) && (floor($current / 10) !== floor(($current - $dir) / 10));
				if ($long_wall || $short_wall) {
					// set current to false, so we know we hit a wall,
					// but keep going, we need to store the dir so we can show any reflection properly
					// and we need to add the new path index below

					// we store dir here because we need to know which direction the laser left the node in
					// for corner piece hits that send the laser through the wall
					$current = false;
				}

				$next[$key] = array($current, $dir);
				if ($do_split) {
					// add the new path index here so we know which path was the original path
					// and where are the splits came from
					$next[$key][2] = count($paths) + $split - 1;
				}

				if (false !== $current) {
					$used[] = array($current, $dir);
				}
			} // end foreach $current

			// if we have no valid nodes left
			// break the loop
			if ( ! $continue) {
				break;
			}

			// add to our laser path
			// and pass along to the next round
			$laser_path[] = $paths = $next;

			++$i; // keep those pesky infinite loops at bay
		} // end while
		call(self::get_laser_ascii($board, $laser_path));
		call($hits);

		// straighten up the laser path
		array_walk($laser_path, create_function('& $val', 'ksort($val);'));

		return compact('laser_path', 'hits', 'laser_hit');
	}


	/** static public function find_sphynx
	 *		Finds the sphynx on the board and
	 *		returns both the index and orientation
	 *
	 * @param string expanded board
	 * @param bool [optional] silver
	 * @return array sphynx index, orientation
	 */
	static public function find_sphynx($board, $silver = true)
	{
		$sphynxes = ($silver) ? array('E','F','J','K') : array('e','f','j','k');

		// find the sphynx
		foreach ($sphynxes as $sphynx) {
			if (false !== ($idx = strpos($board, $sphynx))) {
				break;
			}
		}

		if ( ! isset($idx)) {
			throw new MyException(__METHOD__.': Sphynx not found on board');
		}

		// get the orientation
		switch (strtoupper($sphynx)) {
			case 'E' : $dir = I_UP; break;
			case 'F' : $dir = I_RIGHT; break;
			case 'J' : $dir = I_DOWN; break;
			case 'K' : $dir = I_LEFT; break;
		}

		return array($idx, $dir);
	}


	/** static public function get_anubis_dir
	 *		Gets the direction the anubis is facing
	 *
	 * @param string piece
	 * @return int anubis direction (or false otherwise)
	 */
	static public function get_anubis_dir($piece)
	{
		switch (strtoupper($piece)) {
			case 'L' : return I_UP; break;
			case 'M' : return I_RIGHT; break;
			case 'N' : return I_DOWN; break;
			case 'O' : return I_LEFT; break;
		}

		return false;
	}


	/** public function get_laser_path
	 *		Returns the laser path array
	 *
	 * @param void
	 * @return array laser path
	 */
	public function get_laser_path( )
	{
		return $this->_laser_path;
	}


	/** public function set_board
	 *		Tests and sets the board
	 *
	 * @param string expanded FEN of board
	 * @return void
	 */
	public function set_board($xFEN)
	{
		call(__METHOD__);
		call($xFEN);

		// test the board and make sure all is well
		if (80 != strlen($xFEN)) {
			throw new MyException(__METHOD__.': Board is not the right size');
		}

		$this->_board = $xFEN;

		$this->has_sphynx = Setup::has_sphynx($this->_board);

		call($this->_get_board_ascii( ));
	}


	/** public function get_board
	 *		Returns the board
	 *
	 * @param void
	 * @return string expanded FEN of board
	 */
	public function get_board( )
	{
		call(__METHOD__);

		return $this->_board;
	}


	/** public function get_move
	 *		Returns the latest move and hits
	 *
	 * @param void
	 * @return array string move, array hits
	 */
	public function get_move( )
	{
		call(__METHOD__);

		return array(
			0 => $this->_move,
			'move' => $this->_move,
			1 => $this->_hits,
			'hits' => $this->_hits,
		);
	}


	/** protected function _move_piece
	 *		Tests and moves a piece
	 *
	 * @param int board from index
	 * @param int board to index
	 * @param bool optional move both obelisks
	 * @return void
	 */
	protected function _move_piece($from_index, $to_index, $both_obelisks = true)
	{
		call(__METHOD__);

		$from_index = (int) $from_index;
		$to_index = (int) $to_index;
		$both_obelisks = (bool) $both_obelisks;
		call($from_index);
		call($to_index);
		call($both_obelisks);

		$piece = $this->_board[$from_index];
		$to_piece = $this->_board[$to_index];
		$silver = ($piece == strtoupper($piece));
		call($piece);
		call($to_piece);
		call($silver);

		// check for a piece on the from square
		if ('0' == $piece) {
			throw new MyException(__METHOD__.': No piece found to move');
		}

		// check for only one square away
		$difference = abs($from_index - $to_index);
		if (0 == $difference) {
			throw new MyException(__METHOD__.': The from square and to square are the same');
		}

		if ( ! in_array($difference, array(1, 9, 10, 11))) {
			throw new MyException(__METHOD__.': Piece can only move one square');
		}
		// TODO: this needs work, it works, but it's not pretty
		// TOWER: this is going to get _complicated_

		// check for moving off edge of board
		$off_vertical = ((0 > $to_index) || (80 <= $to_index));
		$off_horizontal = ((1 == abs($from_index - $to_index)) && (floor($to_index / 10) !== floor($from_index / 10)));
		if ($off_vertical || $off_horizontal) {
			throw new MyException(__METHOD__.': Piece cannot move off the edge of the board');
		}
		// TODO: this needs work, it works, but it's not pretty

		// check for wrong color piece moving onto a colored square
		$not_silver = array(0, 10, 20, 30, 40, 50, 60, 70, 8, 78);
		$not_red = array(9, 19, 29, 39, 49, 59, 69, 79, 1, 71);
		$color_array = 'not_'.self::get_piece_color($piece);
		if (in_array($to_index, ${$color_array})) {
			throw new MyException(__METHOD__.': Cannot move onto square of opposite color');
		}
		// TODO: this needs work, it works, but it's not pretty

		// TOWER: test for moving a pharaoh into a corner of the tower
		// it's not allowed

		// check for trying to move stationary sphynx
		if (in_array(strtoupper($piece), array('E','F','J','K')) && ! $this->_extra_info['move_sphynx']) {
			throw new MyException(__METHOD__.': Cannot move stationary sphynx');
		}

		// check for a piece in the way
		if ('0' != $to_piece) {
			$to_color_array = 'not_'.self::get_piece_color($to_piece);

				// make sure we are not moving obelisks around
			if (in_array(strtoupper($piece), array('V','W')) && ('V' != strtoupper($to_piece))) {
				throw new MyException(__METHOD__.': Target square not empty - '.$piece);
			}
				// both the Djed and Eye of Horus can swap places with other pieces
				// as well as swapping a single and double obelisk tower if the from is double
			elseif ( ! in_array(strtoupper($piece), array('H','I','X','Y','W','V'))) {
				throw new MyException(__METHOD__.': Target square not empty - '.$piece);
			}
				// but only with pyramids, obelisks, or anubises
			elseif ( ! in_array(strtoupper($to_piece), array('A','B','C','D','V','W','L','M','N','O'))) {
				throw new MyException(__METHOD__.': Target piece not swappable - '.$to_piece);
			}
				// make sure the swapped piece isn't going onto an off-color square
			elseif (in_array($from_index, ${$to_color_array})) {
				throw new MyException(__METHOD__.': Target piece not swappable due to ending on opposite color square - '.$to_piece.': '.self::get_piece_color($to_piece).' on '.self::get_piece_color($piece));
			}
		}

		// make sure we are not trying to split a non-obelisk tower (or single tower)
		if ( ! $both_obelisks && ('W' != strtoupper($piece))) {
			$both_obelisks = true;
		}

		// if we made it here, the move is easy
		// if we are joining / swapping obelisk towers
		if (in_array(strtoupper($piece), array('V','W')) && ('V' == strtoupper($to_piece))) {
			if ('V' == strtoupper($piece)) {
				$this->_board[$from_index] = '0';
				$this->_board[$to_index] = ($silver ? 'W' : 'w');
			}
			else {
				// swap the two places
				$temp = $to_piece;
				$this->_board[$to_index] = $piece;
				$this->_board[$from_index] = $temp;
			}
		}
		elseif ($both_obelisks) {
			// swap the two places
			$temp = $to_piece;
			$this->_board[$to_index] = $piece;
			$this->_board[$from_index] = $temp;
		}
		else { // we're splitting an obelisk tower
			// set both from and to equal to a single obelisk
			$this->_board[$from_index] = $this->_board[$to_index] = ($silver ? 'V' : 'v');
		}

		call($this->_get_board_ascii( ));
	}


	/** protected function _rotate_piece
	 *		Tests and rotates a piece
	 *
	 * @param int board index
	 * @param int direction (0 = CCW, 1 = CW)
	 * @return void
	 */
	protected function _rotate_piece($index, $direction)
	{
		call(__METHOD__);

		$rotation = array(
			'A' => array('D', 'B'),
			'B' => array('A', 'C'),
			'C' => array('B', 'D'),
			'D' => array('C', 'A'),

			'X' => array('Y', 'Y'),
			'Y' => array('X', 'X'),

			'H' => array('I', 'I'),
			'I' => array('H', 'H'),

			'E' => array('K', 'F'),
			'F' => array('E', 'J'),
			'J' => array('F', 'K'),
			'K' => array('J', 'E'),

			'L' => array('O', 'M'),
			'M' => array('L', 'N'),
			'N' => array('M', 'O'),
			'O' => array('N', 'L'),
		);

		$piece = $this->_board[$index];
		call($piece);
		$silver = ($piece == strtoupper($piece));
		$piece = strtoupper($piece);

		if ( ! isset($rotation[$piece])) {
			throw new MyException(__METHOD__.': Piece rotation not found');
		}

		$this->_board[$index] = ($silver ? $rotation[$piece][$direction] : strtolower($rotation[$piece][$direction]));
	}


	/** static public function extra_info
	 *		Returns the extra info merged with the default values
	 *
	 * @param array [optional] extra info
	 * @return array extra info
	 */
	static public function extra_info($extra_info = array( ))
	{
		$extra_info = array_clean($extra_info, array_keys(self::$EXTRA_INFO_DEFAULTS));
		return array_merge_plus(self::$EXTRA_INFO_DEFAULTS, $extra_info);
	}


	/** static public function get_board_ascii
	 *		Returns the board in an ASCII format
	 *
	 * @param string expanded board FEN
	 * @return string ascii board
	 */
	static public function get_board_ascii($board)
	{
		$ascii = "\n"
			.'     A   B   C   D   E   F   G   H   I   J'."\n"
			.'   +---+---+---+---+---+---+---+---+---+---+';

		for ($length = strlen($board), $i = 0; $i < $length; ++$i) {
			$char = $board[$i];

			if (0 == ($i % 10)) {
				$ascii .= "\n ".(8 - floor($i / 10)).' |';
			}

			if ('0' == $char) {
				$char = ' ';
			}

			$ascii .= ' '.$char.' |';

			if (9 == ($i % 10)) {
				$ascii .= ' '.(8 - floor($i / 10))."\n"
					.'   +---+---+---+---+---+---+---+---+---+---+';
			}
		}

		$ascii .= "\n"
			.'     A   B   C   D   E   F   G   H   I   J'."\n";

		/*
		    A   B   C   D   E   F   G   H   I   J
		  +---+---+---+---+---+---+---+---+---+---+
		8 | R | S |   |   |   |   |   |   | R | S | 8
		  +---+---+---+---+---+---+---+---+---+---+
		7 | R |   |   |   |   |   |   |   |   | S | 7
		  +---+---+---+---+---+---+---+---+---+---+
		6 | R |   |   |   |   |   |   |   |   | S | 6
		  +---+---+---+---+---+---+---+---+---+---+
		5 | R |   |   |   |   |   |   |   |   | S | 5
		  +---+---+---+---+---o---+---+---+---+---+
		4 | R |   |   |   |   |   |   |   |   | S | 4
		  +---+---+---+---+---+---+---+---+---+---+
		3 | R |   |   |   |   |   |   |   |   | S | 3
		  +---+---+---+---+---+---+---+---+---+---+
		2 | R |   |   |   |   |   |   |   |   | S | 2
		  +---+---+---+---+---+---+---+---+---+---+
		1 | R | S |   |   |   |   |   |   | R | S | 1
		  +---+---+---+---+---+---+---+---+---+---+
		    A   B   C   D   E   F   G   H   I   J
		*/

		return $ascii;
	}


	/** protected function _get_board_ascii
	 *		Returns the board in an ASCII format
	 *
	 * @see get_board_ascii
	 * @param string optional expanded board FEN
	 * @return string ascii board
	 */
	protected function _get_board_ascii($board = null)
	{
		if ( ! $board) {
			$board = $this->_board;
		}

		return self::get_board_ascii($board);
	}


	/** static public function get_laser_ascii
	 *		Returns the board in an ASCII format
	 *		with the laser path shown
	 *
	 * @see get_board_ascii
	 * @param string expanded board FEN
	 * @param array laser path
	 * @return string ascii board
	 */
	static public function get_laser_ascii($board, $laser_path)
	{
		foreach ($laser_path as $paths) {
			foreach ($paths as $node) {
				if (is_array($node)) {
					if (is_int($node[0]) && ('0' == $board[$node[0]])) {
						$board[$node[0]] = (1 == abs($node[1])) ? '-' : '|';
					}
				}
			}
		}

		return self::get_board_ascii($board);
	}


	/** protected function _get_laser_ascii
	 *		Returns the board in an ASCII format
	 *		with the laser path shown
	 *
	 * @see get_board_ascii
	 * @see get_laser_ascii
	 * @param string optional expanded board FEN
	 * @param array optional laser path
	 * @return string ascii board
	 */
	protected function _get_laser_ascii($board = null, $laser_path = null)
	{
		if ( ! $board) {
			$board = $this->_board;
		}

		if ( ! $laser_path) {
			$laser_path = $this->_laser_path;
		}

		return self::get_laser_ascii($board, $laser_path);
	}


	/** static public function target_to_index
	 *		Converts a human readable target (H7)
	 *		into a computer readable string index (76)
	 *
	 * @param string target
	 * @return int string index
	 */
	static public function target_to_index($target)
	{
		try {
			$target = self::test_target($target);
		}
		catch (MyException $e) {
			throw $e;
		}

		$chars = array('A','B','C','D','E','F','G','H','I','J');
		$index = array_search($target[0], $chars);

		$index += 10 * (8 - (int) $target[1]);

		return $index;
	}


	/** static public function index_to_target
	 *		Converts a computer readable string index (76)
	 *		into a human readable target (H7)
	 *
	 * @param int string index
	 * @return string target
	 */
	static public function index_to_target($index)
	{
		$chars = array('A','B','C','D','E','F','G','H','I','J');

		$target = $chars[$index % 10];
		$target .= (8 - floor($index / 10));

		return $target;
	}


	/** static public function get_piece_color
	 *		Grabs the color of the given piece
	 *
	 * @param string piece code
	 * @return string piece color
	 */
	static public function get_piece_color($piece)
	{
		if (strtoupper($piece) === $piece) {
			return 'silver';
		}

		return 'red';
	}


	/** static public function test_target
	 *		Tests the target for proper format
	 *
	 * @param string target
	 * @return string target
	 */
	static public function test_target($target)
	{
		// make sure it's uppercase and only 2 characters long
		$target = strtoupper($target);
		$target = substr($target, 0, 2);

		// test the format
		if ( ! preg_match('/[A-J]\d/', $target)) {
			throw new MyException(__METHOD__.': Invalid target format');
		}

		return $target;
	}


} // end Pharaoh

