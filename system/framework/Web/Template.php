<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Web/Template.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */

namespace System\Web;
use System\Http\Request;
use System\Http\Response;

/**
 * The Template Engine of the cms
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Web
 */
class Template
{
    /**
     * The View object for the layout
     * @var \System\Web\View
     */
    protected static $View;

    public static function AddView(View $View)
    {

    }

    /**
     * Displays a Popup message to be displayed to the client
     *
     * @param string $type The html class type ie: "error", "info", "warning" etc
     * @param string $message The string message to display to the client
     * @return void
     */
    public static function Alert($type, $message)
    {

    }

    /**
     * Adds a message to be displayed in the Global Messages container of the layout
     *
     * @param string $type The html class type ie: "error", "info", "warning" etc
     * @param string $message The string message to display to the client
     * @return void
     */
    public static function DisplayMessage($type, $message)
    {

    }

    /**
     * @param string $ModuleName The name of the module
     * @param string $ViewFileName The filename of the view file, including extension
     * @param bool $HasJsFile [Reference Variable] References whether a view JS file was
     *      found in the template files for this view file
     *
     * @return \System\Web\View
     */
    public static function LoadModuleView($ModuleName, $ViewFileName, &$HasJsFile = false)
    {

    }

    public static function LoadPartial($name)
    {

    }

    /**
     * Renders the layout, and all of its contents
     *
     * @param bool $ReturnContents If true, the contents rendered will be
     *      returned. Otherwise, the contents are sent to the browser, and the
     *      Response will be sent.
     *
     * @throws \Exception Thrown if the layout file has not been set
     *      using the {@link Layout::SetLayout()} method
     *
     * @return string|void
     */
    public static function Render($ReturnContents = false)
    {
        // Make sure we have loaded a layout
        if(!(self::$View instanceof View))
            throw new \Exception("You must first load a layout file before rendering");

        // Return contents if requested
        if($ReturnContents)
            return self::$View->render();

        // Send response
        Response::Body(self::$View->render());
        Response::Send();
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws \Exception Thrown if the layout file has not been set
     *      using the {@link Layout::SetLayout()} method
     */
    public static function Set($key, $value)
    {
        if(self::$View instanceof View)
            self::$View->set($key, $value);
        else
            throw new \Exception("You must first load a layout file before assigning variables");
    }

    public static function SetLayout($filePath)
    {

    }
}