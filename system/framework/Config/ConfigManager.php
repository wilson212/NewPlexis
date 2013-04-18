<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Config/ConfigManager.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Config;

/**
 * Class ConfigManager
 *
 * @package     System
 * @subpackage  Config
 */
class ConfigManager
{
    /** PHP Config File */
    const TYPE_PHP = 0;

    /** INI Config File */
	const TYPE_INI = 1;

    /** XML Config File */
	const TYPE_XML = 2;
	
	public static function Load($filename, $configType = self::TYPE_PHP)
	{
	
	}
}