<?php

// $Id: func.bitwise.php 162 2009-07-16 10:21:42Z cchristensen $

/**
 *		BITWISE FUNCTIONS
 * * * * * * * * * * * * * * * * * * * * * * * * * * */
// basically so i don't have to remember the bitwise functions


/** function bit_set [bitSet]
 *		Switch the $bit bits in $val to ON if $switch is true (or not given)
 *		and OFF is $switch is false
 *		$reverse reverses the $bit bits we wish to set;
 *		using the OFF bits, instead of the ON bits
 *
 *
 * @param int value we are modifying
 * @param int value contianing the bits we wish to modify
 * @param bool optional switch the bits on, or off
 * @param bool optional reverse the bits we wish to modify
 * @return int
 */
function bit_set($val, $bit, $switch = true, $reverse = false) {
	$val = (int) $val;
	$bit = (int) $bit;

	// set some strings that humans would consider false
	// but would be converted to true if converted by PHP
	if (is_string($switch)) {
		switch ($switch) {
			case 'false' :
			case 'down' :
			case 'off' :
			case 'not' :
			case '0' :
			case '' :
				$switch = false;
				break;

			default :
				$switch = true;
				break;
		}
	}

	if ((bool) $switch) {
		return bit_on($val, $bit, $reverse);
	}
	else {
		return bit_off($val, $bit, $reverse);
	}
}
function bitSet($val, $bit, $switch = true, $reverse = false) { return bit_set($val, $bit, $switch, $reverse); }


/** function bit_on [bitOn]
 *		Switch the $bit bits in $val to ON
 *		$reverse reverses the $bit bits we wish to set;
 *		using the OFF bits, instead of the ON bits
 *
 *
 * @param int value we are modifying
 * @param int value contianing the bits we wish to modify
 * @param bool optional reverse the bits we wish to modify
 * @return int
 */
function bit_on($val, $bit, $reverse = false) {
	$val = (int) $val;
	$bit = (int) $bit;

	if ((bool) $reverse) {
		return $val | ( ~ $bit);
	}
	else {
		return $val | $bit;
	}
}
function bitOn($val, $bit, $reverse = false) { return bit_on($val, $bit, $reverse); }


/** function bit_off [bitOff]
 *		Switch the $bit bits in $val to OFF
 *		$reverse reverses the $bit bits we wish to set;
 *		using the OFF bits, instead of the ON bits
 *
 *
 * @param int value we are modifying
 * @param int value contianing the bits we wish to modify
 * @param bool optional reverse the bits we wish to modify
 * @return int
 */
function bit_off($val, $bit, $reverse = false) {
	$val = (int) $val;
	$bit = (int) $bit;

	if ((bool) $reverse) {
		return $val & $bit;
	}
	else {
		return $val & ( ~ $bit);
	}
}
function bitOff($val, $bit, $reverse = false) { return bit_off($val, $bit, $reverse); }


/** function bit_toggle [bitToggle]
 *		Toggle the $bit bits in $val to the opposite of what they are now
 *		$reverse reverses the $bit bits we wish to set;
 *		using the OFF bits, instead of the ON bits
 *
 *
 * @param int value we are modifying
 * @param int value contianing the bits we wish to modify
 * @param bool optional reverse the bits we wish to modify
 * @return int
 */
function bit_toggle($val, $bit, $reverse = false) {
	$val = (int) $val;
	$bit = (int) $bit;

	if ((bool) $reverse) {
		return $val ^ ( ~ $bit);
	}
	else {
		return $val ^ $bit;
	}
}
function bitToggle($val, $bit, $reverse = false) { return bit_toggle($val, $bit, $reverse); }

