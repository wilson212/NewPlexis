<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Configuration/Drivers/PhpConfig.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Configuration\Drivers;
use System\Configuration\ConfigBase;

/**
 * PhpConfig Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Configuration
 */
class PhpConfig extends ConfigBase
{
    /**
     * Constructor
     *
     * @param string $_filepath The config filepath
     *
     * @throws \FileNotFoundException Thrown if the config file does not exist
     */
    public function __construct($_filepath)
    {
        // Include file and add it to the $files array
        if(!file_exists($_filepath))
            throw new \FileNotFoundException("Config file '{$_filepath}' does not exist!");

        // Set filepath variable
        $this->filePath = $_filepath;
        unset($_filepath);

        // Get defined variables
        include( $this->filePath );
        $vars = get_defined_vars();

        // Add the variables to the $data[$name] array
        foreach( $vars as $key => $val )
        {
            if($key != 'this')
                $this->variables[$key] = $val;
        }
    }

    public function save() {}
}