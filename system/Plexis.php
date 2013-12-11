<?php
/**
 * Plexis Content Management System
 *
 * @file        System/Plexis.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
use System\Configuration\ConfigManager;
use System\Core\Autoloader;
use System\Core\ErrorHandler;
use System\Database\DbConnection;
use System\Http\WebRequest;
use System\Routing\Router;
use System\Security\Session;
use System\Utils\LogWritter;
use System\Wowlib\Server;

class Plexis
{
    /**
     * Internal var that prevents the system from running twice
     * @var bool
     */
    private static $running = false;

    /**
     * Holds the database connection for the plexis database
     * @var System\Database\DbConnection
     */
    protected static $PlexisDb = false;

    protected static $Config;
    protected static $DbConfig;

    protected static $Server = null;

    /**
     * An array of loaded plugins
     * @var string[]
     */
    protected static $plugins = array();

    /**
     * Main method for running the Plexis application
     */
    public static function Init()
    {
        // Don't allow the system to run twice
        if(self::$running) return;
        self::$running = true;

        // Include required classes to init the autoloader
        // @noinspection PhpIncludeInspection
        require SYSTEM_PATH . DS .'framework'. DS .'core'. DS .'Autoloader.php';

        // Register the Default Core and Library namespaces with the autoloader
        Autoloader::Register(); // Register the Autoloader with spl_autoload;
        Autoloader::RegisterNamespace('System', SYSTEM_PATH . DS .'framework');
        Autoloader::RegisterPath(SYSTEM_PATH . DS .'framework'. DS .'Exceptions');

        // Make sure output buffering is enabled, and started This is pretty important
        ini_set('output_buffering', 'On');
        ob_start();

        // Enable the error handler to handle all errors
        ErrorHandler::Register();

        /** The URL to get to the root of the website (HTTP_HOST + webroot) */
        define('SITE_URL', ( MOD_REWRITE ) ? WebRequest::BaseUrl() : WebRequest::BaseUrl() .'/?uri=');

        // Catch all exceptions from application
        try {
            self::Run();
        }
        catch(Exception $e) {
            ErrorHandler::HandleException($e);
        }
    }

    /**
     * Internal method for initializing the individual parts
     * of the system, to get plexis running
     *
     * @return void
     */
    protected static function Run()
    {
        // Load Configs
        self::LoadConfigs();

        // Load Plugins
        self::LoadPlugins();

        // Load DB Connection
        self::Database();

        // Initiate User Session
        Session::Init();

        // Handle Request
        try {
            $Response = WebRequest::GetInitial()->execute();
            $Response->send(); // Send executed response to browser
			self::Cleanup();
        }
        catch(\HttpNotFoundException $e) {
            self::Show404();
        }
    }
	
	/**
	 * Cleanup (Closing) method for the CMS. Here we make sure the cms is ready for the
     * execution to stop
	 */
	protected static function Cleanup()
	{
		// All cleanup code to be executed here
		$time = "Page Loaded in ". round(microtime(true) - TIME_START, 5) . " seconds";
        $Log = LogWritter::Instance('debug');
        $Log->logDebug($time);
	}

    /**
     * Forces Plexis to cleanup and shutdown, stopping the execution of the
     * current request. Any code after this method is called will NOT be executed!
     */
    public static function Stop()
    {
        self::Cleanup(); die;
    }

    /**
     * Fetches the Database connection
     *
     * @param bool $showOffline If set to false, the Site Offline page will
     *   not be rendered if the plexis database connection is offline
     *
     * @return System\Database\DbConnection
     */
    public static function Database($showOffline = true)
    {
        if(!self::$PlexisDb instanceof DbConnection)
        {
            try
            {
                // Load database config
                self::$PlexisDb = new DbConnection(
                    self::$DbConfig["Plexis"]["host"],
                    self::$DbConfig["Plexis"]["port"],
                    self::$DbConfig["Plexis"]["database"],
                    self::$DbConfig["Plexis"]["username"],
                    self::$DbConfig["Plexis"]["password"]
                );
            }
            catch(DatabaseConnectError $e)
            {
               if($showOffline)
                   self::ShowSiteOffline('Plexis database offline');
            }
        }

        return self::$PlexisDb;
    }

    /**
     * Returns the Plexis main configuration config file instance
     *
     * @param string $name
     *
     * @return \System\Configuration\ConfigBase
     */
    public static function Config($name = 'site')
    {
        return ($name == 'database') ? self::$DbConfig : self::$Config;
    }

    /**
     * Fetches the WoW Server
     *
     * @return bool|Server
     */
    public static function GetServer()
    {
        // Make sure we aren't double loading
        if(self::$Server === null)
        {
            // Load our emulator and database array
            $ucEmu = ucfirst(self::$Config['emulator']);
            try
            {
                // Load database
                $Conn = new DbConnection(
                    self::$DbConfig["Realm"]["host"],
                    self::$DbConfig["Realm"]["port"],
                    self::$DbConfig["Realm"]["database"],
                    self::$DbConfig["Realm"]["username"],
                    self::$DbConfig["Realm"]["password"]
                );

                self::$Server = new Server($ucEmu, $Conn);
            }
            catch(\DatabaseConnectError $e)
            {
                self::$Server = false;
            }
            catch(\Exception $ex)
            {
                self::$Server = false;
            }
        }

        return self::$Server;
    }

    /**
     * Displays the 404 page not found page
     *
     * Calling this method will clear all current output, render the 404 page
     * and kill all current running scripts. No code following this method
     * will be executed
     *
     * @return void
     */
    public static function Show404()
    {
        // Load the 404 Error module
        $Initial = WebRequest::GetInitial();
        $Request = new WebRequest('error/404', $Initial->method());
        $Request->isAjax($Initial->isAjax());
        try {
            $Request->execute()->send();
        }
        catch(\HttpNotFoundException $e) {
            $Response = $Request->getResponse();
            $Response->statusCode(404);
            $Response->body('<h1>404 Page Not Found</h1>');
            $Response->send();
        }
        self::Stop();
    }

    /**
     * Displays the 403 "Forbidden"
     *
     * Calling this method will clear all current output, render the 403 page
     * and kill all current running scripts. No code following this method
     * will be executed
     *
     * @return void
     */
    public static function Show403()
    {
        // Load the 403 Error module
        $Initial = WebRequest::GetInitial();
        $Request = new WebRequest('error/403', $Initial->method());
        $Request->isAjax($Initial->isAjax());
        try {
            $Request->execute()->send();
        }
        catch(\HttpNotFoundException $e) {
            $Response = $Request->getResponse();
            $Response->statusCode(403);
            $Response->body('<h1>403 Forbidden</h1>');
            $Response->send();
        }
        self::Stop();
    }

    /**
     * Displays the site offline page
     *
     * Calling this method will clear all current output, render the site offline
     * page and kill all current running scripts. No code following this method
     * will be executed
     *
     * @param string $message The message to also be displayed with the
     *   Site Offline page.
     * @return void
     */
    public static function ShowSiteOffline($message = null)
    {
        // Load the 403 Error module
        $Initial = WebRequest::GetInitial();
        $Request = new WebRequest('error/offline', $Initial->method());
        $Request->isAjax($Initial->isAjax());
        try {
            $Request->execute()->send();
        }
        catch(\HttpNotFoundException $e) {
            $Response = $Request->getResponse();
            $Response->statusCode(503);
            $Response->body('<h1>Site is currently offline</h1><br /><br />'. $message);
            $Response->send();
        }
        self::Stop();
    }

    /**
     * Returns an array of installed plugins
     *
     * @return string[]
     */
    public static function GetLoadedPlugins()
    {
        return self::$plugins;
    }

    /**
     * Returns whether or not a plugin is installed and running
     *
     * @param string $name The name of the plugin
     *
     * @return bool
     */
    public static function PluginInstalled($name)
    {
        return in_array($name, self::$plugins);
    }

    /**
     * Internal method for loading the plexis config files
     *
     * @return void
     */
    protected static function LoadConfigs()
    {
        // Load plexis config
        self::$Config = ConfigManager::Load( SYSTEM_PATH . DS . "config" . DS . "config.php" );
        self::$DbConfig = ConfigManager::Load( SYSTEM_PATH . DS . "config" . DS . "database.php" );

        // Include Versions file
        include SYSTEM_PATH . DS . 'Versions.php';

        // Create debug log
        //$Log = new LogWritter(SYSTEM_PATH . DS . "logs". DS ."debug.log", "debug");
        $Log = new LogWritter(null, 'debug');
        $Log->setLogLevel(self::$Config->get("log_level"));
    }

    /**
     * Internal method for loading, and running all plugins
     *
     * @return void
     */
    protected static function LoadPlugins()
    {
        // Include our plugins file, and get the size
        $Plugins = array();
        include SYSTEM_PATH . DS . 'config' . DS . 'plugins.php';
        $OrigSize = sizeof($Plugins);

        // Loop through and run each plugin
        $i = 0;
        foreach($Plugins as $name)
        {
            $file = SYSTEM_PATH . DS . 'plugins' . DS . $name .'.php';
            if(!file_exists($file))
            {
                // Remove the plugin from the list
                unset($Plugins[$i]);
                continue;
            }

            // Construct the plugin class
            include $file;
            $className = "Plugin\\". $name;
            new $className();

            // Add the plugin to the list of installed plugins
            self::$plugins[] = $name;
            $i++;
        }

        // If we had to remove plugins, then save the plugins file
        if(sizeof($Plugins) != $OrigSize)
        {
            $file = SYSTEM_PATH . DS . 'config' . DS . 'plugins.php';
            $source = "<?php\n\$Plugins = ". var_export($Plugins, true) .";\n?>";
            file_put_contents($file, $source);
        }
    }
}