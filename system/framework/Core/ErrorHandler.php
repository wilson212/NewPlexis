<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Core/ErrorHandler.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 * @contains    ErrorHandler
 */
namespace System\Core;
use System\Http\WebRequest;
use System\Http\WebResponse;

/**
 * Responsible for handling all errors, and exceptions, and displaying
 * an error page
 *
 * @author      Steven Wilson 
 * @package     Core
 */
class ErrorHandler
{
	protected static $HandlingErrors = false;

	protected static $HandlingExceptions = false;

	/**
	 * Registers this object as the error handler
	 *
	 * @param bool $handleErrors
	 * @param bool $handleExceptions
	 *
	 * @return void
	 */
	public static function Register($handleErrors = true, $handleExceptions = true)
	{
		// Errors
		if($handleErrors && !self::$HandlingErrors)
		{
			self::$HandlingErrors = true;
			set_error_handler('System\Core\ErrorHandler::HandlePHPError');
			error_reporting(E_ALL);
		}

		// Exceptions
		if($handleExceptions && !self::$HandlingExceptions)
		{
			self::$HandlingExceptions = true;
			set_exception_handler('System\Core\ErrorHandler::HandleException');
		}

		// Make sure to register output buffering!
		if(ob_get_level() == 0)
		{
			ini_set('output_buffering', 'On');
			ob_start();
		}
	}

	/**
	 * UnRegisters this object as the error handler
	 *
	 * @return void
	 */
	public static function UnRegister()
	{
		self::$HandlingErrors = false;
		self::$HandlingExceptions = false;
		restore_error_handler();
		restore_exception_handler();
	}

	/**
	 * Main method for showing an error. Not guaranteed to display the error, just
	 * depends on the users error reporting level.
	 *
	 * @param int $lvl Error level. the error levels share the php constants error levels
	 * @param string $message The error message
	 * @param string $file The filename in which the error was triggered from
	 * @param int $line The line number in which the error was triggered from
	 * @return void
	 */
	public static function TriggerError($lvl, $message, $file, $line)
	{
		self::DisplayError($lvl, $message, $file, $line);
	}

	/**
	 * Same method as TriggerError, except this method is called by php internally
	 *
	 * @param int $lvl Error level. the error levels share the php constants error levels
	 * @param string $message The error message
	 * @param string $file The filename in which the error was triggered from
	 * @param int $line The line number in which the error was triggered from
	 * @return void
	 */
	public static function HandlePHPError($lvl, $message, $file, $line)
	{
		// If the error_reporting level is 0, then this is a suppressed error ("@" preceding)
		if(error_reporting() == 0) return;
		self::DisplayError($lvl, $message, $file, $line);
	}

	/**
	 * Main method for handling exceptions
	 *
	 * @param \Exception $e The thrown exception
	 * @return void
	 */
	public static function HandleException($e)
	{
		self::DisplayError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), true);
	}

	/**
	 * Displays the error screen
	 *
	 * @param int $lvl Error level. the error levels share the php constants error levels
	 * @param string $message The error message
	 * @param string $file The filename in which the error was triggered from
	 * @param int $line The line number in which the error was triggered from
	 * @param bool $exception Is this an exception? If set to false, the error will be treated
			as a php error rather then an exception
	 * @return void
	 */
	protected static function DisplayError($lvl, $message, $file, $line, $exception = false)
	{
		// Clear out the current output buffer
		if(ob_get_length() != 0) ob_clean();

		// Define variables
		$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
		$level = ($exception == true) ? $lvl : self::ErrorLevelToText($lvl);

		// If ajax, then we create a json encoded error
		if($isAjax)
		{
			$mode = ($exception == true) ? "exception" : "error";
			$data = array(
				'message' => "A php {$mode} was thrown during this request.",
				'errorData' => array(
					'exception' => $exception,
					'level' => $level,
					'message' => $message,
					'file' => $file,
					'line' => $line
				)
			);
			$page = json_encode($data);
		}
		else
		{
			// We wont use a view here because we might not have the Library namespace registered in the autoloader
			$page = @file_get_contents( SYSTEM_PATH . DS ."errors". DS ."general_error.php" );
			if(!empty($page))
			{
				// Set the template file's variables
				$title = ($exception == true) ? "Exception Thrown: " : "PHP Error: ";
				$find = array('{ERROR_LEVEL}', '{TITLE}', '{MESSAGE}', '{FILE}', '{LINE}');
				$replace = array($level, $title, $message, $file, $line);
				$page = str_replace($find, $replace, $page);
			}
			else
				$page = $message;
		}

		// Set error header if the headers have yet to be sent
		if(!headers_sent())
			header("HTTP/1.1 500 Internal Server Error");

		// Spit out the error page and tell plexis to stop execution
		echo $page;
		\Plexis::Stop();
	}

	/**
	 * Converts a php error constant level to a string
	 *
	 * @param int $lvl The error constant
	 * @return string
	 */
	protected static function ErrorLevelToText($lvl)
	{
		switch($lvl)
		{
			default:
			case E_ERROR:
				return 'Error';
			case E_WARNING:
				return 'Warning';
			case E_NOTICE:
				return 'Notice';
			case E_USER_ERROR:
				return 'User Error';
			case E_USER_WARNING:
				return 'User Warning';
			case E_USER_NOTICE:
				return 'User Notice';
			case E_PARSE:
				return 'Parse Error';
			case E_STRICT:
				return 'Strict';
			case E_CORE_ERROR:
				return 'PHP Core Error';
		}
	}
}