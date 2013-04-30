<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Security/UserIdentity.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Security;

/**
 * UserIdentity Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Security
 */
class UserIdentity
{
    /**
     * Indicates whether this user identity is a guest
     * @var bool
     */
    protected $isGuest = true;

    /**
     * @param string $operation The name of the operation we are checking
     *   permissions for
     */
    public function checkAccess($operation) {}

    /**
     * Returns whether this user identity is a guest
     *
     * @return bool
     */
    public function isGuest()
    {
        return $this->isGuest;
    }

    public function login($username, $password) {}
}