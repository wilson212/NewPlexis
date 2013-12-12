<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Wowlib/Driver.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Wowlib;

/**
 * Driver Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Wowlib
 */
final class Driver
{
    /**
     * The driver $Config array
     * @var array
     */
    protected $config = array();

    /**
     * The driver version (not supported yet)
     * @var
     */
    protected $version;

    /**
     * Constructor
     *
     * @param string $filePath The full path to the driver file
     *
     * @throws \Exception Thrown if the driver file cannot be located
     */
    public function __construct($filePath)
    {
        // First, we must load the emulator config
        $config = array();

        // If extension doesn't exist, return false
        if( !file_exists( $filePath ) )
            throw new \Exception('Config file: '. $filePath .' not found');
        require $filePath;

        $this->config = $config;
    }

    /**
     * Returns the driver item
     *
     * @param $item
     *
     * @return bool|string
     */
    public function get($item)
    {
        // Make sure the config key exists
        if(!isset($this->config[$item])) return false;

        return $this->config[$item];
    }

    /**
     * Returns the database column name for a table by the column's ID
     *
     * @param string $tableKey The Table Key (account, character) etc
     * @param string $colKey The columns ID
     *
     * @return bool|string Returns false if the Table doesn't exist or isn't supported
     *      by this drivers version.
     */
    public function getColumnById($tableKey, $colKey)
    {
        // Make sure the config key exists
        if(!isset($this->config["{$tableKey}Columns"][$colKey])) return false;

        return $this->config["{$tableKey}Columns"][$colKey];
    }

    /**
     * Returns all the database columns for the specified table key
     *
     * @param string $tableKey The Table Key (account, character) etc
     *
     * @return mixed[]
     */
    public function getColumns($tableKey)
    {
        return $this->config["{$tableKey}Columns"];
    }

    /**
     * Returns the table name for the specified table key
     *
     * @param string $tableKey The Table Key (account, character) etc
     *
     * @return bool
     */
    public function getTableById($tableKey)
    {
        // Make sure the config key exists
        if(!isset($this->config["{$tableKey}Table"])) return false;

        return $this->config["{$tableKey}Table"];
    }
}