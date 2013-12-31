<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Web/Template.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */

namespace System\Web;
use System\Http\WebRequest;
use System\IO\Path;
use System\Security\Session;

/**
 * The Template Engine of the cms
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Web
 */
class Template extends View
{
	/**
	 * The root path to the theme's folder
	 * @var string
	 */
	protected $themePath;

	/**
	 * The complete http path to the theme root
	 * @var string
	 */
	protected $themeUrl;

	/**
	 * Theme xml config object
	 * @var \SimpleXMLElement
	 */
	protected $themeConfig;

	/**
	 * The layout name to be used
	 * @var string
	 */
	protected $layoutName = 'default';

	/**
	 * Indicates whether the entire template will be rendered (header / footer)
	 * @var bool
	 */
	protected $renderFull = true;

	/**
	 * An array of lines to be injected into the layout head tags
	 * @var string[]
	 */
	protected $headers = array();

	/**
	 * Array of template messages
	 * @var array[] ('level', 'message')
	 */
	protected $messages = array();

	/**
	 * Array of views for the contents area
	 * @var View[]
	 */
	protected $views = array();

	/**
	 * Javascript Variables to be added in the header
	 * @var mixed[]
	 */
	protected $jsVars = array();

	/**
	 * The title of the page
	 * @var string
	 */
	protected $pageTitle;

	/**
	 * @var \System\Web\Breadcrumb
	 */
	public $breadcrumb;

	/**
	 * Constructor
	 *
	 * @param $themePath
	 * @param string $layoutName The name of the layout file (No extension)
	 *
	 * @throws \InvalidThemePathException
	 * @internal param $themeName
	 */
	public function __construct($themePath, $layoutName = "default")
	{
		// Make sure the path exists!
		if(!file_exists( Path::Combine($themePath, 'theme.xml') ))
			throw new \InvalidThemePathException('Invalid theme path "'. $themePath .'"');

		// Load theme paths etc
		$this->themePath = $themePath;
		$this->layoutName = $layoutName;
		$this->themeUrl = WebRequest::BaseUrl() . str_replace(array(ROOT, DS), array('', '/'), $themePath);

		// Make sure the layout file exists
		parent::loadFile($themePath . DS . "layouts" . DS . $layoutName . ".tpl");

		// Set page title
		$this->pageTitle = \Plexis::Config()->get("site_title");

		// Create a breadcrumb instance
		$this->breadcrumb = new Breadcrumb();
	}

	/**
	 * Builds the template and returns the output
	 *
	 * @return string
	 */
	public function render()
	{
		// Convert all of our views into html
		$buffer = '';
		foreach($this->views as $view)
			$buffer .= $view->render();

		// If rendering a full template
		if($this->renderFull)
		{
			//$contents = $this->contents;

			// Parse plexis tags (temporary till i input a better method)
			preg_match_all('~\{plexis::(.*)\}~iUs', $this->contents, $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				switch(trim(strtolower($match[1])))
				{
					case "head":
						$this->contents = str_replace($match[0], trim($this->buildHeader()), $this->contents);
						break;
					case "contents":
						$this->contents = str_replace($match[0], $buffer, $this->contents);
						break;
					case "messages":
						$this->contents = str_replace($match[0], $this->parseGlobalMessages(), $this->contents);
						break;
					case "elapsedtime":
						//$contents = str_replace($match[0], Benchmark::ElapsedTime('total_script_exec', 5), $this->contents);
						break;
				}
			}

			// Set template variables
			$this->variables['SITE_URL'] = SITE_URL;
			$this->variables['TEMPLATE_URL'] = $this->themeUrl;
			$this->variables['CSS_DIR'] = $this->themeUrl .'/css';
			$this->variables['JS_DIR'] = $this->themeUrl .'/js';
			$this->variables['IMG_DIR'] = $this->themeUrl .'/img';
			$this->variables['session'] = array(
				'id' => Session::GetId(),
				'data' => Session::GetUser()->asArray()
			);
			$this->variables['title'] = $this->pageTitle;

			// Render the layout
			return parent::render();
		}
		else
			return $buffer;
	}

	/**
	 * Specified whether or not to render the entire template
	 * @param bool $value
	 */
	public function renderLayout($value)
	{
		$this->renderFull = $value;
	}

	/**
	 * Appends the list of views to be rendered as the main content
	 *
	 * @param View $View The view to add to the main contents
	 *
	 * @return void
	 */
	public function addView(View $View)
	{
		$this->views[] = $View;
	}

	/**
	 * Sets the page title (After server title)
	 *
	 * @param string $title The title of the page
	 *
	 * @return void
	 */
	public function pageTitle($title)
	{
		$this->pageTitle .= " :: " . $title;
	}

	/**
	 * Displays a Popup message to be displayed to the client
	 *
	 * @param string $type The html class type ie: "error", "info", "warning" etc
	 * @param string $message The string message to display to the client
	 * @return void
	 */
	public function alert($type, $message)
	{

	}

	/**
	 * Adds a message to be displayed in the Global Messages container of the layout
	 *
	 * @param string $type The html class type ie: "error", "info", "warning" etc
	 * @param string $message The string message to display to the client
	 * @return void
	 */
	public function displayMessage($type, $message)
	{
		$this->messages[] = array($type, $message);
	}

	/**
	 * Loads a module view file from the theme's view folder, if it exists.
	 *
	 * The format for a module view inside the theme is: "views/$modulename/$viewname"
	 *
	 * @param string $ModuleName The name of the module
	 * @param string $ViewFileName The filename of the view file, including extension
	 * @param bool $HasJsFile [Reference Variable] References whether a view JS file was
	 *      found in the template files for this view file
	 *
	 * @return \System\Web\View
	 */
	public function loadModuleView($ModuleName, $ViewFileName, &$HasJsFile = false)
	{
		// Build path
		$Module = strtolower($ModuleName);
		$View = View::FromFile(Path::Combine($this->themePath, 'views', $Module, $ViewFileName .'.tpl'));

		// Get the JS file path
		$viewjs = Path::Combine($this->themePath, 'js', 'modules', $Module, $ViewFileName .'.js');

		// If the JS file exists in the template, include it!
		if(file_exists($viewjs))
		{
			$View->attachScriptScr($this->themeUrl . "/js/modules/{$Module}/{$ViewFileName}.js");
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
	public function loadPartial($name)
	{
		// Build path
		$path = Path::Combine($this->themePath, 'partials', $name .'.tpl');

		// Try and load the view
		return View::FromFile($path);
	}

	/**
	 * Clears the contents buffer of the template
	 */
	public function clearContents()
	{
		$this->views = array();
	}

	public function loadFile($filePath)
	{
		throw new \Exception("Unsupported Method");
	}

	/**
	 * Returns the current theme path
	 *
	 * @return string The path from the root to the theme folder.
	 */
	public function getThemePath()
	{
		return $this->themePath;
	}

	/**
	 * Returns the theme HTTP url to the root dir.
	 *
	 * @return string The path from the root to the theme folder.
	 */
	public function getThemeUrl()
	{
		return $this->themeUrl;
	}

	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Builds the plexis header
	 *
	 * @return string The rendered header data
	 */
	protected function buildHeader()
	{
		$base = WebRequest::BaseUrl();
		$Config = \Plexis::Config();

		// Convert our JS vars into a string :)
		$string =
			"        var Globals = {
			Url : '". SITE_URL ."',
			BaseUrl : '". $base ."',
			TemplateUrl : '". $this->themeUrl ."',
			Debugging : false,
			RealmId : 1,
		}\n";
		foreach($this->jsVars as $key => $val)
		{
			// Format the var based on type
			$val = (is_numeric($val)) ? $val : '"'. $val .'"';
			$string .= "        var ". $key ." = ". $val .";\n";
		}

		// Build Basic Headers
		$headers = array(
			'<!-- Basic Headings -->',
			'<title>'. $Config['site_title'] .'</title>',
			'<meta name="keywords" content="'. $Config['keywords'] .'"/>',
			'<meta name="description" content="'. $Config['description'] .'"/>',
			'<meta name="generator" content="Plexis"/>',
			'', // Add Whitespace
			'<!-- Content type, And cache control -->',
			'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>',
			'<meta http-equiv="Cache-Control" content="no-cache"/>',
			'<meta http-equiv="Expires" content="-1"/>',
			'', // Add Whitespace
			'<!-- Include jQuery Scripts -->',
			'<script type="text/javascript" src="'. $base .'/assets/jquery.js"></script>',
			'<script type="text/javascript" src="'. $base .'/assets/jquery-ui.js"></script>',
			'<script type="text/javascript" src="'. $base .'/assets/jquery.validate.js"></script>',
			'', // Add Whitespace
			'<!-- Define Global Vars and Include Plexis Static JS Scripts -->',
			"<script type=\"text/javascript\">\n". rtrim($string) ."\n    </script>",
			'<script type="text/javascript" src="'. $base .'/assets/plexis.js"></script>',
			'' // Add Whitespace
		);

		// Merge user added headers
		if(!empty($this->headers))
		{
			$headers[] = '';
			$headers[] = '<!-- Controller Added -->';
			$headers = array_merge($headers, array_unique($this->headers));
		}

		return implode("\n    ", $headers);
	}

	/**
	 * Parse the global messages for the template renderer
	 *
	 * @return string The parsed global message contents
	 */
	protected function parseGlobalMessages()
	{
		// Load the global_messages view
		$View = View::FromFile( Path::Combine($this->themePath, 'partials', 'message.tpl') );
		$buffer = '';

		// Loop through and add each message to the buffer
		$size = sizeof($this->messages);
		for($i = 0; $i < $size; $i++)
		{
			$View->set('level', $this->messages[$i][0]);
			$View->set('message', $this->messages[$i][1]);
			$buffer .= $View->render();
		}

		return rtrim($buffer, PHP_EOL);
	}
}