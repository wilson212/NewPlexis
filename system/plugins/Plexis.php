<?php
/**
 * Plexis Content Management System
 *
 * @copyright:    Copyright (c) 2013, Plexis Dev Team
 * @license:      GNU GPL v3
 */
namespace Plugin;
use \Plexis as App;
use System\Http\Response;
use System\Web\Template;

/**
 * Main plugin for detecting whether the system needs installed
 * Displays error message when install folder exists, but the system
 * is installed
 *
 * @author:     Steven Wilson
 * @author:     Tony (Syke)
 * @package     Plugins
 */
class Plexis
{
    public function __construct()
    {
        // Check for database online, suppress errors
        $DB = App::LoadDBConnection(false);

        // Check if the install directory exists
        $installerExists = is_dir( ROOT . DS . 'install' );

        // Check if installer files are present
        $locked = file_exists( ROOT . DS . 'install'. DS .'install.lock' );

        // Check if the install folder is still local
        if($DB == false && $locked == false && $installerExists == true)
        {
            // Temporary redirect (307)
            Response::Redirect('install/index.php', 0, 307);
            die;
        }
        elseif($locked == false && $installerExists == true)
        {
            // Warn that the installer is accessible.
            Template::DisplayMessage("error", "The installer is publicly accessible! Please rename, delete or re-lock your install folder");
        }
    }
}
?>