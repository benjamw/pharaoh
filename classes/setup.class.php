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


	/** protected property board
	 *		Holds the game board
	 *
	 * @var string
	 */
	protected $board;


	/** protected property reflection
	 *		Holds the reflection type
	 *		cane be: Origin, Long, Short, None
	 *
	 * @var string
	 */
	protected $reflection;



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
		return $this->get_board_ascii( );
	}


	function test_reflection($xFEN, $type = 'Origin')
	{
		if (preg_match('/[0-9]/', $xFEN)) {
			$xFEN = expandFEN($xFEN);
		}

		// look for invalid characters
		if (preg_match('/[^abcdhipvwxy0]/i', $xFEN)) {
			throw new MyException(__METHOD__.': Invalid characters found in setup');
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

				function _reflect($i) {
					// TOWER: may need to add some more reflect algorithms
					return 79 - $i;
				}
				break;

			default :
				return false;
				break;
		}

		// TOWER: may need to change limits
		for ($i = 0; $i < 80; ++$i) {
			$c = $xFEN[$i];

			if (('0' !== $c) && (strtoupper($c) === $c) && in_array($c, $reflect_keys)) {
				if ($reflect[$c] !== $xFEN[_reflect($i)]) {
					throw new MyException(__METHOD__.': Invalid reflected character found at index: '.$i.'- '.$c.'->'.$xFEN[_reflect($i)].'; should be '.$reflect[$c]);
				}

				// removed the tested chars
				$xFEN[$i] = '.';
				$xFEN[_reflect($i)] = '.';
			}
		}

		// we tested all silver -> red
		// now look for any remaining red
		if (preg_match('/[a-dhipvwxy]/', $xFEN)) {
			throw new MyException(__METHOD__.': Red piece found without matching Silver piece');
		}

		return true;
	}


	static public function get_list( )
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT *
			FROM ".self::SETUP_TABLE."
			ORDER BY has_tower ASC
				, has_horus ASC
				, used DESC
				, name ASC
		";
		$setups = $Mysql->fetch_array($query);

		return $setups;
	}


	static public function get_count($player_id = 0)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		$query = "
			SELECT COUNT(*)
			FROM ".self::SETUP_TABLE."
		";
		$total = Mysql::get_instance( )->fetch_value($query);

		$query = "
			SELECT COUNT(*)
			FROM ".self::SETUP_TABLE."
			WHERE created_by = '{$player_id}'
		";
		$mine = Mysql::get_instance( )->fetch_value($query);

		return array($total, $mine);
	}


	static public function add_used($setup_id)
	{
		call(__METHOD__);

		$setup_id = (int) $setup_id;

		$Mysql = Mysql::get_instance( );

		$query = "
			UPDATE ".self::SETUP_TABLE."
			SET used = used + 1
			WHERE setup_id = '{$setup_id}'
		";
		$Mysql->query($query);
	}


	/** static public function get_board_ascii
	 *		Returns the board in an ASCII format
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

} // end Setup

