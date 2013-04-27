<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Configuration/ConfigManager.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Configuration;
use System\Configuration\Drivers\IniConfig;
use System\Configuration\Drivers\PhpConfig;
use System\Configuration\Drivers\XmlConfig;

/**
 * Class ConfigManager
 *
 * @package     System
 * @subpackage  Configuration
 */
class ConfigManager
{
    /** PHP Configuration File */
    const TYPE_PHP = 0;

    /** INI Configuration File */
	const TYPE_INI = 1;

    /** XML Configuration File */
	const TYPE_XML = 2;

    /**
     * Loads a config file, and returns the Config object
     *
     * @param string $filename The full path to the config file
     * @param int $configType The config type (See class constants TYPE_*)
     *
     * @throws \FileNotFoundException Thrown if the config file doesn't exist
     * @throws \Exception
     *
     * @return \System\Configuration\ConfigFile
     */
    public static function Load($filename, $configType = self::TYPE_PHP)
	{
        // Include file and add it to the $files array
        if(!file_exists($filename))
            throw new \FileNotFoundException("Config file '{$filename}' does not exist!");

        switch($configType)
        {
            case self::TYPE_PHP:
                return new PhpConfig($filename);
            case self::TYPE_INI:
                return new IniConfig($filename);
            case self::TYPE_XML:
                return new XmlConfig($filename);
            default:
                throw new \Exception("Invalid config type.");
        }
	}
}