<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Helpers/String.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Helpers;

/**
 * A class to assist string based operations
 *
 * @package System
 * @subpackage Helpers
 */
class String
{
	/**
	 * Replaces one or more format items in a specified string with the 
	 * string representation of a specified object
	 */
	public static function Format($string)
	{
		$args = array_slice(func_get_args(), 1);
		for($i = 0; $i < count($args); $i++)
			$string = str_replace("\{{$i}\}", $args[$i], $string);
			
		return $string;
	}
	
	/**
	 * Indicates whether the specified string is null or an Empty string
	 */
	public static function IsNullOrEmpty( $Input )
	{
		return (!is_string( $Input ) || empty($Input));
	}
	
	/**
	 * Indicates whether a specified string is null, empty, or consists 
	 * only of white-space characters
	 */
	public static function IsNullEmptyOrWhitespace( $Input )
	{
        $Input = trim( $Input );
		return (!is_string( $Input ) || empty($Input));
	}
	
	/**
	 * Determines whether the end of a string matches the specified string
	 */
	public static function EndsWith( $string, $sub )
	{
		$len = strlen( $sub );
		return substr_compare( $string, $sub, -$len, $len ) === 0;
	}
	
	/**
	 * Determines whether the beginning of a string matches a specified string
	 */
	public static function StartsWith( $string, $sub )
	{
		return substr_compare( $string, $sub, 0, strlen( $sub ) ) === 0;
	}
}