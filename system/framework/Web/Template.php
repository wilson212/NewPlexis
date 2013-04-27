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
use System\IO\Path;

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

    /**
     * An array of attached Views
     * @var View[]
     */
    protected static $contents = array();

    /**
     * The root path to the themes folder
     * @var string
     */
    protected static $themePath;

    /**
     * The selected theme name
     * @var string
     */
    protected static $themeName;

    /**
     * The complete http path to the theme root
     * @var string
     */
    protected static $themeUrl;

    /**
     * Theme xml config object
     * @var \SimpleXMLElement
     */
    protected static $themeConfig;

    /**
     * The layout name to be used
     * @var string
     */
    protected static $layoutName = 'default';

    /**
     * An array of lines to be injected into the layout head tags
     * @var string[]
     */
    protected static $headers = array();

    /**
     * Array of template messages
     * @var array[] ('level', 'message')
     */
    protected static $messages = array();

    /**
     * Javascript Variables to be added in the header
     * @var mixed[]
     */
    protected static $jsVars = array();

    /**
     * The title of the page
     * @string
     */
    protected static $pageTitle;

    /**
     * Appends the list of views to be rendered as the main content
     *
     * @param View $View The view to add to the main contents
     *
     * @return void
     */
    public static function AddView(View $View)
    {
        self::$contents[] = $View;
    }

    /**
     * Appends the header adding a css tag
     *
     * @param string $location The http location of the file
     *
     * @return void
     */
    public static function AddStylesheet($location)
    {
        $location = trim($location);

        // If we don't have a complete url, we need to determine if the css
        // file is a plexis, or template file
        if(!preg_match('@^((ftp|http(s)?)://|www\.)@i', $location))
        {
            $parts = explode('/', $location);
            $file = self::$themePath . DS . $parts;

            // Are we handling a template or plexis asset?
            $location = (file_exists($file)) ? self::$themeUrl .'/'. ltrim($location, '/') : Request::BaseUrl() .'/'. ltrim($location, '/');
        }
        self::$headers[] = '<link rel="stylesheet" type="text/css" href="'. $location .'"/>';
    }

    /**
     * Appends the header adding a script tag
     *
     * @param string $location The http location of the file
     * @param string $type The script mime type, as it would be in the html script tag.
     *
     * @return void
     */
    public static function AddScriptSrc($location, $type = 'text/javascript')
    {
        $location = trim($location);

        // If we don't have a complete url, we need to determine if the css
        // file is a plexis, or template file
        if(!preg_match('@^((ftp|http(s)?)://|www\.)@i', $location))
        {
            $parts = explode('/', $location);
            $file = self::$themePath . DS . $parts;

            // Are we handling a template or plexis asset?
            $location = (file_exists($file)) ? self::$themeUrl .'/'. ltrim($location, '/') : Request::BaseUrl() .'/'. ltrim($location, '/');
        }
        self::$headers[] = '<script type="'. $type .'" src="'. $location .'"></script>';
    }

    /**
     * Sets the page title (After server title)
     *
     * @param string $title The title of the page
     *
     * @return void
     */
    public static function PageTitle($title)
    {
        self::$pageTitle = $title;
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
        self::$messages[] = array($type, $message);
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
        // Build path
        $Module = strtolower($ModuleName);
        $View = new View(Path::Combine(self::$themePath, self::$themeName, 'views', $Module, $ViewFileName .'.tpl'));

        // Get the JS file path
        $viewjs = Path::Combine(self::$themePath, self::$themeName, 'js', 'views', $Module, $ViewFileName .'.js');

        // If the JS file exists in the template, include it!
        if(file_exists($viewjs))
        {
            $View->attachScriptScr(self::$themeUrl . "/js/views/{$Module}/{$ViewFileName}.js");
            $HasJsFile = true;
        }

        // Try and load the view
        return $View;
    }

    /**
     * Loads a partial view file from the template's partials folder.
     *
     * @param string $name The name of the partial view file (no extension).
     *
     * @throws \ViewNotFoundException Thrown if the template does not have the partial view
     *
     * @return \System\Web\View
     */
    public static function LoadPartial($name)
    {
        // Build path
        $path = Path::Combine(self::$themePath, self::$themeName, 'views', 'partials', $name .'.tpl');

        // Try and load the view
        return new View($path);
    }

    /**
     * Renders the layout, and all of its contents
     *
     * @param bool $ReturnContents If true, the contents rendered will be
     *      returned. Otherwise, the contents are sent to the browser, and the
     *      Response will be sent.
     *
     * @throws \Exception Thrown if the layout file has not been set
     *      using the {@link Template::SetLayout()} method
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
        return null;
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws \Exception Thrown if the layout file has not been set
     *      using the {@link Template::SetLayout()} method
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

    /**
     * Returns the current theme path
     *
     * @param bool $themeName Include the current set theme name?
     *
     * @return string The path from the root to the theme folder.
     */
    public static function GetThemePath($themeName = false)
    {
        return ($themeName) ? self::$themePath . DS . self::$themeName : self::$themePath;
    }

    /**
     * Returns the theme HTTP url to the root dir.
     *
     * @return string The path from the root to the theme folder.
     */
    public static function GetThemeUrl()
    {
        return self::$themeUrl;
    }

    /**
     * Sets the path to the theme folder
     *
     * @param string $path The full path to the theme folder
     * @param string $name The theme name. Set only if you want to also define
     *   the theme name as well as the path
     *
     * @throws \InvalidThemePathException If the theme config cannot be found
     * @return void
     */
    public static function SetThemePath($path, $name = null)
    {
        // Make sure the path exists!
        if(!is_dir($path))
            throw new \InvalidThemePathException('Invalid theme path "'. $path .'"');

        // Set theme path
        self::$themePath = $path;

        // Set the theme name if possible
        if(!empty($name))
            self::SetTheme($name);
    }

    /**
     * Sets the name of the theme to render, where the layout.tpl is located
     *
     * @param string $name The theme name
     *
     * @throws \InvalidThemePathException If the theme doesn't exist in the theme path
     *
     * @return void
     */
    public static function SetTheme($name)
    {
        // Make sure the path exists!
        $path = self::$themePath . DS . $name . DS . 'theme.xml';
        if(empty(self::$themePath) || !file_exists($path))
            throw new \InvalidThemePathException('Cannot find theme config file! "'. $path .'"');


        // Build the HTTP url to the theme's root folder
        self::$themeName = $name;
        $path = str_replace(ROOT . DS, '', dirname($path));
        self::$themeUrl = Request::BaseUrl() .'/'. str_replace(DS, '/', $path);
    }
}