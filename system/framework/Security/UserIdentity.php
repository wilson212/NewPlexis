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
final class UserIdentity implements \IteratorAggregate, \ArrayAccess, \Countable,  \Serializable
{
    /**
     * The user's ID... if 0, means user is a guest
     * @var int
     */
    protected $userId = 0;

    /**
     * Indicates whether this user identity is the website's root admin (Owner)
     * @var bool
     */
    protected $isOwner = false;

    /**
     * User variables
     * @var mixed[]
     */
    protected $variables = array();

    /**
     * Constructor
     *
     * @param int $userId The user's ID we are loading an identity for. Set as 0 for guest
     */
    public function __construct($userId = 0)
    {
        if($userId == 0)
        {
            // Guest Vars
            $this->variables = array(
                'username' => 'Guest'
            );
        }
    }

    /**
     * This method is used to return whether the user have a specific permission
     *
     * @param string $operation The name of the operation we are checking
     *   permissions for
     * @return bool
     */
    public function checkAccess($operation)
    {
        return true;
    }

    /**
     * Returns whether this user identity is a guest
     *
     * @return bool
     */
    public function isGuest()
    {
        return ($this->userId == 0);
    }

    /**
     * Indicates whether this user identity is the website's root admin (Owner)
     * @return bool
     */
    public function isOwner()
    {
        return $this->isOwner;
    }

    // === Interface / Abstract Methods === //

    /**
     * Returns the number of items in the list
     * This method is required by the interface Countable.
     *
     * @return int
     */
    public function count()
    {
        return count($this->variables);
    }

    /**
     * Returns whether the specified item key exists in the container
     * This method is required by the interface ArrayAccess.
     *
     * @param string $key The item key to check for
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->variables);
    }

    /**
     * Returns the item value of the specified key.
     * This method is required by the interface ArrayAccess.
     *
     * @param string $key The item key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->variables[$key];
    }

    /**
     * Sets the item with the specified key.
     * This method is required by the interface ArrayAccess.
     *
     * @param string $key The item key to set
     * @param mixed $value The item value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->variables[$key] = $value;
    }

    /**
     * Removes the item with the specified key.
     * This method is required by the interface ArrayAccess.
     *
     * @param string $key The item key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->variables[$key]);
    }

    /**
     * Serializes the data, and returns it.
     * This method is required by the interface Serializable.
     *
     * @return string The serialized string
     */
    public function serialize()
    {
        return serialize($this->variables);
    }

    /**
     * Unserializes the data, and sets up the storage in this container
     * This method is required by the interface Serializable.
     *
     * @param string $data
     *
     * @return void
     */
    public function unserialize($data)
    {
        $this->variables = unserialize($data);
    }

    /**
     * Returns the ArrayIterator of this object
     * This method is required by the interface IteratorAggregate.
     *
     * @return string The serialized string
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->variables);
    }
}