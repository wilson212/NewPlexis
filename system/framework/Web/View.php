<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Web/View.php
 * @copyright   2013 Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Web;

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
    protected $contents = "";

    /**
     * The left delimiter to use for parsing variables
     * @var string
     */
    protected $LDelim = '{';

    /**
     * The right delimiter to use for parsing variables
     * @var string
     */
    protected $RDelim = '}';

    /**
     * Assigned template variables and values
     * @var mixed[]
     */
    protected $variables = array();

    /**
     * An array of attached style sheets
     * @var string[]
     */
    protected $stylesheets = array();

    /**
     * An array of attached scripts
     * @var array[] (location, type)
     */
    protected $scripts = array();

    /**
     * Creates a new instance of View using the specified view file.
     *
     * @param string $filePath The full path to the view file
     *
     * @throws \ViewNotFoundException Thrown if the view file cannot be located
     *
     * @return View
     */
    public static function FromFile($filePath)
    {
        $View = new View();
        $View->loadFile($filePath);
        return $View;
    }

    /**
     * Creates a new instance of View using the specified view contents
     *
     * @param mixed $contents The contents of the view file
     *
     * @return View
     */
    public static function FromString($contents)
    {
        $View = new View();
        $View->setContents($contents);
        return $View;
    }

    /**
     * @param string $filePath The full path to the view file
     *
     * @throws \ViewNotFoundException Thrown if the view file cannot be located
     */
    public function loadFile($filePath)
    {
        if(!file_exists($filePath))
            throw new \ViewNotFoundException('Could not find view file "'. $filePath .'".');

        $this->contents .= file_get_contents($filePath);
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
            $this->variables[$name] = $value;
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
    public function attachStylesheet($location)
    {
        $this->stylesheets[] = $location;
    }
    
    /**
     * Appends the header adding a script tag for this view file
     *
     * @param string $location The http location of the file
     * @param string $type The script mime type, as it would be in the html script tag.
     *
     * @return void
     */
    public function attachScriptScr($location, $type = 'text/javascript')
    {
        $this->scripts[] = array('location' => $location, 'type' => $type);
    }
    
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
    public function __toString()
    {
        return $this->render();
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
            eval('?>'. $this->parse($this->contents, $this->variables));

            // Capture the completed source, and return it
            return ob_get_clean();
        }

        return $this->contents;
    }

    /**
     * This method loops through all the specified variables, and replaces
     * the Pseudo blocks that contain variable names
     *
     * @param string $source The source string to parse
     * @param mixed[] $variables The variables to parse in the string
     *
     * @return string
     */
    protected function parse($source, $variables)
    {
        // store the vars into $data, as its easier then $this->variables
        $count = 0;

        // Do a search and destroy or pseudo blocks... keep going till we replace everything
        do
        {
            // If we don't replace something in the current iteration, then we'll break;
            $replaced_something = false;

            // Loop through the data and catch arrays
            foreach($variables as $key => $value)
            {
                // If $value is an array, we need to process it as so
                if(is_array($value))
                {
                    // First, we check for array blocks (Foreach blocks), you do so by checking: {/key}
                    // .. if one exists we preg_match the block
                    if(strpos($source, $this->LDelim . '/' . $key . $this->RDelim) !== false)
                    {
                        // Create our array block regex
                        $regex = $this->LDelim . $key . $this->RDelim . "(.*)". $this->LDelim . '/' . $key . $this->RDelim;

                        // Match all of our array blocks into an array, and parse each individually
                        preg_match_all("~" . $regex . "~iUs", $source, $matches, PREG_SET_ORDER);
                        foreach($matches as $match)
                        {
                            // Parse pair: Source, Match to be replaced, With what are we replacing?
                            $replacement = $this->parsePair($match[1], $value);
                            if($replacement === "_PARSER_false_") continue;

                            // Main replacement
                            $source = str_replace($match[0], $replacement, $source);
                            $replaced_something = true;
                        }
                    }

                    // Now that we are done checking for blocks, Create our array key identifier
                    $key = $key .".";

                    // Next, we check for nested array blocks, you do so by checking for: {/key.*}.
                    // ..if one exists we preg_match the block
                    if(strpos($source, $this->LDelim . "/" . $key) !== false)
                    {
                        // Create our regex
                        $regex = $this->LDelim . $key ."(.*)". $this->RDelim . "(.*)". $this->LDelim . '/' . $key ."(.*)". $this->RDelim;

                        // Match all of our array blocks into an array, and parse each individually
                        preg_match_all("~" . $regex . "~iUs", $source, $matches, PREG_SET_ORDER);
                        foreach($matches as $match)
                        {
                            // Parse pair: Source, Match to be replaced, With what are we replacing?
                            $replacement = $this->parsePair($match[2], $this->parseArray($match[1], $value));
                            if($replacement === "_PARSER_false_") continue;

                            // Check for a false reading
                            $source = str_replace($match[0], $replacement, $source);
                            $replaced_something = true;
                        }
                    }

                    // Lastly, we check just plain arrays. We do this by looking for: {key.*}
                    // .. if one exists we preg_match the array
                    if(strpos($source, $this->LDelim . $key) !== false)
                    {
                        // Create our regex
                        $regex = $this->LDelim . $key . "(.*)".$this->RDelim;

                        // Match all of our arrays into an array, and parse each individually
                        preg_match_all("~" . $regex . "~iUs", $source, $matches, PREG_SET_ORDER);
                        foreach($matches as $match)
                        {
                            // process the array, If we got a false array parse, then skip the rest of this loop
                            $replacement = $this->parseArray($match[1], $value);
                            if($replacement === "_PARSER_false_") continue;

                            // If our replacement is a array, it will cause an error, so just return "array"
                            if(is_array($replacement)) $replacement = "array";

                            // Main replacement
                            $source = str_replace($match[0], $replacement, $source);
                            if($replacement != $match[0]) $replaced_something = true;
                        }
                    }
                }
            }

            // Now parse singles. We do this last to catch variables that were
            // inside array blocks...
            foreach($variables as $key => $value)
            {
                // We don't handle arrays here
                if(is_array($value)) continue;

                // Find a match for our key, and replace it with value
                $match = $this->LDelim . $key . $this->RDelim;
                if(strpos($source, $match) !== false)
                {
                    $source = str_replace($match, $value, $source);
                    $replaced_something = true;
                }
            }

            // If we did not replace anything, quit
            if(!$replaced_something)
                break;

            // Raise the counter
            ++$count;
        } while($count < 5);

        // Return the parsed source
        return $source;
    }

    /**
     * Parses a string array such as {user.userinfo.username}
     *
     * @param string $key The full un-parsed array ( { something.else} )
     * @param mixed[] $array The actual array that holds the value of $key
     *
     * @return mixed Returns the parsed value of the array key
     */
    protected function parseArray($key, $array)
    {
        // Check to see if this is even an array first
        if(!is_array($array)) return $array;

        // Check if this is a multi-dimensional array
        if(strpos($key, '.') !== false)
        {
            $args = explode('.', $key);
            $count = count($args);

            for($i = 0; $i < $count; $i++)
            {
                if(!isset($array[$args[$i]]))
                    return "_PARSER_false_";
                elseif($i == $count - 1)
                    return $array[$args[$i]];
                else
                    $array = $array[$args[$i]];
            }
        }

        // Just a simple 1 stack array
        else
        {
            // Check if variable exists in $array
            if(array_key_exists($key, $array))
                return $array[$key];
        }

        // Tell the requester that the array doesn't exist
        return "_PARSER_false_";
    }

    /**
     * Parses array blocks (  {key} ,,, {/key} ), acts like a foreach loop
     *
     * @param string $match The preg_match of the block {key} (what we need) {/key}
     * @param mixed[] $val The array that contains the variables inside the blocks
     *
     * @return string Returns the parsed foreach loop block
     */
    protected function parsePair($match, $val)
    {
        // Init the empty main block replacement
        $final_out = '';

        // Make sure we are dealing with an array!
        if(!is_array($val) || !is_string($match)) return "_PARSER_false_";

        // Remove nested vars, nested vars are for outside vars
        if(strpos($match, $this->LDelim . $this->LDelim) !== false)
        {
            $match = str_replace(
                array($this->LDelim, $this->RDelim . $this->RDelim),
                array("<<!", "!>>"),
                $match
            );
        }

        // Define out loop number
        $i = 0;

        // Process the block loop here, We need to process each array $val
        foreach($val as $key => $value)
        {
            // if value isn't an array, then we just replace {value} with string
            if(is_array($value))
                // Parse our block. This will catch nested blocks and arrays as well
                $block = $this->parse($match, $value);
            else
                // Just replace {value}, as we are dealing with a string
                $block = str_replace('{value}', $value, $match);

            // Setup a few variables to tell what loop number we are on
            if(strpos($block, "{loop.") !== false)
            {
                $block = str_replace(
                    array("{loop.key}", "{loop.num}", "{loop.count}"),
                    array($key, $i, $i + 1),
                    $block
                );
            }

            // Add this finished block to the final return
            $final_out .= $block;
            ++$i;
        }

        // Return nested vars
        if(strpos($final_out, "<<!") !== false)
        {
            $final_out = str_replace(
                array("<<!", "!>>"),
                array($this->LDelim, $this->RDelim),
                $final_out
            );
        }
        return $final_out;
    }
}