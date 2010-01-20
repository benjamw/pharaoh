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
|   $Id: setup.class.php 198 2009-09-23 00:29:54Z cchristensen $
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


	protected function _expandFEN($FEN)
	{
		$FEN = trim($FEN);

		$xFEN = preg_replace('/(1?[0-9])/e', "str_repeat('0', \\1)", $FEN);
		$xFEN = str_replace('/', '', $xFEN); // Leave only pieces and empty squares

		return $xFEN;
	}


	protected function _packFEN($xFEN)
	{
		$xFEN = trim($xFEN);

		$FEN = trim(chunk_split($xFEN, 10, '/'), '/'); // add the row separaters
		$FEN = preg_replace('/(0{1,10})/e', "strlen('\\1')", $FEN);

		return $FEN;
	}


	function test_reflection($xFEN, $type = 'Origin') {
		if (preg_match('/[0-9]/', $xFEN)) {
			$xFEN = expandFEN($xFEN);
		}

		// look for invalid characters
		if (preg_match('/[^abcdhipvwxy0]/i', $xFEN)) {
			throw new Exception('Invalid characters found in setup');
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
					throw new Exception('Invalid reflected character found at index: '.$i.'- '.$c.'->'.$xFEN[_reflect($i)].'; should be '.$reflect[$c]);
				}

				// removed the tested chars
				$xFEN[$i] = '.';
				$xFEN[_reflect($i)] = '.';
			}
		}

		// we tested all silver -> red
		// now look for any remaining red
		if (preg_match('/[a-dhipvwxy]/', $xFEN)) {
			throw new Exception('Red piece found without matching Silver piece');
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
		";
		$setups = $Mysql->fetch_array($query);

		return $setups;
	}

} // end Setup

