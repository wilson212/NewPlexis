<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Web/Widget.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Web;

/**
 * Widget Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Web
 */
class Widget
{
    public static function Run($name)
    {
        $Widget = new Widget($name);
        return $Widget->render();
    }

    public function __construct($name)
    {

    }

    public function render()
    {

    }
}