<?php
/*
+---------------------------------------------------------------------------
|
|   setup.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Pharaoh Setup module
|   > Date started: 2009-12-22
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

class Setup {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property SETUP_TABLE
	 *		Holds the game setup table name
	 *
	 * @var string
	 */
	const SETUP_TABLE = T_SETUP;


	/** protected property id
	 *		Holds the setup id
	 *
	 * @var int
	 */
	protected $id;


	/** protected property board
	 *		Holds the game board as expanded FEN
	 *
	 * @var string
	 */
	protected $board;


	/** protected property name
	 *		Holds the name of the setup
	 *
	 * @var string
	 */
	protected $name;


	/** protected property creator
	 *		Holds the id of the creator (player)
	 *
	 * @var int
	 */
	protected $creator;



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
	public function __construct($id = null)
	{
		$id = (int) $id;
		call(__METHOD__);

		if ($id) {
			$this->id = $id;
		}

		$this->_pull( );
	}


	/** public function __destruct
	 *		Class destructor
	 *		Gets object ready for destruction
	 *
	 * @param void
	 * @action destroys object
	 * @return void
	 */
	public function __destruct( )
	{
		// save anything changed to the database
		// BUT... only if PHP didn't die because of an error
		$error = error_get_last( );

		if (0 == ((E_ERROR | E_WARNING | E_PARSE) & $error['type'])) {
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

		switch ($property) {
			case 'name' :
				// make sure this name isn't in use
				$test_value = sani($value);
				$query = "
					SELECT COUNT(*)
					FROM ".self::SETUP_TABLE."
					WHERE name = '{$test_value}'
						AND setup_id <> '{$this->id}'
				";
				$count = Mysql::get_instance( )->fetch_value($query);

				if ($count) {
					throw new MyException(__METHOD__.': That setup name ('.$value.') is already in use');
				}
				break;

			case 'board' :
				try {
					self::is_valid_setup($value);
				}
				catch (MyExecption $e) {
					throw $e;
				}

				$value = expandFEN($value);
				break;

			case 'id' :
				$value = (int) $value;
				break;

			default :
				// do nothing
				break;
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


	/** static public function get_board_ascii
	 *		Returns the board in an ASCII format
	 *
	 *      A   B   C   D   E   F   G   H   I   J
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 8 | R | S |   |   |   |   |   |   | R | S | 8
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 7 | R |   |   |   |   |   |   |   |   | S | 7
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 6 | R |   |   |   |   |   |   |   |   | S | 6
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 5 | R |   |   |   |   |   |   |   |   | S | 5
	 *   +---+---+---+---+---o---+---+---+---+---+
	 * 4 | R |   |   |   |   |   |   |   |   | S | 4
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 3 | R |   |   |   |   |   |   |   |   | S | 3
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 2 | R |   |   |   |   |   |   |   |   | S | 2
	 *   +---+---+---+---+---+---+---+---+---+---+
	 * 1 | R | S |   |   |   |   |   |   | R | S | 1
	 *   +---+---+---+---+---+---+---+---+---+---+
	 *      A   B   C   D   E   F   G   H   I   J
	 *
	 * @param string expanded board FEN
	 * @return string ascii board
	 */
	static public function get_board_ascii($board)
	{
		$ascii = '
     A   B   C   D   E   F   G   H   I   J
   +---+---+---+---+---+---+---+---+---+---+';

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
				$ascii .= ' '.(8 - floor($i / 10)).'
   +---+---+---+---+---+---+---+---+---+---+';
  			}
		}

		$ascii .= '
     A   B   C   D   E   F   G   H   I   J
';

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


	/** protected function _pull
	 *		Pulls the setup data from the database
	 *
	 * @param void
	 * @action pulls the setup data and sets the class properties
	 * @return void
	 */
	protected function _pull( )
	{
		call(__METHOD__);

		if ( ! $this->id) {
			return;
		}

		$query = "
			SELECT *
			FROM ".self::SETUP_TABLE."
			WHERE setup_id = '{$this->id}'
		";
		$setup = Mysql::get_instance( )->fetch_assoc($query);

		$this->board = expandFEN($setup['board']);
		$this->creator = $setup['created_by'];
		$this->name = $setup['name'];
	}


	/** public function validate
	 *		Validates the current setup
	 *
	 * @param void
	 * @return bool if the setup is valid
	 */
	public function validate( )
	{
		call(__METHOD__);
		call($this->board);

		$Mysql = Mysql::get_instance( );

		try {
			// will run is_valid_setup as well
			self::is_valid_reflection($this->board);
		}
		catch (MyExecption $e) {
			throw $e;
		}

		// test for pre-existing setup
		$FEN = packFEN($this->board);
		$query = "
			SELECT *
			FROM ".self::SETUP_TABLE."
			WHERE board = '{$FEN}'
				AND setup_id <> '{$this->id}'
		";
		$result = $Mysql->fetch_assoc($query);

		if ($result) {
			throw new MyException(__METHOD__.': Setup already exists as "'.$result['name'].'" (#'.$result['setup_id'].')');
		}

		// test for pre-existing setup name
		$name = sani($this->name);
		$query = "
			SELECT *
			FROM ".self::SETUP_TABLE."
			WHERE name = '{$name}'
				AND setup_id <> '{$this->id}'
		";
		$result = $Mysql->fetch_assoc($query);

		if ($result) {
			throw new MyException(__METHOD__.': Setup name ('.$name.') already used (#'.$result['setup_id'].')');
		}

		return true;
	}


	/** protected function _save
	 *		Saves the setup data to the database
	 *
	 * @param void
	 * @action saves the setup data
	 * @return void
	 */
	protected function _save( )
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		try {
			$this->validate( );
		}
		catch (MyException $e) {
			throw $e;
		}

		// DON'T sanitize the data
		// it gets sani'd in the MySQL->insert method

		// translate (filter/sanitize) the data
		$data['name'] = $this->name;
		$data['board'] = packFEN($this->board);
		$data['reflection'] = self::_get_reflection($this->board);
		$data['has_horus'] = self::has_horus($this->board);
		$data['has_tower'] = self::has_tower($this->board);
		$data['created_by'] = (int) $this->creator;
		call($data);

		// create the setup
		$required = array(
			'name',
			'board',
		);

		$key_list = array_merge($required, array(
			'reflection',
			'has_horus',
			'has_tower',
			'created_by',
		));

		try {
			$_DATA = array_clean($data, $key_list, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['created '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo


		// grab the original setup data
		$query = "
			SELECT *
			FROM ".self::SETUP_TABLE."
			WHERE setup_id = '{$this->id}'
		";
		$setup = $Mysql->fetch_assoc($query);
		call($setup);

		if ( ! empty($this->id) && ! $setup) {
			// we must have deleted the setup, just stop
			return false;
		}

		if ( ! $setup) {
			$this->id = $Mysql->insert(self::SETUP_TABLE, $_DATA);

			if (empty($this->id)) {
				throw new MyException(__METHOD__.': Setup could not be created');
			}

			return $this->id;
		}

		$update_setup = false;

		foreach ($setup as $key => $value) {
			if (empty($_DATA[$key])) {
				continue;
			}

			if ($_DATA[$key] != $value) {
				$update_setup[$key] = $_DATA[$key];
			}
		}
		call($update_setup);

		if ($update_setup) {
			$Mysql->insert(self::SETUP_TABLE, $update_setup, " WHERE setup_id = '{$this->id}' ");
			return true;
		}

		return false;
	}

	public function save( )
	{
		call(__METHOD__);
		call($_POST);

		// set the post stuff into the object
		try {
			$this->name = $_POST['name'];
			$this->board = $_POST['setup'];
			$this->creator = $_SESSION['player_id'];
		}
		catch (MyException $e) {
			throw $e;
		}

		return $this->_save( );
	}


	/** static public function is_valid_setup
	 *		Tests the validity of the given setup
	 *
	 * @param string setup
	 * @return bool valid setup
	 */
	static public function is_valid_setup($setup)
	{
		call(__METHOD__);
		call($setup);

		// expand the board FEN
		$xFEN = expandFEN($setup);

		if (80 != strlen($xFEN)) {
			throw new MyException(__METHOD__.': Incorrect board size');
		}

		// test for a pharaoh on both sides
		if ( ! preg_match('/P/', $xFEN, $s_match) || ! preg_match('/p/', $xFEN, $r_match)) {
			throw new MyException(__METHOD__.': Missing one or both Pharaohs');
		}

		// make sure there's only one of each pharaoh
		if ((1 != count($s_match)) || (1 != count($s_match))) {
			throw new MyException(__METHOD__.': Too many of one or both Pharaohs');
		}

		// look for invalid characters
		if (preg_match('/[^abcdhiptvwxy0]/i', $xFEN)) {
			throw new MyException(__METHOD__.': Invalid characters found in setup');
		}

		// test for pieces on incorrect colors
		$not_silver = array(0, 10, 20, 30, 40, 50, 60, 70, 8, 78);
		foreach ($not_silver as $i) {
			if (('0' != $xFEN[$i]) && ('silver' == Pharaoh::get_piece_color($xFEN[$i]))) {
				throw new MyException(__METHOD__.': Silver piece on red square at '.$i);
			}
		}

		$not_red = array(9, 19, 29, 39, 49, 59, 69, 79, 1, 71);
		foreach ($not_red as $i) {
			if (('0' != $xFEN[$i]) && ('red' == Pharaoh::get_piece_color($xFEN[$i]))) {
				throw new MyException(__METHOD__.': Red piece on silver square at '.$i);
			}
		}

		return true;
	}


	/** static public function test_reflection
	 *		Tests the validity of the given reflection
	 *		for the given setup
	 *
	 * @param string setup
	 * @param string [optional] reflection type (Origin, Long, Short)
	 * @return bool if the reflection is valid
	 */
	static public function is_valid_reflection($setup, $type = 'Origin')
	{
		call(__METHOD__);
		call($setup);

		// expand the board FEN
		$xFEN = expandFEN($setup);

		// validate the setup
		try {
			self::is_valid_setup($xFEN);
		}
		catch (MyException $e) {
			throw $e;
		}

		// make sure the given xFEN has all the pieces reflected properly
		switch ($type) {
			case 'Origin' :
				$reflect = array(
					// pyramids
					'A' => 'c', 'C' => 'a',
					'B' => 'd', 'D' => 'b',

					// djeds
					'X' => 'x', 'Y' => 'y',

					// obelisks
					'V' => 'v',
					'W' => 'w',

					// pharaoh
					'P' => 'p',

					// eye of horus
					'H' => 'h', 'I' => 'i',
				);
				$reflect_keys = array_keys($reflect);

				// TOWER: may need to add some more reflect algorithms
				$_reflect = '
					$return = 79 - $i;
				';
				break;

			case 'Long' :
				$reflect = array(
					// pyramids
					'A' => 'b', 'B' => 'a',
					'C' => 'd', 'D' => 'c',

					// djeds
					'X' => 'y', 'Y' => 'x',

					// obelisks
					'V' => 'v',
					'W' => 'w',

					// pharaoh
					'P' => 'p',

					// eye of horus
					'H' => 'i', 'I' => 'h',
				);
				$reflect_keys = array_keys($reflect);

				// TOWER: may need to add some more reflect algorithms
				$_reflect = '
					$tens = (int) floor($i / 10);
					$return = ((7 - $tens) * 10) + ($i % 10);
				';
				break;

			case 'Short' :
				$reflect = array(
					// pyramids
					'A' => 'd', 'D' => 'a',
					'B' => 'c', 'C' => 'b',

					// djeds
					'X' => 'y', 'Y' => 'x',

					// obelisks
					'V' => 'v',
					'W' => 'w',

					// pharaoh
					'P' => 'p',

					// eye of horus
					'H' => 'i', 'I' => 'h',
				);
				$reflect_keys = array_keys($reflect);

				// TOWER: may need to add some more reflect algorithms
				$_reflect = '
					$tens = (int) floor($i / 10);
					$return = ($tens * 10) + (9 - ($i % 10));
				';
				break;

			default :
				return true;
				break;
		}

		// TOWER: may need to change limits
		for ($i = 0; $i < 80; ++$i) {
			$c = $xFEN[$i];

			if (('0' !== $c) && (strtoupper($c) === $c) && in_array($c, $reflect_keys)) {
				eval($_reflect); // creates $return
				if ($reflect[$c] !== $xFEN[$return]) {
					throw new MyException(__METHOD__.': Invalid reflected character found at index '.$i.' ('.Pharaoh::index_to_target($i).') = '.$c.'->'.$xFEN[_reflect($i)].' should be '.$reflect[$c]);
				}

				// remove the tested chars
				$xFEN[$i] = '.';
				$xFEN[$return] = '.';
			}
		}

		// we tested all silver -> red
		// and matching pairs were removed
		// now look for any remaining red
		if (preg_match('/[a-dhipvwxy]/', $xFEN, $matches, PREG_OFFSET_CAPTURE)) {
			// TODO: get index for faulty red pieces
			throw new MyException(__METHOD__.': Red piece found without matching Silver piece');
		}

		return true;
	}


	/** static public function _get_reflection
	 *		Gets the reflection type for the given setup
	 *
	 * @param string board
	 * @return string reflection type
	 */
	static public function _get_reflection($setup)
	{
		call(__METHOD__);
		call($setup);

		// expand the board FEN
		$xFEN = expandFEN($setup);

		foreach (array('Origin', 'Long', 'Short', 'None') as $type) {
			try {
				self::is_valid_reflection($xFEN, $type);
			}
			catch (MyException $e) {
				continue;
			}

			break;
		}

		return $type;
	}


	/** public function get_reflection
	 *		Gets the reflection type for the current setup
	 *
	 * @param void
	 * @return string reflection type
	 */
	public function get_reflection( )
	{
		return self::_get_reflection($this->board);
	}


	/** static public function has_tower
	 *		Does the given setup use the tower?
	 *
	 * @param void
	 * @return bool setup has tower
	 */
	static public function has_tower($setup)
	{
		return preg_match('/[t]/i', $setup);
	}


	/** static public function has_horus
	 *		Does the given setup use the Eye of Horus?
	 *
	 * @param void
	 * @return bool setup has eye of horus
	 */
	static public function has_horus($setup)
	{
		return preg_match('/[hi]/i', $setup);
	}


	/** static public function delete
	 *		Deletes the given setup and all related data
	 *
	 * @param mixed array or csv of setup ids
	 * @action deletes the setup and all related data from the database
	 * @return void
	 */
	static public function delete($ids)
	{
		$Mysql = Mysql::get_instance( );

		array_trim($ids, 'int');

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No game ids given');
		}

		$tables = array(
			self::SETUP_TABLE ,
		);

		$Mysql->multi_delete($tables, " WHERE setup_id IN (".implode(',', $ids).") ");

		$query = "
			OPTIMIZE TABLE ".self::SETUP_TABLE."
		";
		$Mysql->query($query);
	}


	/** static public function get_list
	 *		Gets the list of all the setups
	 *
	 * @param void
	 * @return array setups
	 */
	static public function get_list( )
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT S.*
				, COUNT(G.game_id) AS current_games
			FROM ".self::SETUP_TABLE." AS S
				LEFT JOIN ".Game::GAME_TABLE." AS G
					ON (G.setup_id = S.setup_id)
			GROUP BY S.setup_id
			ORDER BY S.has_tower ASC
				, S.has_horus ASC
				, S.name ASC
		";
		$setups = $Mysql->fetch_array($query);

		return $setups;
	}


	/** static public function get_count
	 *		Gets the count of all the setups
	 *		optionally filters by creator
	 *
	 * @param int [optipnal] player id
	 * @return array (total, owned by player)
	 */
	static public function get_count($player_id = 0)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		$query = "
			SELECT COUNT(*)
			FROM ".self::SETUP_TABLE."
		";
		$total = $Mysql->fetch_value($query);

		$query = "
			SELECT COUNT(*)
			FROM ".self::SETUP_TABLE."
			WHERE created_by = '{$player_id}'
		";
		$mine = $Mysql->fetch_value($query);

		return array($total, $mine);
	}


	/** static public function add_used
	 *		Increments the 'used' count for the given setup
	 *
	 * @param int setup id
	 * @action increments the 'used' count
	 * @return void
	 */
	static public function add_used($setup_id)
	{
		call(__METHOD__);

		$setup_id = (int) $setup_id;

		$query = "
			UPDATE ".self::SETUP_TABLE."
			SET used = used + 1
			WHERE setup_id = '{$setup_id}'
		";
		Mysql::get_instance( )->query($query);
	}

} // end Setup


/*

--
-- Table structure for table `ph_setup`
--

DROP TABLE IF EXISTS `ph_setup`;
CREATE TABLE IF NOT EXISTS `ph_setup` (
  `setup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `board` varchar(87) COLLATE latin1_general_ci NOT NULL,
  `reflection` enum('Origin','Short','Long','None') COLLATE latin1_general_ci NOT NULL DEFAULT 'Origin',
  `has_horus` tinyint(1) NOT NULL DEFAULT '0',
  `has_tower` tinyint(1) NOT NULL DEFAULT '0',
  `used` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'the player id of the player that created the setup',
  PRIMARY KEY (`setup_id`),
  KEY `has_horus` (`has_horus`),
  KEY `has_tower` (`has_tower`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=27 ;

--
-- Dumping data for table `ph_setup`
--

INSERT INTO `ph_setup` (`setup_id`, `name`, `board`, `reflection`, `has_horus`, `has_tower`, `used`, `created`, `created_by`) VALUES
(NULL, 'Classic', '4wpwb2/2c7/3D6/a1C1xy1b1D/b1D1YX1a1C/6b3/7A2/2DWPW4', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Dynasty', '4cwb3/5p4/a3cwy3/b1x1D1B3/3d1b1X1D/3YWA3C/4P5/3DWA4', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Imhotep', '4wpwy2/10/3D2a3/aC2By2bD/bD2Yd2aC/3C2b3/10/2YWPW4', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Osiris', '1Y3cp1bC/2w3wb2/6D3/a3ix3D/b3XI3C/3b6/2DW3W2/aD1PA3y1', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Isis', '6wpb1/a1x3cw2/1X8/a1a1DI2c1/1A2ib1C1C/8x1/2WA3X1C/1DPW6', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Classic 2', '4wpwb2/2c7/3D6/a1C1xi1b1D/b1D1IX1a1C/6b3/7A2/2DWPW4', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Dynasty 2', '4cwb3/5p4/a3cwy3/b1h1D1B3/3d1b1H1D/3YWA3C/4P5/3DWA4', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Imhotep 2', '4wpwy2/10/3D2a3/aC2Bi2bD/bD2Id2aC/3C2b3/10/2YWPW4', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Khufu', '4wpwb2/5cb3/a5D3/4yX1a1D/b1C1Xy4/3b5C/3DA5/2DWPW4', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Imseti', '1B1wpb4/2Xbcw4/10/a3xc3D/b3AX3C/10/4WADx2/4DPW1d1', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Nefertiti', '4w1w3/3c1pb3/2C1cy1c2/a1Y6D/b6y1C/2A1YA1a2/3DP1A3/3W1W4', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Rameses', '3w1pwb2/4bc4/2Cb2x3/a4X3D/b3x4C/3X2Da2/4AD4/2DWP1W3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Amarna', '1CBcwpw3/4bcb3/10/a2x2x3/3X2X2C/10/3DAD4/3WPWAda1', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Saqqara', '3cwp1wb1/4bxb3/a2D6/4X4D/b4x4/6b2C/3DXD4/1DW1PWA3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Djoser''s Step', '3cw1w1b1/5p1b2/4bxb3/a4y3D/b3Y4C/3DXD4/2D1P5/1D1W1WA3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Horemheb', '3c3b2/4wpw3/3x1x1b2/a3c1b2D/b2D1A3C/2D1X1X3/3WPW4/2D3A3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Senet', '4cwb3/a2c1p1b2/4xwy3/5b3C/a3D5/3YWX4/2D1P1A2C/3DWA4', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Tutankhamun', '3w4b1/a1cpb5/3w1b4/b1x1y1b3/3D1Y1X1D/4D1W3/5DPA1C/1D4W3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Offla', '3c1pwb2/4cwb3/2X2x4/a4D3D/b3b4C/4X2x2/3DWA4/2DWP1A3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Ebana', '3xwpwb2/5x4/2DA6/a1CB5D/b5da1C/6cb2/4X5/2DWPWX3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Qa''a', '3xwpwb2/4cy4/8bD/3c2b2C/a2D2A3/bD8/4YA4/2DWPWX3', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Qa''a 2', '3xwpwb2/4cy4/8bD/3c2b1hC/aH1D2A3/bD8/4YA4/2DWPWX3', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Seti I', '1XB1cw1pb1/a4cwy1D/6b3/5i4/4I5/3D6/b1YWA4C/1DP1WA1dx1', 'Origin', 1, 0, 0, NOW( ), 0),
(NULL, 'Seti II', '1XB1cw1pb1/a4cwy1D/6b3/10/10/3D6/b1YWA4C/1DP1WA1dx1', 'Origin', 0, 0, 0, NOW( ), 0),
(NULL, 'Ay', '4cw1wbC/3CB5/5x4/a3Ypb3/3DPy3C/4X5/5da3/aDW1WA4', 'Origin', 0, 0, 0, NOW( ), 0);


*/

