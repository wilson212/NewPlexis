<?php
/**
 * Plexis Content Management System
 *
 * @copyright   2011-2012, Plexis Dev Team
 * @license     GNU GPL v3
 * @package     Wowlib
 */
namespace System\Wowlib;
use System\Database\DbConnection;
use System\IO\Path;

class Server // implements iEmulator
{
    /**
     * @var DbConnection
     */
    protected $DB;

    /**
     * The emulator name
     * @var string
     */
    protected $emulator = '';
    
    /**
     * Array of found extensions
     */
    protected $ext = array();

    /**
     * Query Options
     * @var array
     */
    protected $queryConfig = array(
        'limit' => 50,
        'offset' => 0,
        'where' => '',
        'bind' => array(),
        'orderby' => '',
        'direction' => 'ASC'
    );

    /**
     * Driver config options
     * @var array
     */
    protected $config;

    /**
     * Loaded extensions
     * @var array
     */
    protected $loaded = array();

    /**
     * Constructor
     *
     * @param string $emu The emulator driver name
     * @param DbConnection $DB The realm database connection
     *
     * @throws \Exception
     */
    public function __construct($emu, $DB)
    {
        // Set local variables
        $this->DB = $DB;
        $this->emulator = $emu;
        $config = array();
        
        // First, we must load the emulator config
		$file = Path::Combine( SYSTEM_PATH, 'emulators', $emu, 'Driver.php' );
        
        // If extension doesnt exist, return false
        if( !file_exists( $file ) )
            throw new \Exception('Config file for emulator '. $emu .' not found');
        require $file;
        
        // Set config array
        $this->config = $config;
        
        // load additional extensions
        $afile = Path::Combine( SYSTEM_PATH, 'emulators', $emu, 'Account.php' );
        $rfile = Path::Combine( SYSTEM_PATH, 'emulators', $emu, 'Realm.php' );
        if(file_exists($afile) && !class_exists("\\$emu\\Account", false)) require $afile;
        if(file_exists($rfile) && !class_exists("\\$emu\\Realm", false)) require $rfile;
    }

    /**
     * Fetches a users account data from the database
     *
     * @param int|String $id The account ID or username
     *
     * @return bool|Account
     */
    public function fetchAccount($id)
    {
        // Build config, and our where statement
        $config = $this->queryConfig;
        $col = (is_int($id)) ? $this->config['accountColumns']['id'] : $this->config['accountColumns']['username'];
        $config['where'] = "`{$col}`='{$id}'";
        
        // Grab our account query, and execute it
        $query = $this->_buildQuery('A', $config);
        $row = $this->DB->query( $query )->fetch();
        if(!is_array($row)) return false;
        
        // Get our classname
        $class = "Wowlib\\{$this->emulator}\\Account";
        if(!class_exists($class, false)) $class = "Wowlib\\Account";
        
        // Try to load the class
        try {
            $account = new $class($row, $this);
        }
        catch(\Exception $e) {
            $account = false;
        }
        return $account;
    }

    /**
     * Lists an array of accounts from the realm database
     *
     * @param array $config An array of query configurations (See Documentation)
     *
     * @return Account[] An array of accounts
     */
    public function getAccountList($config = array())
    {
        // Merge Configs
        $config = array_merge($this->queryConfig, $config); 
        
        // Get our query prepared into a statement
        $statement = $this->prepare('A', $config);
        
        // Execute the statement
        $statement->execute();
        $online = array();
        
        // Get our classname
        $class = "Wowlib\\{$this->emulator}\\Account";
        if(!class_exists($class, false)) $class = "Wowlib\\Account";
        
        // Build an array of character objects
        while($row = $statement->fetch())
        {
            $online[] = new $class($row, $this);
        }
        
        return $online;
    }

    /**
     * Fetches a realm from the database, as well as providing access to Character
     * and world database features
     *
     * @param int $id The realm ID we are requesting the information from
     *
     * @return bool|Realm Returns the RealmId Object, or false on failure
     */
    public function getRealmById($id)
    {
        // If the realm doesn't support a realm list, use Plexis' List
        if(!$this->config['realmTable'])
        {
            return false;
        }
        else
        {
            // Build config, and our where statement
            $config = $this->queryConfig;
            $col = $this->config['realmColumns']['id'];
            $config['where'] = "`{$col}`={$id}";

            // Grab our realm query, and exeute it
            $query = $this->_buildQuery('R', $config);
            $row = $this->DB->query( $query )->fetch();
            if(!is_array($row)) return false;

            // Get our classname
            $class = "Wowlib\\{$this->emulator}\\Realm";
            if(!class_exists($class, false)) $class = "Wowlib\\Realm";

            // Try and build the realm object
            try {
                $realm = new $class($id, $row, $this->config, $this->DB);
            }
            catch (\Exception $e) {
                $realm = false;
            }
            return $realm;
        }
    }

    /**
     * Fetches the realm list from the realm database. If the realm
     * does not support a realm list (Arcemu), the realm list from the
     * Plexis database is returned instead.
     *
     * @param array $config An array of query configurations (See Documentation)
     *
     * @return Realm[]
     */
    public function getRealmlist($config = array())
    {
        // Make sure this emulator support realmlists!
        if(!$this->config['realmTable']) return array();
        
        // Merge Configs
        $config = array_merge($this->queryConfig, $config);
        
        // Sort by ID by default
        if($config['orderby'] == '') 
            $config['orderby'] = $this->config['realmColumns']['id'];
        
        // Get our query prepared into a statement
        $statement = $this->prepare('R', $config);
        
        // Execute the statement
        $statement->execute();
        
        // Get our classname
        $class = "Wowlib\\{$this->emulator}\\Realm";
        if(!class_exists($class, false)) $class = "Wowlib\\Realm";
        
        // Build the array of realm objects
        $realms = array();
        while($row = $statement->fetch())
        {
            $realms[] = new $class($row, $this);
        }
        
        return $realms;
    }

    /**
     * Creates a new account
     *
     * @param string $username
     * @param string $password
     * @param string  $email
     * @param string $ip
     *
     * @return bool|int
     */
    public function createAccount($username, $password, $email = NULL, $ip = '0.0.0.0')
    {
        // Make sure the username doesnt exist, just incase the script didnt check yet!
        if($this->accountExists($username)) return false;
        
        // SHA1 the password
        $user = strtoupper($username);
        $pass = strtoupper($password);
        $shap = sha1($user.':'.$pass);
        
        // Get our column names
        $cols = $this->config['accountColumns'];
        
        // Build our tables and values for Database insertion
        $data = array(
            "{$cols['username']}" => $username, 
            "{$cols['email']}" => $email, 
        );
        
        // Condition based columns
        if($cols['password']) $data[ $cols['password'] ] = $password;
        if($cols['shaPassword']) $data[ $cols['shaPassword'] ] = $shap;
        if($cols['lastIp']) $data[ $cols['lastIp'] ] = $ip;
        
        // If we have an affected row, then we return TRUE
        return ($this->DB->insert("account", $data) > 0) ? $this->DB->lastInsertId() : false;
    }

    /**
     * This method takes a username and password, and verifies them against
     * the realm database, returning whether the login was successful
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function validate($username, $password)
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $cols = $this->config['accountColumns'];
        $passcol = ($cols['shaPassword'] != false) ? $cols['shaPassword'] : $cols['password'];
        
        // Load the users info from the Realm DB
        $query = "SELECT `{$cols['id']}` AS `id`, `{$passcol}` AS `password` FROM `{$table}` WHERE `{$cols['username']}`=?";
        $result = $this->DB->query( $query, array($username) )->fetchRow();
        
        // Make sure the username exists!
        if(!is_array($result)) return false;
        
        // SHA1 the password check
        if($cols['shaPassword'] != false && strlen($result['password']) == 40)
        {
            $user = strtoupper($username);
            $pass = strtoupper($password);
            $password = sha1($user.':'.$pass);
            
            // If the result was false, then username is no good. Also match passwords.
            return ( strtolower($result['password']) == $password );
        }
        
        return ( $result['password'] == $password );
    }

    /**
     * This method attempts to verify account credentials, and returns
     * the users account object upon success.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool|Account
     */
    public function login($username, $password)
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $columns = $this->config['accountColumns'];
        $passcol = ($columns['shaPassword'] != false) ? $columns['shaPassword'] : $columns['password'];
        
        // Prepare the column names
        $cols = "`". implode('`, `', $columns) ."`";
        
        // Load the users info from the Realm DB
        $query = "SELECT {$cols} FROM `{$table}` WHERE `{$columns['username']}`=?";
        $result = $this->DB->query( $query, array($username) )->fetch();
        if(!is_array($result)) return false;
        
        // SHA1 the password check
        if($cols['shaPassword'] != false && strlen($result[$passcol]) == 40)
        {
            $user = strtoupper($username);
            $pass = strtoupper($password);
            $password = sha1($user.':'.$pass);
            
            // Match the SHA passwords
            if( strtolower($result[$passcol]) == $password ) goto Account;
        }
        else
        {
            if( $result[$passcol] == $password ) goto Account;
        }
        
        // Return 0 if the passwords were invalid
        return false;
        
        Account:
        {
            // Get our classname
            $class = "\\{$this->emulator}\\Account";
            if(!class_exists($class, false)) $class = "\\System\\Wowlib\\Account";
            return new $class($result, $this);
        }
    }

    /**
     * Determines if an account exists or not
     *
     * @param int|string $id The account ID or username we are checking for
     *
     * @return bool
     */
    public function accountExists($id)
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $colid = $this->config['accountColumns']['id'];
        $username = $this->config['accountColumns']['username'];
        
        // Check the Realm DB for this username / account ID
        if(is_int($id))
            $query = "SELECT `{$username}` FROM `{$table}` WHERE `{$colid}`=?";
        else
            $query = "SELECT `{$colid}` FROM `{$table}` WHERE `{$username}` LIKE ? LIMIT 1";

        // If the result is NOT false, we have a match, username is taken
        $res = $this->DB->query( $query, array($id) )->fetchColumn();
        return ($res !== false);
    }

    /**
     * Indicates whether an account with the specified email exists
     *
     * @param string $email
     *
     * @return bool
     */
    public function emailExists($email)
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $colid = $this->config['accountColumns']['email'];
        $id = $this->config['accountColumns']['id'];
        
        // Check the Realm DB for this username
        $query = "SELECT `{$id}` FROM `{$table}` WHERE `{$colid}`=?";
        $res = $this->DB->query( $query, array($email) )->fetchColumn();
        
        // If the result is NOT false, we have a match, username is taken
        return ($res !== false);
    }

    /**
     * Checks the realm database, and returns whether the account is banned
     *
     * @param int $account_id
     *
     * @return bool
     */
    public function accountBanned($account_id)
    {
        // Get our table and column names
        $table = $this->config['bannedTable'];
        $bannedCond = $this->config['conditionIfBanned'];
        $id = $this->config['bannedColumns']['accountId'];
        
        // Build the query
        $query = "SELECT COUNT({$id}) FROM `{$table}` WHERE {$bannedCond} AND `{$id}`=?";
        $check = $this->DB->query( $query, array($account_id) )->fetchColumn();
        return ($check !== false && $check > 0) ? true : false;
    }

    /**
     * Checks the realm database, and returns whether the specified
     * IP address is banned
     *
     * @param string $ip
     *
     * @return bool
     */
    public function ipBanned($ip)
    {
        // Get our table and column names
        $table = $this->config['ipBannedTable'];
        $id = $this->config['ipBannedColumns']['ip'];
        
        // Build the Query
        $query = "SELECT COUNT({$id}) FROM `{$table}` WHERE `{$id}`=?";
        $check = $this->DB->query( $query, array($ip) )->fetchColumn();
        return ($check !== FALSE && $check > 0) ? true : false;
    }

    /**
     * Bans the specified account ID
     *
     * @param int $id The Account ID
     * @param string $banreason The reason the user is being banned
     * @param string $unbandate
     * @param string $bannedby The unban timestamp
     * @param bool $banip Ban the IP as well?
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function banAccount($id, $banreason, $unbandate = NULL, $bannedby = 'Admin', $banip = false)
    {
        // Check for account existance
        if(!$this->accountExists($id)) return false;
        
        // Get our table and column names
        $cols = $this->config['bannedColumns'];
        $table = $this->config['bannedTable'];

        // Make sure our unbandate is set, 1 year default
        ($unbandate == NULL) ? $unbandate = (time() + 31556926) : '';
        $data = array("{$cols['accountId']}" => $id);
        
        // Add supported columns
        if($cols['banTime']) $data[ $cols['banTime'] ] = time();
        if($cols['unbanTime']) $data[ $cols['unbanTime'] ] = $unbandate;
        if($cols['bannedBy']) $data[ $cols['bannedBy'] ] = $bannedby;
        if($cols['banReason']) $data[ $cols['banReason'] ] = $banreason;
        if($cols['active']) $data[ $cols['active'] ] = 1;
        
        // Insert
        $result = $this->DB->insert($table, $data);
        
        // Do we ban the IP as well?
        return ($banip && $result) ? $this->banAccountIp($id, $banreason, $unbandate, $bannedby) : $result;
    }

    /**
     * @param int $id The account ID
     * @param string $banreason The reason user is being banned
     * @param string $unbandate The unban date timestamp
     * @param string $bannedby Who is banning the user?
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function banAccountIp($id, $banreason, $unbandate = NULL, $bannedby = 'Admin')
    {
        // Get our table and column names
        $cid = $this->config['accountColumns']['id'];
        $lip = $this->config['accountColumns']['lastIp'];
        $table = $this->config['accountTable'];
        if($lip == false) return false;
        
        // Check for account existance
        $query = "SELECT `{$lip}` FROM `{$table}` WHERE `{$cid}`=?";
        $ip = $this->DB->query( $query, array($id) )->fetchColumn();
        if(!$ip) return false;
        
        // Check if the IP is already banned or not
        if( $this->ipBanned($ip) ) return true;
        
        // Get our table and column names
        $cols = $this->config['ipBannedColumns'];
        $table = $this->config['ipBannedTable'];

        // Make sure our unbandate is set, 1 year default
        ($unbandate == NULL) ? $unbandate = (time() + 31556926) : '';
        $data = array("{$cols['ip']}" => $ip);
        
        // Add supported columns
        if($cols['banTime']) $data[ $cols['banTime'] ] = time();
        if($cols['unbanTime']) $data[ $cols['unbanTime'] ] = $unbandate;
        if($cols['bannedBy']) $data[ $cols['bannedBy'] ] = $bannedby;
        if($cols['banReason']) $data[ $cols['banReason'] ] = $banreason;
        
        // Return the insert result
        return $this->DB->insert($table, $data);
    }

    /**
     * Unbans a user account
     *
     * @param int $id The account ID
     *
     * @return bool
     */
    public function unbanAccount($id)
    {
        // Check if the account is not Banned
        if( !$this->accountBanned($id) ) return true;
        
        // Get our table and column names
        $cols = $this->config['bannedColumns'];
        $table = $this->config['bannedTable'];
        
        // Check for account existance
        return $this->DB->update($table, array("{$cols['active']}" => 0), "`{$cols['accountId']}`={$id}");
    }

    /**
     * Unbans an IP address
     *
     * @param int $id
     *
     * @return bool
     */
    public function unbanAccountIp($id)
    {
        // Get our table and column names
        $cid = $this->config['accountColumns']['id'];
        $lip = $this->config['accountColumns']['lastIp'];
        $table = $this->config['accountTable'];
        if($lip == false) return false;
        
        // Check for account existance
        $query = "SELECT `{$lip}` FROM `{$table}` WHERE `{$cid}`=?";
        $ip = $this->DB->query( $query, array($id) )->fetchColumn();
        if(!$ip) return false;
        
        // Check if the IP is banned or not
        if( !$this->ipBanned($ip) ) return true;
        
        // Get our table and column names
        $table = $this->config['ipBannedTable'];
        $col =  $this->config['ipBannedColumns']['ip'];
        
        // Check for account existance
        return $this->DB->delete($table, "`{$col}`=".$ip);
    }

    /**
     * Deletes a user account
     *
     * @param int $id The Account ID
     *
     * @return bool
     */
    public function deleteAccount($id)
    {
        // Delete any bans
        $this->unbanAccount($id);
        
        // Get our table and column names
        $table = $this->config['accountTable'];
        $col = $this->config['accountColumns']['id'];
        
        // Delete the account
        return $this->DB->delete($table, "`{$col}`=".$id);
    }

    /**
     * Returns the expansion level of the account
     *
     * @return int
     *      0 => Base Game
     *      1 => Burning Crusade
     *      2 => Wrath of the Lich King
     *      3 => Cataclysm
     *      4 => Mists of Pandaria
     */
    public function expansionLevel()
    {
        return (int) $this->config['expansionLevel'];
    }

    /**
     * Returns the Database ID of the given expansion
     *
     * @param int $e
     *
     * @return bool|int
     */
    public function expansionToBit($e)
    {
        if(!isset($this->config['expansionToBit'][$e])) return false;
        return (int) $this->config['expansionToBit'][$e];
    }

    /**
     * Fetches the number of accounts in the realm database
     * @return int
     */
    public function numAccounts()
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $col = $this->config['accountColumns']['id'];
        return $this->DB->query("SELECT COUNT(`{$col}`) FROM `{$table}`")->fetchColumn();
    }

    /**
     * Fetches the number of banned accounts from the realm database
     *
     * @return int
     */
    public function numBannedAccounts()
    {
        // Get our table and column names
        $table = $this->config['bannedTable'];
        $col = $this->config['bannedColumns']['accountId'];
        return $this->DB->query("SELECT COUNT(`{$col}`) FROM `{$table}` WHERE `active` = 1")->fetchColumn();
    }

    /**
     * Fetches the number of inactive accounts (3 months)
     *
     * @return int
     */
    public function numInactiveAccounts()
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $id = $this->config['accountColumns']['id'];
        $ll = $this->config['accountColumns']['lastLogin'];
        
        // 90 days or older
        $time = time() - 7776000;
        $query = "SELECT COUNT(`{$id}`) FROM `{$table}` WHERE UNIX_TIMESTAMP(`{$ll}`) <  $time";
        return $this->DB->query( $query )->fetchColumn();
    }

    /**
     * Fetches the number of Active accounts from the realm database
     *
     * @return int
     */
    public function numActiveAccounts()
    {
        // Get our table and column names
        $table = $this->config['accountTable'];
        $id = $this->config['accountColumns']['id'];
        $ll = $this->config['accountColumns']['lastLogin'];
        
        // 24 hours or sooner
        $time = date("Y-m-d H:i:s", time() - 86400);
        $query = "SELECT COUNT(`{$id}`) FROM `{$table}` WHERE `{$ll}` BETWEEN  '$time' AND NOW()";
        return $this->DB->query( $query )->fetchColumn();
    }
    
/*
| -------------------------------------------------------------------------------------------------
|                                           Helper Methods
| -------------------------------------------------------------------------------------------------
*/

    
/*
| ---------------------------------------------------------------
| Method: getColumnById()
| ---------------------------------------------------------------
|
| This method returns the column name for the given ID's
|
| @Param: (String) $table - The table ID
| @Param: (String) $col - The column ID ID
| @Return: (String)
|
*/
    public function getColumnById($table, $col)
    {
        // Make sure the config key exists
        if(!isset($this->config["{$table}Columns"][$col])) return false;
        
        return $this->config["{$table}Columns"][$col];
    }
    
/*
| ---------------------------------------------------------------
| Method: getDB()
| ---------------------------------------------------------------
|
| This method returns the database connection object to the realm
| database
|
| @Return: (Object)
|
*/
    public function getDB()
    {
        return $this->DB;
    }
    
/*
| -------------------------------------------------------------------------------------------------
|                               Realm & Account Table Query Builder
| -------------------------------------------------------------------------------------------------
*/
    protected function _buildQuery($mode, $config)
    {
        // Grab our columns and table names
        if($mode == 'R')
        {
            $cols = $this->config['realmColumns'];
            $table = $this->config['realmTable'];
        }
        else
        {
            $cols = $this->config['accountColumns'];
            $table = $this->config['accountTable'];
        }
        
        // Filter out false column names
        $columns = array();
        foreach($cols as $c)
        {
            if($c !== false) $columns[] = $c;
        }
        
        // Prepare the column names
        $cols = "`". implode('`, `', $columns) ."`";
    
        // pre build the query...
        $query = "SELECT {$cols} FROM `{$table}`";
        
        // Append Where statement
        if($config['where'] != null) $query .= " WHERE {$config['where']}";
        
        // Append OrderBy statement
        $dir = (strtoupper($config['direction']) == 'ASC') ? 'ASC' : 'DESC';
        if($config['orderby'] != null) $query .= " ORDER BY `{$config['orderby']}` {$dir}";
        
        // Append Limits
        $query .= " LIMIT {$config['offset']}, {$config['limit']}";
        
        // Return the query
        return $query;
    } 
    
    protected function prepare($mode, $config)
    {
        // Append Limits
        $query = $this->_buildQuery($mode, $config);
        
        // Prepare the statement, and bind params
        $stmt = $this->DB->prepare($query);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        if(is_array($config['bind']) && !empty($config['bind']))
        {
            foreach($config['bind'] as $key => $var)
            {
                if(is_int($var))
                    $stmt->bindParam($key, $var, \PDO::PARAM_INT);
                else
                    $stmt->bindParam($key, $var, \PDO::PARAM_STR, strlen($var));
            }
        }
        
        // Return the statement
        return $stmt;
    }
}
// EOF