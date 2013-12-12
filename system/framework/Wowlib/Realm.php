<?php
/* 
| -------------------------------------------------------------- 
| Realm Object
| --------------------------------------------------------------
|
| Author:       Wilson212
| Copyright:    Copyright (c) 2012, Plexis Dev Team
| License:      GNU GPL v3
|
*/
namespace System\Wowlib;

use System\Database\DbConnection;

class Realm // implements iRealm
{
    // Our Parent wowlib class and Database connection
    protected $DB;
    protected $parent;
    protected $config;

    protected $id;
    
    // Realm data array
    protected $data = array();
    
    // Array of columns
    protected $cols = array();

    /**
     * Characters Object
     * @var
     */
    protected $characters = null;

    /**
     * World Object
     * @var
     */
    protected $world = null;
    
/*
| ---------------------------------------------------------------
| Constructor
| ---------------------------------------------------------------
|
*/
    public function __construct($id, $data, $config, $Db)
    {
        // If the result is NOT false, we have a match, username is taken
        if(!is_array($data)) throw new \Exception('Realm Doesnt Exist');
        
        // Load the realm database connection
        $this->id = $id;
        $this->DB = $Db;
        $this->data = $data;
        $this->config = $config;
        $this->cols = $config['realmColumns'];
    }

    public function getWorld()
    {
        if($this->world === null)
        {
            // Fetch world DB data from database
            $Conn = \Plexis::Database();
            $data = $Conn->query("SELECT world_db FROM pcms_realms WHERE id=". $this->id)->fetchColumn();
            $data = unserialize($data);

            // Init Connection
            try
            {
                // Load database
                $Conn = new DbConnection(
                    $data["host"],
                    $data["port"],
                    $data["database"],
                    $data["username"],
                    $data["password"]
                );

                // $this->world = new World($this->config, $Conn);
            }
            catch(\DatabaseConnectError $e)
            {
                $this->world = false;
            }
        }

        return $this->world;
    }

    public function getCharacters()
    {
        if($this->characters === null)
        {
            // Fetch world DB data from database
            $Conn = \Plexis::Database();
            $data = $Conn->query("SELECT char_db FROM pcms_realms WHERE id=". $this->id)->fetchColumn();
            $data = unserialize($data);

            // Init Connection
            try
            {
                // Load database
                $Conn = new DbConnection(
                    $data["host"],
                    $data["port"],
                    $data["database"],
                    $data["username"],
                    $data["password"]
                );

                $this->characters = new Characters($this->config, $Conn);
            }
            catch(\Exception $e)
            {
                $this->characters = false;
            }
        }

        return $this->characters;
    }
    
/*
| ---------------------------------------------------------------
| Method: save()
| ---------------------------------------------------------------
|
| This method saves the current realm data in the database
|
| @Retrun: (Bool): If the save is successful, returns TRUE
|
*/ 
    public function save()
    {
        // Fetch our table name, and ID column for the query
        $table = $this->config['realmTable'];
        $col = $this->cols['id'];
        return ($this->DB->update($table, $this->data, "`{$col}`= ".$this->data[$col]) !== false);
    }
    
/*
| ---------------------------------------------------------------
| Method: getId()
| ---------------------------------------------------------------
|
| This method returns the realms id
|
| @Return (Int)
|
*/
    public function getId()
    {
        // Fetch our column name
        $col = $this->cols['id'];
        return (int) $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getName()
| ---------------------------------------------------------------
|
| This method returns the realms name
|
| @Return (String)
|
*/
    public function getName()
    {
        // Fetch our column name
        $col = $this->cols['name'];
        return $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getAddress()
| ---------------------------------------------------------------
|
| This method returns the realms address
|
| @Return (String)
|
*/
    public function getAddress()
    {
        // Fetch our column name
        $col = $this->cols['address'];
        return $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getPort()
| ---------------------------------------------------------------
|
| This method returns the realms port
|
| @Return (String)
|
*/
    public function getPort()
    {
        // Fetch our column name
        $col = $this->cols['port'];
        return $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getType()
| ---------------------------------------------------------------
|
| This method returns the realms type
|
| @Return (Int)
|
*/
    public function getType()
    {
        // Fetch our column name
        $col = $this->cols['type'];
        return (int) $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getPopulation()
| ---------------------------------------------------------------
|
| This method returns the realms population as a float value
|
| @Return (Float) - 0.5 for low, 1.0 for medium, 2.0 for High
|
*/
    public function getPopulation()
    {
        // Fetch our column name
        $col = $this->cols['population'];
        return (float) $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getBuild()
| ---------------------------------------------------------------
|
| This method returns the realms game build
|
| @Return (Int)
|
*/
    public function getBuild()
    {
        // Fetch our column name
        $col = $this->cols['gamebuild'];
        return (int) $this->data[$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getStatus()
| ---------------------------------------------------------------
|
| This method returns the realms online status
|
| @Return (Int) - 1 for online, 0 for offline
|
*/
    public function getStatus($timeout = 3)
    {
        // Fetch our column names
        $add = $this->cols['address'];
        $port = $this->cols['port'];
        
        // Check status
        $status = @fsockopen($this->data[$add], $this->data[$port], $err, $estr, $timeout);
        return (int)($handle !== false);
    }

    public function getUptime()
    {
        // Get our table and column names
        $table = $this->config['uptimeTable'];
        $rid = $this->config['uptimeColumns']['realmId'];
        $cid = $this->config['uptimeColumns']['startTime'];
        if($table == false) return false;

        // Grab Realms
        $query = "SELECT MAX(`{$cid}`) FROM `{$table}` WHERE `{$rid}`=?";
        $result = $this->DB->query( $query, array($this->id) )->fetchColumn();
        return (time() - $result);
    }
    
/*
| ---------------------------------------------------------------
| Method: setName()
| ---------------------------------------------------------------
|
| This method sets the realms name
|
| @Param: (String) $name - The realms new name
| @Return (Bool)
|
*/
    public function setName($name)
    {
        if(!is_string($name) || strlen($name) > 32) return false;
        $this->data[ $this->cols['name'] ] = $name;
        return true;
    }
    
/*
| ---------------------------------------------------------------
| Method: setAddress()
| ---------------------------------------------------------------
|
| This method sets the realms address
|
| @Param: (String) $address - The realms new address
| @Return (Bool)
|
*/
    public function setAddress($address)
    {
        $this->data[ $this->cols['address'] ] = $address;
        return true;
    }
    
/*
| ---------------------------------------------------------------
| Method: setPort()
| ---------------------------------------------------------------
|
| This method returns the realms port
|
| @Param: (Int) $port - The realms new port number
| @Return (Bool)
|
*/
    public function setPort($port)
    {
        if(!is_numeric($port)) return false;
        $this->data[ $this->cols['port'] ] = (int) $port;
        return true;
    }
    
/*
| ---------------------------------------------------------------
| Method: setType()
| ---------------------------------------------------------------
|
| This method returns the realms type
|
| @Return (Bool) Returns false of the type passed is invalid
|
*/
    public function setType($icon)
    {
        $i = (int) $icon;
        if($i != 0 || $i != 1 || $i != 4 || $i != 6 || $i != 8) return false;
        $this->data[ $this->cols['type'] ] = $i;
        return true;
    }
}