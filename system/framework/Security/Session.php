<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Security/Session.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Security;

/**
 * Session Management Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Security
 */
final class Session
{
    /**
     * The current session user identity
     * @var UserIdentity
     */
    protected static $UserIdentity;

    public static function Init()
    {
        // Use the database and session cookie to init a user identity
        self::$UserIdentity = new UserIdentity(0);
    }

    /**
     * Returns the current user identity for this session
     * @return UserIdentity
     */
    public static function GetUser()
    {
        return self::$UserIdentity;
    }

    public static function Login($username, $password)
    {
        // Use wowlib to determine if user pass is correct

        // Set the new user identity
    }

    /**
     * Forces a UserIdentity to logout of this session
     *
     * @param bool $immediately If set to true, the UserIdentity will be set to Guest now, and
     *      will be considered a guest for the rest of this page load. If set to false,
     *      the UserIdentity will remain logged in for the rest of this page load, and logged out
     *      on the following page load.
     *
     * @return void
     */
    public static function Logout($immediately  = true)
    {
        // Remove session cookie and DB entry, and set to guest

        // Allow user session for this page load?
        if($immediately) self::$UserIdentity = new UserIdentity(0);
    }
}