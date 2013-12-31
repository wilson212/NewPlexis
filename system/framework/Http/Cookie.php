<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Http/Cookie.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Http;

/**
 * Cookie Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Http
 */
class Cookie
{
	/**
	 * The name of the cookie
	 * @var string
	 */
	protected $name;

	/**
	 * The value of the cookie
	 * @var string
	 */
	protected $value;

	/**
	 * The Unix timestamp in which this cookie expires
	 * @var bool|int
	 */
	protected $expires;

	/**
	 * The domain path the cookie is valid for
	 * @var string
	 */
	protected $path;

	/**
	 * The domain name for this cookie
	 * @var bool|string
	 */
	protected $domain = false;

	/**
	 * Constructor
	 *
	 * @param string $name The name of the cookie
	 * @param string|int $value The value of the cookie
	 * @param int|bool $expires The Unix timestamp in which this cookie will expire
	 * @param string $path The domain path, for whom can use this cookie
	 */
	public function __construct($name, $value, $expires = false, $path = '/')
	{
		$this->name = $name;
		$this->value = $value;
		$this->expires = ($expires === false || !is_numeric($expires)) ? time() + 31536000 : $expires;
		$this->path = $path;
	}

	/**
	 * Returns the name of this cookie
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the value of this cookie
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Returns the Unix timestamp in which this cookie will expire
	 *
	 * @return int
	 */
	public function getExpireTime()
	{
		return $this->expires;
	}

	/**
	 * Returns the path, for whom can use this cookie
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns the domain name for the cookie
	 *
	 * @return string|bool Returns false if there is no domain set
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Sets the domain associated with this cookie
	 *
	 * @param string $domain The domain name
	 *
	 * @return string
	 */
	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	/**
	 * Sets the domain path, for whom can use this cookie
	 *
	 * @param $path
	 *
	 * @return void
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Sets a cookie to be sent in the {@link WebResponse} object
	 *
	 * The response object must be set in order for the cookie to be set
	 *
	 * @param string $name The name of the cookie
	 * @param string|int $value The value of the cookie
	 * @param int|bool $expires The Unix timestamp in which this cookie will expire
	 * @param string $path The domain path, for whom can use this cookie
	 *
	 * @return void
	 */
	public static function Set($name, $value, $expires, $path = '/')
	{
		$_COOKIE[$name] = $value;
		$Cookie = new Cookie($name, $value, $expires, $path);
		WebRequest::GetInitial()->getResponse()->setCookie($Cookie);
	}

	/**
	 * Deletes a cookie
	 *
	 * The response object must be set in order for the cookie to be deleted
	 *
	 * @param string $name The name of the cookie
	 */
	public static function Delete($name)
	{
		unset($_COOKIE[$name]);
		$Cookie = new Cookie($name, null, time() - 3600);
		WebRequest::GetInitial()->getResponse()->setCookie($Cookie);
	}

	/**
	 * Returns whether a cookie is set by name
	 *
	 * @param string $name The name of the cookie
	 *
	 * @return bool
	 */
	public static function Exists($name)
	{
		return isset($_COOKIE[$name]);
	}

	/**
	 * @return int|string The value of this cookie
	 */
	public function __toString()
	{
		return $this->value;
	}
}