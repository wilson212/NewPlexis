<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework//WebResponse.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Http;

/**
 * WebResponse Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  
 */
class WebResponse
{
    /**
     * HTTP protocol 1.0
     */
    const HTTP_10 = 'HTTP/1.0';

    /**
     * HTTP protocol 1.1
     */
    const HTTP_11 = 'HTTP/1.1';

    /**
     * Response Protocol (HTTP/1.0 | 1.1)
     * @var string
     */
    protected $protocol = self::HTTP_11;

    /**
     * The request object for this response
     * @var WebRequest
     */
    protected $request;

    /**
     * Status code to be returned in the response
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Content encoding
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * Content Mime Type
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * Array of headers to be sent with the response
     * @var string[]
     */
    protected $headers = array();

    /**
     * Array of cookies to be sent with the response
     * @var \System\Http\Cookie[]
     */
    protected $cookies = array();

    /**
     * The response body (contents)
     * @var string
     */
    protected $body;

    /**
     * Used to determine if output / headers have been sent already
     * @var bool
     */
    protected $outputSent = false;

    /**
     * Array of $statusCode => $description
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

    public function __construct(WebRequest $Request)
    {
        $this->request = $Request;
    }

    public function statusCode($code = null)
    {
        if(empty($code))
            return $this->statusCode;

        $this->statusCode = $code;
        return $this;
    }

    /**
     * Sets or returns the body of the response, based on
     * if a variable is passed setting the contents or not.
     *
     * @param string|null $contents
     *
     * @internal param string $content The body contents. Leave null if retrieving
     *   the current set contents.
     *
     * @return string|$this If $content is left null, the current
     *   contents are returned, else, this object is returned
     */
    public function body($contents = null)
    {
        // Are we setting or retrieving?
        if(empty($contents))
            return $this->body;

        $this->body = (string) $contents;
        return $this;
    }

    public function appendBody($contents)
    {
        $this->body .= $contents;
    }

    public function prependBody($contents)
    {
        $this->body = $contents . $this->body;
    }

    /**
     * Sets a header $key to the given $value
     *
     * @param $name
     * @param string $value The header key's or name's value to be set
     *
     * @internal param string $key The header key or name
     * @return void
     */
    public function setHeader($name, $value)
    {
        $key = str_replace('_', '-', $name);
        if($key == 'Content-Type')
        {
            if(preg_match('/^(.*);\w*charset\w*=\w*(.*)/', $value, $matches))
            {
                $this->contentType = $matches[1];
                $this->charset = $matches[2];
            }
            else
                $this->contentType = $value;
        }
        else
            $this->headers[$key] = $value;
    }

    /**
     * Sets a cookies value
     *
     * @param \System\Http\Cookie $Cookie The cookie object
     *
     * @return void
     */
    public function setCookie(Cookie $Cookie)
    {
        $this->cookies[$Cookie->getName()] = $Cookie;
    }

    /**
     * Sends all the response headers, cookies, and current buffered contents
     * to the client. After this method is called, any output will most likely
     * cause a content length error for our client.
     *
     * @param bool $clearOutputBuffer Clear the output buffer?
     *
     * @return void
     */
    public function send($clearOutputBuffer = true)
    {
        // Send headers
        $this->sendHeaders();

        // Output the body contents
        echo $this->body;

        if($clearOutputBuffer)
            ob_flush();
    }

    /**
     * Sends a header
     *
     * @param string $name The name of the header
     * @param string $value The value of the header
     * @return bool
     */
    protected function sendHeader($name, $value = null)
    {
        // Make sure the headers haven't been sent!
        if (!headers_sent())
        {
            if (is_null($value))
                header($name);
            else
                header("{$name}: {$value}");

            return true;
        }

        return false;
    }

    /**
     * Sends all headers to the browser
     *
     * @return void
     */
    protected function sendHeaders()
    {
        // Send status
        $this->sendHeader("{$this->protocol} {$this->statusCode} ". self::$HttpCodes[$this->statusCode]);

        // Send Cookies
        foreach($this->cookies as $Cookie)
        {
            $domain = ($Cookie->getDomain() == false) ? $_SERVER['HTTP_HOST'] : $Cookie->getDomain();
            setcookie(
                $Cookie->getName(),
                $Cookie->getValue(),
                $Cookie->getExpireTime(),
                $Cookie->getPath(),
                $domain
            );
        }

        // Send Content Type
        if (strpos($this->contentType, 'text/') === 0)
            $this->setHeader('Content-Type', $this->contentType ."; charset=". $this->charset);
        elseif ($this->contentType === 'application/json')
            self::SetHeader('Content-Type', $this->contentType ."; charset=UTF-8");
        else
            self::SetHeader('Content-Type', $this->contentType);

        // Send the rest of the headers
        foreach ($this->headers as $key => $value)
            $this->sendHeader($key, $value);

    }
}