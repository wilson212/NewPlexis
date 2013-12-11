<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Configuration/ConfigBase.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Configuration;

/**
 * Configuration Abstract Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Configuration
 */
abstract class ConfigBase implements \IteratorAggregate, \ArrayAccess, \Countable,  \Serializable
{
    /**
     * A dictionary container for all config variables
     * @var mixed[]
     */
    protected $variables;

    /**
     * The full file path to the config file
     * @var string
     */
    protected $filePath;

    /**
     * Loads the files variables into the $variables array
     *
     * @param string $_filepath The full file path to the config file
     */
    public abstract function __construct($_filepath);

    /**
     * Returns the specified config item
     *
     * @param string $key The config item key
     * @param mixed $defaultReturn If the config item doesn't exist, this value is returned
     *
     * @return mixed|null
     */
    public function get($key, $defaultReturn = null)
    {
        // Check if this is a multi-dimensional array
        if(strpos($key, '.') !== false)
        {
            $args = explode('.', $key);
            $count = count($args);
            $buffer = $this->variables;

            for($i = 0; $i < $count; $i++)
            {
                if(!isset($buffer[$args[$i]]))
                    return $defaultReturn;
                elseif($i == $count - 1)
                    return $buffer[$args[$i]];
                else
                    $buffer = $buffer[$args[$i]];
            }
        }

        // Just a simple 1 stack array
        else
        {
            // Check if variable exists in $array
            if(array_key_exists($key, $this->variables))
                return $this->variables[$key];
        }

        return $defaultReturn;
    }

    public function set($key, $value)
    {
        // Check if this is a multi-dimensional array
        if(strpos($key, '.') !== false)
        {
            $args = explode('.', $key);
            $count = count($args);
            $s_key = '';

            // Loop though each level (period or "element")
            for($i = 0; $i < $count; $i++)
            {
                // add quotes if the argument is a string
                if(!is_numeric($args[$i]))
                    $s_key .= "['$args[$i]']";
                else
                    $s_key .= "[$args[$i]]";
            }

            // Check if variable exists in $val
            eval('$this->variables'. $s_key .' = $value;');
        }
        else
            $this->variables[$key] = $value;
    }

    public abstract function save();

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