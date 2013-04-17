<?php
/**
 * Plexis Content Management System
 *
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
 
/**
 * An exception thrown when there is an http exception, such as a 404 or a 403
 *
 * @package     System
 * @subpackage  Http
 * @file        System/Http/HttpException.php
 */
class HttpException extends Exception 
{
	public function __construct($statusCode, $customDesc)
	{
	
	}
}
?>