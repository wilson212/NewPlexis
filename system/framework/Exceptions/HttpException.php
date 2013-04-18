<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Exceptions/HttpException.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
 
/**
 * An exception thrown when there is an http exception, such as a 404 or a 403
 *
 * @package     System
 * @subpackage  Exceptions
 */
class HttpException extends Exception 
{
    /**
     * An array of status codes as keys, and descriptions as values
     * @var string[]
     */
    protected static $HttpCodes = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

    /**
     * Constructor
     *
     * @param int $statusCode The Http Status Code
     * @param string $customDesc A custom status code description
     */
    public function __construct($statusCode, $customDesc = null)
	{
        $message = (empty($customDesc) && isset(self::$HttpCodes[$statusCode]))
            ? self::$HttpCodes[$statusCode]
            : $customDesc;
	    parent::__construct($message, $statusCode);
	}
}