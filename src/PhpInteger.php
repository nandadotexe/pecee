<?php
namespace Pecee;
class PhpInteger {
	
	/**
	 * Check if a given value is a counting type or 
	 * if the value of the string has numbers in it.
	 * @return bool
	 */
	public static function isInteger($val) {
		return (is_int(filter_var($val, FILTER_VALIDATE_INT)) || is_int($val));
	}
	
	public static function isNummeric($val) {
		return (self::isInteger($val) || is_numeric($val));
	}

}