<?php
/**
 * Plexis Content Management System
 *
 * @file        System/Plexis.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
use System\Core\Autoloader;
use System\Core\ErrorHandler;
use System\Utils\Benchmark;

class Plexis
{
    /**
     * Internal var that prevents the system from running twice
     * @var bool
     */
    private static $running = false;

    public static function Init()
    {
        // Don't allow the system to run twice
        if(self::$running) return;
        self::$running = true;

        // Include required classes to init the autoloader
        /** @noinspection PhpIncludeInspection */
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

        // Catch all exceptions from application
        try {
            self::Run();
        }
        catch(Exception $e) {
            ErrorHandler::HandleException($e);
        }
    }

    protected static function Run()
    {
        /* $Conn = new System\Database\DbConnection('127.0.0.1', 3306, 'plexis', 'admin', 'admin');
        $Command = $Conn->CreateCommand("SELECT * FROM pcms_accounts WHERE id=:id");
        $Command->bindParam(":id", 7, \PDO::PARAM_INT);

        var_dump($Command->ExecuteReader()->Read()); */

        echo "Loaded in ", round(microtime(true) - TIME_START, 5), " seconds";
    }
}