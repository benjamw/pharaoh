<?php
/*
+---------------------------------------------------------------------------
|
|   myexception.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > PHP Exception Extension module
|   > Date started: 2008-03-09
|
|   > Module Version Number: 1.0.0
|
+---------------------------------------------------------------------------
*/


class MyException
	extends Exception {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** private property _backtrace
	 *		should we show a backtrace in the log
	 *
	 * @param bool
	 */
	private $_backtrace = true;


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param string error message
	 * @param int optional error code
	 * @action instantiates object
	 * @action writes the exception to the log
	 * @return void
	 */
	public function __construct($message, $code = 1)
	{
		parent::__construct($message, $code);

		// our own exception handling stuff
		if ( ! empty($GLOBALS['_LOGGING'])) {
			$this->_write_error( );
		}
	}


	/** public function outputMessage
	 *		cleans the message for use
	 *
	 * @param void
	 * @return cleaned message
	 */
	public function outputMessage( )
	{
		// strip off the __METHOD__ bit of the error, if possible
		$message = $this->message;
		$message = preg_replace('/(?:\\w+::)+\\w+:\\s+/', '', $message);

		return $message;
	}


	/** protected function _write_error
	 *		writes the exception to the log file
	 *
	 * @param void
	 * @action writes the exception to the log
	 * @return void
	 */
	protected function _write_error( )
	{
		// first, lets make sure we can actually open and write to directory
		// specified by the global variable... and lets also do daily logs for now
		$log_name = 'exception_'.date('Ymd', time( )).'.log';

		// okay, write our log message
		$str = date('Y/m/d H:i:s')." == ({$this->code}) {$this->message} : {$this->file} @ {$this->line}\n";

		if ($this->_backtrace) {
			$str .= "---------- [ BACKTRACE ] ----------\n";
			$str .= $this->getTraceAsString( )."\n";
			$str .= "-------- [ END BACKTRACE ] --------\n\n";
		}

		if ($fp = @fopen(LOG_DIR.$log_name, 'a')) {
			fwrite($fp, $str);
			fclose($fp);
		}

		call($str);
	}

}


/* PHP's built in Exception Class Reference ----

class Exception
{
    protected $message = 'Unknown exception';   // exception message
    protected $code = 0;                        // user defined exception code
    protected $file;                            // source filename of exception
    protected $line;                            // source line of exception

    function __construct($message = null, $code = 0);

    final function getMessage();                // message of exception
    final function getCode();                   // code of exception
    final function getFile();                   // source filename
    final function getLine();                   // source line
    final function getTrace();                  // an array of the backtrace()
    final function getTraceAsString();          // formated string of trace

    // Overrideable
    function __toString();                       // formated string for display
}

*/

