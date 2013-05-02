<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Routing/Router.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 * @contains    Router
 */
namespace System\Routing;
use \Plexis;
use System\Core\Module;
use System\Http\Request;
use System\Http\WebRequest;
use System\Utils\LogWritter;
use System\Security\XssFilter;

/**
 * The Router is used to determine which module, controller, and action
 *
 * When called, this object works with the Request object to determine 
 * the current uri, and analyze it to determine which module, controller, 
 * and method to load. This object also handles the adding and removing of
 * routes that are stored in the plexis database.
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Routing
 */
class Router
{
    /**
     * Have we routed the url yet?
     * @var bool
     */
    protected static $routed = false;
    
    /**
     * Specified whether the main request was handled
     * @var bool
     */
    protected static $RequestHandled = false;
    
    /**
     * The route stack of all defined routes
     * @var \System\Routing\RouteCollection
     */
    protected static $Routes;
    
    /**
     * Holds the plexis Logger object
     * @var \System\Utils\LogWritter
     */
    protected static $Log;
    
    /**
     * This method analyzes the current URL request, and loads the
     * module in which claims the URL route. This method is called
     * automatically, and will not do anything if called again.
     *
     * @return void
     */
    public static function Init() 
    {
        // Make sure we only route once
        if(self::$routed) return;
        
        // Load debug log
        self::$Log = LogWritter::Instance("debug");
        if(self::$Log == false)
            self::$Log = new LogWritter();
        
        // Load our route collection
        self::$Routes = new RouteCollection();
        $routes = array();
        
        // Search the database for defined routes
        include SYSTEM_PATH . DS .'config'. DS .'routes.php';
        
        // Do we have a custom route?
        if(is_array($routes))
        {
            // Add routes to the collection
            foreach($routes as $match => $route)
                self::$Routes->addRoute( new Route($match, $route) );
        }
        
        // Tell the system we've routed
        self::$routed = true;
    }
    
    /**
     * This method analyzes a uri string, and returns the Module associated
     * with the uri route. This method also returns the data required to correctly
     * invoke the module action in a reference variable
     *
     * @param string $route The uri string to be routed.
     * @param string[] $data [Reference Variable] This variable will
     *   pass back the request data, such as the controller, action, 
     *   and parameters to be used to invoke the module. This variable
     *   will be empty if the module could not be routed.
     *
     * @return \System\Core\Module|bool Returns false if the request leads to a 404,
     *   otherwise the module object will be returned.
     */
    public static function Forge($route, &$data = array())
    {
        // Debug logging
        self::$Log->logDebug("[Router] Forging route \"{$route}\"");

        // Correctly format the URI, removing double or more slashes
        $route = trim(preg_replace('~(/{2,})~', '/', strtolower($route)), '/');
        if(empty($route))
            // There is no URI, Lets load our default module
            $route = Plexis::GetConfig()->get("default_module");

        /** @noinspection PhpUnusedLocalVariableInspection */
        $Mod = false;

        // Try to find a module route for the request
        if(self::$Routes->hasRoute($route, $data))
        {
            // Debug logging
            self::$Log->logDebug("[Router] Global route for \"{$route}\" found. Loading module \"{$data['module']}\"...");

            // Check for a routes
            try {
                // Is the module installed?
                $Mod = Module::Load( $data['module'] );
                if(!$Mod->isInstalled())
                {
                    self::$Log->logWarning("[Router] Module is not installed");
                    return false;
                }
            }
            catch( \ModuleNotFoundException $e ) {
                // Debug logging
                self::$Log->logWarning("[Router] Unable to locate module \"{$data['module']}\"");
                return false;
            }
        }
        else
        {
            // Get our module name
            $parts = explode('/', $route);
            $module = $parts[0];

            // Check for a routes
            try {
                // Is the module installed?
                self::$Log->logDebug("[Router] Loading module \"{$module}\"...");
                $Mod = Module::Load( $module );
                if(!$Mod->isInstalled())
                {
                    self::$Log->logWarning("[Router] Module is not installed");
                    return false;
                }
            }
            catch( \ModuleNotFoundException $e ) {
                // Debug logging
                self::$Log->logWarning("[Router] Unable to locate module \"{$module}\"");
                return false;
            }

            // Load the routes file if it exist
            $path = $Mod->getRootPath() . DS . 'config' . DS . 'routes.php';
            if(file_exists($path))
            {
                // Debug logging
                self::$Log->logDebug("[Router] Module routes found, loading routes");
                $routes = array();
                include $path;

                // If we have routes, load up a new route collection
                if(is_array($routes))
                {
                    $Rc = new RouteCollection();
                    foreach($routes as $match => $r)
                        $Rc->addRoute( new Route($match, $r) );

                    // If module has route, the $data var will be filled
                    if(!$Rc->hasRoute($route, $data))
                    {
                        // Debug
                        self::$Log->logDebug("[Router] No Module route found for the provided route... using default route path");
                        goto NoModuleRoute;
                    }
                }
                else
                {
                    // Debug
                    self::$Log->logDebug("[Router] Incorrect format for the \$routes array... using default route path");
                    goto NoModuleRoute;
                }
            }
            else
            {
                // Go to for not having a module route defined
                NoModuleRoute:
                {
                    // Make sure we have a module, controller, and action
                    if(!isset($parts[1]))
                        $parts[1] = ucfirst($Mod->getName());
                    if(!isset($parts[2]))
                        $parts[2] = 'Index';

                    $data = array(
                        'controller' => $parts[1],
                        'action' => $parts[2],
                        'params' => array_slice($parts, 3)
                    );
                }
            }
        }

        // Debug logging
        $params = (!empty($params)) ? "and Params: ". implode(', ', $data['params']) : '';
        self::$Log->logDebug("[Router] Found Controller: {$data['controller']}, Action: {$data['action']}". $params);
        return $Mod;
    }
    
    /**
     * Adds a list new route rules in the database for future route matching
     *
     * @param \System\Routing\RouteCollection $routes The route stack container
     *   
     * @return bool Returns true if successful, false otherwise.
     */
    public static function AddRoutes( RouteCollection $routes )
    {
        // Add routes to the collection
        self::$Routes->merge( $routes );
        
        // Write routes file
        $routes = self::$Routes->getRoutes();
        
        // Save the routes file
        $file = SYSTEM_PATH . DS .'config'. DS .'routes.php';
        $string = "<?php\n\$routes = ". var_export($routes, true) .";\n?>";
        $string = preg_replace('/[ ]{2}/', "\t", $string);
        $string = preg_replace("/\=\>[ \n\t]+array[ ]+\(/", '=> array(', $string);
        return file_put_contents($file, $string);
    }
    
    /**
     * Removes a defined route from the database
     *
     * @param string $key The routes array key in routes.php
     *
     * @return bool Returns true on success
     */
    public static function RemoveRoute($key) 
    {
        self::$Routes->removeRoute($key);
        
        // Get our new list of routes
        $routes = self::$Routes->getRoutes();
        
        // Save the routes file
        $file = SYSTEM_PATH. DS . 'config' . DS . 'routes.php';
        $string = "<?php\n\$routes = ". var_export($routes, true) .";\n?>";
        $string = preg_replace('/[ ]{2}/', "\t", $string);
        $string = preg_replace("/\=\>[ \n\t]+array[ ]+\(/", '=> array(', $string);
        return file_put_contents($file, $string);
    }
    
    /**
     * Returns the route collection containing all defined routes.
     *
     * @return \System\Routing\RouteCollection
     */
    public static function FetchRoutes()
    {
        return self::$Routes;
    }
}

// Init the class
Router::Init();

// EOF