<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Http/WebResponse.php
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
     * WebResponse Protocol (HTTP/1.0 | 1.1)
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

    /**
     * Sets or returns the HTTP status code for the response.
     *
     * @param int|null $code If left null, the current response code
     *   is returned, otherwise, the code will be set
     *
     * @return $this|int|bool
     */
    public function statusCode($code = null)
    {
        if(empty($code))
            return $this->statusCode;

        // Cant set a different status code if this is a hard redirect
        if(isset($this->headers["Location"]))
            return false;

        $this->statusCode = $code;
        return $this;
    }

    /**
     * Sets or returns the body of the response, based on
     * if a variable is passed setting the contents or not.
     *
     * @param string|null $contents The body contents. Leave null if retrieving
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

        $this->body = $contents;
        return $this;
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
     * Sets or returns the content type
     *
     * @param string $val The content type to be set
     *
     * @return string|void If $val is left null, the current content
     *   type is returned
     */
    public function contentType($val = null)
    {
        // Are we setting or retrieving?
        if($val == null)
            return $this->contentType;

        $this->contentType = $val;
        return $this;
    }

    /**
     * Sets or returns the content encoding
     *
     * @param string $val The content encoding to be set
     *
     * @return string|void If $val is left null, the current content
     *   encoding is returned
     */
    public function encoding($val = null)
    {
        // Are we setting or retrieving?
        if($val == null)
            return $this->charset;

        $this->charset = $val;
        return $this;
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
     * Returns the current body of the response.
     *
     * @param bool $sendHeaders Send the headers?
     *
     * @return string
     */
    public function capture($sendHeaders = false)
    {
        if($sendHeaders)
            $this->sendHeaders();

        return $this->body;
    }

    /**
     * This method sets a redirect header, and status code.
     *
     * @param string $location The redirect URL. If a relative path
     *   is passed here, the site's URL will be appended
     * @param int $waitTime The wait time (in seconds) before the redirect
     *   takes affect. If set to a non 0 value, the page will still be
     *    rendered. Default is 0 seconds.
     * @param bool $permanent Is this a permanent redirect?
     *
     * @return void
     */
    public function redirect($location, $waitTime = 0, $permanent = false)
    {
        // If we have a relative path, append the site url
        $location = trim($location);
        if(!preg_match('@^((ftp|http(s)?)://|www\.)@i', $location))
            $location = WebRequest::BaseUrl() .'/'. ltrim($location, '/');

        // Set redirect status code
        $this->statusCode = ($permanent) ? 301 : 307;

        // Reset all set data, and process the redirect immediately
        if($waitTime == 0)
        {
            $this->headers['Location'] = $location;
            $this->body = null;
        }
        else
            $this->headers['Refresh'] = $waitTime .';url='. $location;
    }

    /**
     * Indicates whether a redirect has been set or not
     *
     * @return bool
     */
    public function isRedirect()
    {
        return (isset($this->headers['Location']) || isset($this->headers['Refresh']));
    }

    /**
     * Removes all current redirects that are set
     *
     * @return void
     */
    public function clearRedirect()
    {
        if(isset($this->headers['Location']))
            unset($this->headers['Location']);

        if(isset($this->headers['Refresh']))
            unset($this->headers['Refresh']);
    }

    /**
     * Removes all current headers that are set
     *
     * @return void
     */
    public function clearHeaders()
    {
        $this->headers = array();
    }

    /**
     * Removes all current cookies that are modified
     *
     * @return void
     */
    public function clearCookies()
    {
        $this->cookies = array();
    }

    /**
     * Resets all set headers, cookies, and body
     */
    public function reset()
    {
        $this->body = null;
        $this->headers = array();
        $this->cookies = array();
        $this->statusCode = 200;
        $this->contentType = "text/html";
        $this->charset = "UTF-8";
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
            $this->setHeader('Content-Type', $this->contentType ."; charset=". $this->charset);
        else
            $this->setHeader('Content-Type', $this->contentType);

        // Send the rest of the headers
        foreach ($this->headers as $key => $value)
            $this->sendHeader($key, $value);

    }
}