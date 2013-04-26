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
 * The Router is used to determine which module and action to load for 
 * the current request. 
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
        
        // Init log var
        // self::$Log = Logger::Get('Debug');
        
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
     * This method analyzes a uri string, and executes the module
     * tied to the route. If the route cannot be parsed, a 404 error
     * will be thrown
     *
     * @param \System\Http\WebRequest $Request The request object
     *
     * @throws \HttpNotFoundException Thrown if there was a 404, Page Not Found
     *
     * @return \System\Http\WebResponse
     */
    public static function Execute(WebRequest $Request)
    {
        // Debug logging
        // self::$Log->logDebug("[Router] Executing route \"{$route}\"");
        
        // Route request
        $Module = self::LoadModule($Request->getUri(), $data);
        if($Module == false)
            throw new \HttpNotFoundException();
        
        // Define which controller and such we load
        $controller = ($Request->isAjax() && isset($data['ajax']['controller']))
            ? $data['ajax']['controller'] 
            : $data['controller'];
        $action = ($Request->isAjax() && isset($data['ajax']['action']))
            ? $data['ajax']['action']
            : $data['action'];
        
        // Prevent admin controller access in modules!
        if($controller == 'admin' && $Module->getName() != 'admin')
            throw new \HttpNotFoundException();
        
        // Fire the module off
        return $Module->invokeAction($Request, $controller, $action, $data['params']);
    }
    
    /**
     * This method is similar to {@link Router::Execute()}, but does not call on
     * the module to preform any actions. or throw an HttpNotFoundException if
     * route leads to a 404. Instead, the data required to correctly invoke the module,
     * as well as the Module itself is returned.
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
        // self::$Log->logDebug("[Router] Forging route \"{$route}\"");
        
        // Route request
        if(($Mod = self::LoadModule($route, $data)) === false)
            return false;
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
    
    /**
     * Checks a module and action for a matching route.
     *
     * @param string $route The route to map for a module
     * @param string[] $data [Reference Variable] This variable will
     *   pass back the request data, such as the controller, action, 
     *   and parameters to be used to invoke the module.
     *
     * @return \System\Core\Module|bool Returns false if there is no database route,
     *   or if the module matched does not exist.
     */
    protected static function LoadModule($route, &$data)
    {
        // Correctly format the URI
        $route = trim(preg_replace('~(/{2,})~', '/', strtolower($route)), '/');
        $Config = Plexis::GetConfig();
        
        // There is no URI, Lets load our controller and action defaults
        if(empty($route))
        {
            $route = $Config["default_module"]; // Default Module
        }
        else
        {
            // We are note allowed to call certain methods
            $parts = explode('/', $route);
            if(isset($parts[2]) && strncmp($parts[2], '_', 1) == 0)
                return false;
        }
        
        // Try to find a module route for the request
        $Mod = false;
        if(self::$Routes->hasRoute($route, $data))
        {
            // Debug logging
            // self::$Log->logDebug("[Router] Global route for \"{$route}\" found. Loading module \"{$data['module']}\"...");
            
            // Check for a routes
            try {
                $Mod = Module::Load( $data['module'] );
            }
            catch( \ModuleNotFoundException $e ) {
                // Debug logging
                // self::$Log->logWarning("[Router] Unable to locate module \"{$data['module']}\"");
            }
            
            // Does module exist?
            if($Mod == false)
                return false;
                
            // Is the module installed?
            if(!$Mod->isInstalled())
            {
                // Debug logging
                // self::$Log->logWarning("[Router] Module is not installed");
                return false;
            }
        }
        else
        {
            // Get our module name
            $parts = explode('/', $route);
            $module = $parts[0];
            
            // Debug logging
            // self::$Log->logDebug("[Router] Loading module \"{$module}\"...");
            
            // Check for a routes
            try {
                $Mod = Module::Load( $module );
            }
            catch( \ModuleNotFoundException $e ) {
                // Debug logging
                // self::$Log->logWarning("[Router] Unable to locate module \"{$module}\"");
            }
            
            // Does module exist?
            if($Mod == false)
                return false;
                
            // Is the module installed?
            if(!$Mod->isInstalled())
            {
                // Debug logging
                // self::$Log->logWarning("[Router] Module is not installed");
                return false;
            }
            
            // Load the routes file if it exist
            $path = $Mod->getRootPath() . DS . 'config' . DS . 'routes.php';
            if(file_exists($path))
            {
                // Debug logging
                // self::$Log->logDebug("[Router] Module routes found, loading routes");
                $routes = array();
                include $path;
                
                // If we have routes, load up a new route collection
                if(is_array($routes))
                {
                    $Rc = new RouteCollection();
                    foreach($routes as $match => $r)
                        $Rc->addRoute( new Route($match, $r) );
                        
                    if(!$Rc->hasRoute($route, $data))
                    {
                        // Debug
                        // self::$Log->logDebug("[Router] No Module route found for the provided route... using default route path");
                        goto NoModuleRoute;
                    }
                }
                else
                {
                    // Debug
                    // self::$Log->logDebug("[Router] Incorrect format for the \$routes array... using default route path");
                    goto NoModuleRoute;
                }
            }
            else
            {
                // Go to for not having a module route defined
                NoModuleRoute:
                {
                    // Is this an error?
                    if(strpos('error/', $route) !== false)
                    {
                        switch($route)
                        {
                            case "error/404":
                                die('404 Not Found');
                            case "error/403":
                                die('Forbidden');
                            case "error/offline":
                                die('Site Down For Maintenance');
                        }
                    }
                    
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
        // $params = (!empty($params)) ? "and Params: ". implode(', ', $data['params']) : '';
        // self::$Log->logDebug("[Router] Found Controller: {$data['controller']}, Action: {$data['action']}". $params);
        return $Mod;
    }
}

// Init the class
Router::Init();

// EOF