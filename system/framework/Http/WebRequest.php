<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework//WebRequest.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Http;
use System\Collections\Dictionary;
use System\Routing\Router;
use System\Security\XssFilter;
use System\Utils\Validator;

/**
 * WebRequest Class
 *
 * @author      Steven Wilson
 * @package     System
 * @subpackage  Http
 */
class WebRequest
{
    /** HTTP POST method */
    const POST = "POST";

    /** HTTP GET method */
    const GET = "GET";

    /** HTTP DELETE method */
    const DELETE = "DELETE";

    /** HTTP PUT method */
    const PUT = "PUT";

    /**
     * An array of parent request objects
     * @var array
     */
    protected static $Requests = array();

    /**
     * The remote IP address connected to this request
     * @var string
     */
    protected static $clientIp;

    /**
     * Current protocol
     * @var string
     */
    protected static $protocol = 'http';

    /**
     * the site's base url (the root of the website)
     * @var string
     */
    protected static $baseurl;

    /**
     * Http domain name (no trailing paths after the .com)
     * @var string
     */
    protected static $domain;

    /**
     * The web root is the trailing path after the domain name.
     * The base url is the Domain name, plus the webroot
     * @var string
     */
    protected static $webroot;

    /**
     * The URI for this request
     * @var string
     */
    protected $uri;

    /**
     * The HTTP method for this request
     * @var string
     */
    protected $method;

    /**
     * The POST data for this request
     * @var \System\Collections\Dictionary
     */
    protected $postData;

    /**
     * The GET data array for this request
     * @var \System\Collections\Dictionary
     */
    protected $queryString;

    /**
     * The cookies to be used in this request
     * @var \System\Collections\Dictionary
     */
    protected $cookieData;

    /**
     * Indicates if this is an Ajax request
     * @var bool
     */
    protected $isAjax;

    /**
     * The request position
     * @var int
     */
    protected $position;

    /**
     * The HTTP WebResponse for this request
     * @var WebResponse
     */
    protected $response;

    /**
     * Constructor
     *
     * @param string $uri The URI for this request
     * @param string $method The HTTP method for this request
     *
     * @throws \InvalidArgumentException Thrown if the setting method
     *   is not a valid HTTP method
     */
    public function __construct($uri, $method = self::GET)
    {
        // Set request position, and add this request to the list
        $this->position = count(self::$Requests);
        self::$Requests[] = $this;

        // Set method and URI, and response
        $this->uri = $uri;
        $this->method($method);
        $this->response = new WebResponse($this);

        // Init default POST, GET and Cookie data
        $this->postData = new Dictionary();
        $this->queryString = new Dictionary();
        $this->cookieData = new Dictionary();

        // Set if we are an Ajax request
        $this->isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

        // Set statics if this is the Initial Request
        if(empty(self::$domain))
        {
            // Define our domain and webroot
            self::$domain = rtrim($_SERVER['HTTP_HOST'], '/');
            self::$webroot = dirname( $_SERVER['PHP_SELF'] );

            // Detect our protocol
            if(isset($_SERVER['HTTPS']))
            {
                $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                self::$protocol = ($secure) ? 'https' : 'http';
            }
            else
                self::$protocol = 'http';

            // build our base url
            $site_url = preg_replace('~(/{2,})~', '/', strtolower(self::$domain .'/'. self::$webroot));
            self::$baseurl = str_replace( '\\', '', self::$protocol .'://' . rtrim($site_url, '/') );
        }
    }

    /**
     * Indicates whether this request is an HMVC request, or the
     *   main request from the browser
     *
     * @return bool
     */
    public function isHmvc()
    {
        return ($this->position > 0);
    }

    /**
     * Sets or fetches whether this request be treated as an ajax request
     *
     * @param bool|null $setAs If set, indicates whether this request be treated as
     *   an Ajax request
     *
     * @return $this|bool
     */
    public function isAjax($setAs = null)
    {
        if($setAs === null)
            return $this->isAjax;
        else
            $this->isAjax = (bool) $setAs;

        return $this;
    }

    public function getBaseUrl()
    {
        return self::$baseurl;
    }

    public function getDomain()
    {
        return self::$domain;
    }

    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns the parent request, or false if this object is the parent request.
     *
     * @return bool|WebRequest
     */
    public function getParent()
    {
        return ($this->position == 0) ? false : self::$Requests[$this->position - 1];
    }

    /**
     * Returns an array of child requests
     *
     * @return WebRequest[]
     */
    public function getChildren()
    {
        return array_slice(self::$Requests, $this->position + 1);
    }

    /**
     * Returns the Response object for this request
     *
     * @return WebResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets or fetches the current HTTP method for this request
     *
     * @param null|string $setAs The http method of this request
     *  (See class constants GET, POST, PUT, DELETE)
     *
     * @throws \InvalidArgumentException Thrown if the setting method
     *   is not a valid HTTP method
     *
     * @return string|$this If a method is being set, returns this object,
     *   otherwise returns the current set method.
     */
    public function method($setAs = null)
    {
        // If we are fetching the current value
        if(empty($setAs))
            return $this->method;

        // Make sure the setting value is valid
        if($setAs != self::GET && $setAs != self::POST && $setAs != self::PUT && $setAs != self::DELETE)
            throw new \InvalidArgumentException("Invalid HTTP method specified \"{$setAs}\"");

        // Set the new value :)
        $this->method = $setAs;
        return $this;
    }

    /**
     * Sets of fetches POST variables for this request object
     *
     * @param string|string[] $name The name of the post item, or an array
     *   of key => value
     * @param string $value The value of $name
     *
     * @return $this|Dictionary
     */
    public function post($name = null, $value = null)
    {
        // If name is null, return data array
        if($name == null)
            return $this->postData;
        // If name is an array, set each $name as a key/value pair
        elseif(is_array($name))
            foreach($name as $key => $v)
                $this->postData[$key] = $v;
        // If name is valid, and value is null, return the value of name
        elseif($value == null)
            return ($this->postData->containsKey($name)) ? $this->postData[$name] : null;
        // Otherwise, set the value of name
        else
            $this->postData[$name] = $value;

        return $this;
    }

    /**
     * Sets of fetches QueryString (GET) variables for this request object
     *
     * @param string|string[] $name The name of the query item, or an array
     *   of key => value
     * @param string $value The value of $name
     *
     * @return $this|Dictionary
     */
    public function query($name = null, $value = null)
    {
        // If name is null, return data array
        if($name == null)
            return $this->queryString;
        // If name is an array, set each $name as a key/value pair
        elseif(is_array($name))
            foreach($name as $key => $v)
                $this->queryString[$key] = $v;
        // If name is valid, and value is null, return the value of name
        elseif($value == null)
            return ($this->queryString->containsKey($name)) ? $this->queryString[$name] : null;
        // Otherwise, set the value of name
        else
            $this->queryString[$name] = $value;

        return $this;
    }

    /**
     * Sets of fetches Cookies for this request object
     *
     * @param string|string[] $name The name of the cookie, or an array
     *   of key => value
     * @param string $value The value of $name
     *
     * @return $this|Dictionary
     */
    public function cookie($name = null, $value = null)
    {
        // If name is null, return data array
        if($name == null)
            return $this->cookieData;
        // If name is an array, set each $name as a key/value pair
        elseif(is_array($name))
            foreach($name as $key => $v)
                $this->cookieData[$key] = $v;
        // If name is valid, and value is null, return the value of name
        elseif($value == null)
            return ($this->cookieData->containsKey($name)) ? $this->cookieData[$name] : null;
        // Otherwise, set the value of name
        else
            $this->cookieData[$name] = $value;

        return $this;
    }

    /**
     * Executes the request, and returns the response
     *
     * @return WebResponse
     */
    public function execute()
    {
        return Router::Execute($this);
    }

    // Static Methods //

    /**
     * Returns the Initial (Main) Request object.
     *
     * If the initial WebRequest has not been created, it will be
     * created when this method is called automatically.
     *
     * @return WebRequest
     */
    public static function GetInitial()
    {
        if( isset(self::$Requests[0]) )
            return self::$Requests[0];

        // Create a new Web request
        $Request = new WebRequest( self::DetectUri() );

        // Set method
        try {
            if(isset($_SERVER['REQUEST_METHOD']))
                $Request->method(strtoupper($_SERVER['REQUEST_METHOD']));
            elseif(false !== ($env = getenv('REQUEST_METHOD')))
                $Request->method(strtoupper($env));
            else
                $Request->method( (!empty($_POST) ? self::POST : self::GET) );
        }
        catch(\InvalidArgumentException $e) {}

        // Set GET, POST, and Cookies
        $Request->post($_POST);
        $Request->query($_GET);
        $Request->cookie($_COOKIE);
        return $Request;
    }

    /**
     * Determines the full Query string for the initial request
     *
     * @return string
     */
    protected static function DetectUri()
    {
        // Load the plexis config
        $Config = \Plexis::GetConfig();

        // Process the site URI
        if($Config["enable_query_strings"])
        {
            // Define our needed vars
            $m_param = $Config["module_param"];
            $c_param = $Config["controller_param"];
            $a_param = $Config["action_param'"];
            $uri = '';

            // Make sure we have a module at least
            if(isset($_GET[$m_param]))
            {
                $uri = $_GET[$m_param];

                // Get our controller
                if(isset($_GET[$c_param]))
                    $uri .= '/'. $_GET[$c_param];

                // Get our action
                if(isset($_GET[$a_param]))
                    $uri .= '/'. $_GET[$a_param];

                // Clean the query string
                $qs = explode('&', $_SERVER['QUERY_STRING']);
                foreach($qs as $string)
                {
                    // Convert this segment to an array
                    $string = explode('=', $string);

                    // Don't add the controller / action twice ;)
                    if($string[0] == $m_param || $string[0] == $c_param || $string[0] == $a_param)
                        continue;

                    // Append the uri variable
                    $uri .= '/'. $string[1];
                }
            }

            return $uri;
        }
        else
            // Get our current url, which is passed on by the 'url' param
            return (isset($_GET['uri'])) ? $_GET['uri'] : '';
    }

    /**
     * Returns the Remote connected IP address
     *
     * @return string The validated remote IP address. Returns 0.0.0.0 if
     *   the IP address could not be determined
     */
    public static function ClientIp()
    {
        // Return it if we already determined the IP
        if(empty(self::$clientIp))
        {
            // Check to see if the server has the IP address
            if(isset($_SERVER['HTTP_CLIENT_IP']) && Validator::IsValidIp($_SERVER['HTTP_CLIENT_IP']))
            {
                self::$clientIp = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                // HTTP_X_FORWARDED_FOR can be an array og IPs!
                $ips = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach($ips as $ip)
                {
                    if(Validator::IsValidIp($ip))
                    {
                        self::$clientIp = $ip;
                        break;
                    }
                }
            }
            elseif(isset($_SERVER['HTTP_X_FORWARDED']) && Validator::IsValidIp($_SERVER['HTTP_X_FORWARDED']))
            {
                self::$clientIp = $_SERVER['HTTP_X_FORWARDED'];
            }
            elseif(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && Validator::IsValidIp($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            {
                self::$clientIp = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
            }
            elseif(isset($_SERVER['HTTP_FORWARDED_FOR']) && Validator::IsValidIp($_SERVER['HTTP_FORWARDED_FOR']))
            {
                self::$clientIp = $_SERVER['HTTP_FORWARDED_FOR'];
            }
            elseif(isset($_SERVER['HTTP_FORWARDED']) && Validator::IsValidIp($_SERVER['HTTP_FORWARDED']))
            {
                self::$clientIp = $_SERVER['HTTP_FORWARDED'];
            }
            elseif(isset($_SERVER['HTTP_VIA']) && Validator::IsValidIp($_SERVER['HTTP_VIA']))
            {
                self::$clientIp = $_SERVER['HTTP_VIA'];
            }
            elseif(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']))
            {
                self::$clientIp = $_SERVER['REMOTE_ADDR'];
            }

            // If we still have a false IP address, then set to 0's
            if(empty(self::$clientIp)) self::$clientIp = '0.0.0.0';
        }
        return self::$clientIp;
    }

    /**
     * Returns the referring website url
     *
     * @return string
     */
    public static function Referer()
    {
        $ref = null;
        if(isset($_SERVER['HTTP_X_FORWARDED_HOST']))
            $ref = $_SERVER['HTTP_X_FORWARDED_HOST'];
        elseif(isset($_SERVER['HTTP_REFERER']))
            $ref = $_SERVER['HTTP_REFERER'];

        return $ref;
    }
}