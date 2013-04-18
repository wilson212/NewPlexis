<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Web/View.php
 * @copyright   2013 Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Web;
use ViewNotFoundException;

/**
 * An individual view template class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage	Web
 */
class View
{
    /**
     * View contents as a string
     * @var string
     */
    protected $contents;
    
    /**
     * Assigned template variables and values
     * @var mixed[]
     */
    protected $variables = array();
    
    /**
     * Constructor
     *
     * @param string $string The file path to the template file, or the tempalte
     *   as a string
     * @param bool $isFile If set to true, $string becomes a filename, and is
     *   loaded. If false, $string is treated as the view's contents.
     *
     * @throws \ViewNotFoundException if the view file cannot be located
     */
    public function __construct($string, $isFile = true)
    {
        if($isFile && !file_exists($string))
            throw new ViewNotFoundException('Could not find view file "'. $string .'".');
        
        $this->contents = ($isFile) ? file_get_contents($string) : $string;
    }
    
    /**
     * Sets variables to be replaced in the view
     *
     * @param string|array $name Name of the variable to be set,
     *   or can be an array of key => value
     * @param mixed $value The value of the variable (not set if $name
     *   is an array)
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        if(is_array($name) || $name instanceof \Traversable)
        {
            foreach($name as $key => $val)
                $this->variables[$key] = $val;
        }
        else
        {
            $this->variables[$name] = $value;
        }
    }
    
    /**
     * These method clears all the set variables for this view
     *
     * @return void
     */
    public function clearVars()
    {
        $this->variables = array();
    }
    
    /**
     * Fetches all currently set variables
     *
     * @return mixed[]
     */
    public function getVars()
    {
        return $this->variables;
    }
    
    /**
     * Appends the header adding a css tag
     *
     * @param string $location The http location of the file
     *
     * @return void
     */
    public function attachStylesheet($location) {}
    
    /**
     * Appends the header adding a script tag for this view file
     *
     * @param string $location The http location of the file
     * @param string $type The script mime type, as it would be in the html script tag.
     *
     * @return void
     */
    public function attachScriptScr($location, $type = 'text/javascript') {}
    
    /**
     * Returns the view's contents, un-parsed
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }
    
    /**
     * Sets the views contents
     *
     * @param string|View $contents The new contents of
     *   this view file. Must be a string, or an object extending
     *   this Class.
     *
     * @throws \Exception Thrown if the contents are not a string
     *   or a subclass of View
     *
     * @return void
     */
    public function setContents($contents)
    {
        // Make sure our contents are valid
        if(!is_string($contents) && !($contents instanceof View))
            throw new \Exception('Contents of the view must be a string, or an object extending the "View" class');

        $this->contents = $contents;
    }
    
    /**
     * These methods parses the view contents and returns the source
     *
     * @return string
     */
    public function render()
    {
        if(!empty($this->variables))
        {
            // Extract the class variables so $this->variables[ $var ] becomes $var
            extract($this->variables);
            
            // Start contents capture
            ob_start();
            
            // Eval the source so we can process the php tags in the view correctly
            eval('?>'. $this->parse());
            
            // Capture the completed source, and return it
            return ob_get_clean();
        }
        
        return $this->contents;
    }

    protected function parse()
    {

    }
    
    /**
     * These methods parses the view contents and returns the source
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}