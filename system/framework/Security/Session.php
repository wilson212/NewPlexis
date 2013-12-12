<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Security/Session.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Security;
use System\Wowlib\Account;

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

    /**
     * Uses the Servers auth database to login a user
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public static function Login($username, $password)
    {
        // Use wowlib to determine if user pass is correct
        $Server = \Plexis::GetServer();
        if($Server == false)
        {
            // We need to do something about this... Maybe an error message?
        }
        else
        {
            if(($Acct = $Server->login($username, $password)) instanceof Account)
            {
                // Load user from plexis database, assign a new UserIdentity
                self::$UserIdentity = new UserIdentity($Acct->getId());
                return true;
            }
        }

        return false;
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
    public static function Logout($immediately = true)
    {
        // Remove session cookie and DB entry, and set to guest

        // Allow user session for this page load?
        if($immediately) self::$UserIdentity = new UserIdentity(0);
    }
}