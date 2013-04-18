<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Config/Drivers/Config.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Config\Drivers;
use System\Collections\Dictionary;

/**
 * Config Abstract Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Config
 */
abstract class ConfigAbstract
{
    /**
     * A dictionary container for all config variables
     * @var \System\Collections\Dictionary
     */
    protected $variables;

    public function get($key)
    {
        return $this->variables[$key];
    }

    public function set($key, $value)
    {
        $this->variables[$key] = $value;
    }

    public abstract function save();
}