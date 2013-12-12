<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Security/Session.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Security;
use System\Http\Cookie;
use System\Http\WebRequest;
use System\Utils\LogWritter;
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

    /**
     * The session ID
     * @var int
     */
    protected static $sessionId = 0;

    /**
     * Initializes the user session by reading the session cookie, and
     * comparing the cookie data with the database data.
     */
    public static function Init()
    {
        // By default, we start as a guest
        self::$UserIdentity = new UserIdentity(0);

        // Use the database and session cookie to init a user identity
        $Log = LogWritter::Instance('debug');
        $Cookie = (isset($_COOKIE['session'])) ? $_COOKIE['session'] : null;
        $expireTime = (int) \Plexis::Config()->get('session_expire_time');

        // If our cookie is not empty, we process it
        if(!empty($Cookie))
        {
            // Read cookie data to get our token
            $Cookie = base64_decode( $Cookie );
            if($Cookie == false)
            {
                $Log->logWarning("[Auth] Invalid session cookie. Forcing logout");
                Cookie::Delete('session');
            }
            elseif(substr_count($Cookie, '::') == 1)
            {
                list($userid, $token) = explode('::', $Cookie);
                $userid = (int) $userid;
                $Log->logDebug("[Auth] Valid session cookie exists, found user id: {$userid}");

                // Get the database result
                $DB = \Plexis::Database();
                $query = "SELECT * FROM `pcms_sessions` WHERE `token` = ?";
                $session = $DB->query( $query, array($token) )->fetch();

                // Un serialize the user_data array
                if(is_array($session))
                {
                    // check users IP address to prevent cookie stealing
                    if( $session['ip_address'] != WebRequest::ClientIp() )
                    {
                        // Session time is expired
                        $Log->logDebug('[Auth] User IP address doesnt match the IP address of the session id. Forced logout');
                        Cookie::Delete('session');
                    }
                    elseif($session['expire_time'] < (time() - $expireTime))
                    {
                        // Session time is expired
                        $Log->logDebug('[Auth] User session expired, Forced logout');
                        Cookie::Delete('session');
                    }
                    else
                    {
                        // User is good and logged in
                        self::$UserIdentity = new UserIdentity($userid);
                        self::$sessionId = $session['token'];
                    }
                }
            }
            else
            {
                $Log->logWarning("[Auth] Invalid session cookie. Forcing logout");
                Cookie::Delete('session');
            }
            return;
        }
        else
        {
            $Log->logDebug("[Auth] No session cookie found.");
        }
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
            // Only if the login is successful, will $Acct be an Account object
            if(($Acct = $Server->login($username, $password)) instanceof Account)
            {
                // Load user from plexis database, assign a new UserIdentity
                self::$UserIdentity = new UserIdentity($Acct->getId(), $Acct);

                // Generate a completely random session id
                $time = microtime(1);
                $string = sha1(base64_encode(md5(utf8_encode( $time ))));
                self::$sessionId = substr($string, 0, 20);

                // set our session cookie
                $time = time();
                $expireTime = (int) \Plexis::Config()->get('session_expire_time');
                $data = array(
                    'token' => self::$sessionId,
                    'ip_address' => WebRequest::ClientIp(),
                    'expire_time' => ($time + $expireTime)
                );

                // Fetch database
                $DB = \Plexis::Database();

                // Insert session information
                $DB->insert('pcms_sessions', $data);

                // Update user with new session id
                $DB->update('pcms_accounts', array('last_seen' => date('Y-m-d H:i:s', $time)), "`id`=". $Acct->getId());

                // Set cookie
                $token = base64_encode($Acct->getId() .'::'. self::$sessionId);
                Cookie::Set('session', $token, ($time + $expireTime));

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
        if(self::$sessionId != 0)
        {
            // We are logged out now
            self::$sessionId = 0;

            // Remove session cookie
            Cookie::Delete('session');

            // Remove database session
            \Plexis::Database()->delete('pcms_sessions', "`token`='". self::$sessionId ."'");

            // Allow user session for this page load?
            if($immediately)
            {
                self::$UserIdentity = new UserIdentity(0);
            }
        }
    }
}