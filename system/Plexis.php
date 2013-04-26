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
use System\Http\Request;
use System\Http\WebRequest;
use System\Routing\Router;
use System\Security\Auth;
use System\Utils\Benchmark;
use System\Web\Template;

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
    protected static $Db = false;

    protected static $Config;

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

        // Init System Benchmark
        Benchmark::Start('System');

        // Make sure output buffering is enabled, and started This is pretty important
        ini_set('output_buffering', 'On');
        ob_start();

        // Enable the error handler to handle all errors
        ErrorHandler::Register();

        /** The URL to get to the root of the website (HTTP_HOST + webroot) */
        define('SITE_URL', ( MOD_REWRITE ) ? Request::BaseUrl() : Request::BaseUrl() .'/?uri=');

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
        self::DbConnection();

        // Initiate User Session
        // Auth::Init();

        // Handle Request
        try {
            $Response = WebRequest::GetInitial()->execute();
            $Response->send();
        }
        catch(\HttpNotFoundException $e) {
            self::Show404();
        }

        $time = "Loaded in ". round(microtime(true) - TIME_START, 5) . " seconds";
        echo $time;
    }

    /**
     * Fetches the Database connection
     *
     * @param bool $showOffline If set to false, the Site Offline page will
     *   not be rendered if the plexis database connection is offline
     *
     * @return System\Database\DbConnection
     */
    public static function DbConnection($showOffline = true)
    {
        if(!self::$Db instanceof DbConnection)
        {
            try
            {
                // Load database config
                $Config = ConfigManager::Load( SYSTEM_PATH . DS . "config" . DS . "database.php" );
                self::$Db = new DbConnection(
                    $Config["Plexis"]["host"],
                    $Config["Plexis"]["port"],
                    $Config["Plexis"]["database"],
                    $Config["Plexis"]["username"],
                    $Config["Plexis"]["password"]
                );
            }
            catch(DatabaseConnectError $e)
            {
               if($showOffline)
                   self::ShowSiteOffline('Plexis database offline');
            }
        }

        return self::$Db;
    }

    /**
     * @return \System\Configuration\ConfigFile
     */
    public static function GetConfig()
    {
        return self::$Config;
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
        die;
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
            $Response->statusCode(404);
            $Response->body('<h1>403 Forbidden</h1>');
            $Response->send();
        }
        die;
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
            $Response->body('<h1>Site is currently offline<br /><br />'. $message .'</h1>');
            $Response->send();
        }
        die;
    }

    /**
     * Returns an array of installed plugins
     *
     * @return string[]
     */
    public static function ListPlugins()
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

        // Set default theme path
        $theme = Request::Cookie('theme', 'Plexis_BC');
        Template::SetThemePath( ROOT . DS . "themes", $theme );
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