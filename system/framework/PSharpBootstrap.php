<?php
/**
 * PSharp Framework
 *
 * PSharp is a C# inspired, Object Oriented framework for PHP
 * web applications.
 *
 * @author      Steven Wilson
 * @author      Tony Hudgins
 * @copyright   2013 Plexis Dev Team
 * @license     GNU GPL v3
 */
 
 // Make sure we are running php version 5.3.2 or newer!!!!
if(!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50302)
    die('PHP version 5.3.2 or newer required to run the PSharp Framework. Your version: '. PHP_VERSION);

// Include required classes to init the autoloader
require_once __DIR__ . DIRECTORY_SEPARATOR .'Core'. DIRECTORY_SEPARATOR .'Autoloader.php';

// Import Autoloader class into the global namespace
use System\Core\Autoloader;

// Register the Autoloader with spl_autoload
Autoloader::Register();

// Register the System namespace with the autoloader.
Autoloader::RegisterNamespace('System', __DIR__);

// Register Exceptions directory as they are not namespaced
Autoloader::RegisterPath(__DIR__ . DIRECTORY_SEPARATOR .'Exceptions');