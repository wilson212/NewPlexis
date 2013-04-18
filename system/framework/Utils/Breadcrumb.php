<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Utils/Breadcrumb.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Utils;

/**
 * A breadcrumb building class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Utils
 */
class Breadcrumb
{
    protected static $breadcrumbs = array();
    
    public static function Append($text, $href) 
    {
        self::$breadcrumbs[] = array(
            'text' => $text,
            'href' => $href
        );
    }
    
    public function Generate($ulClass = 'breadcrumb', $liClass = null, $divider = "") {}
    
    public static function GenerateListsOnly( $cssClass = "breadcrum", $divider = "" ) 
    {
        $string = null;
        $count = count(self::$breadcrumbs) -1;
        foreach(self::$breadcrumbs as $k => $b)
        {
            $class = ($cssClass != null) ? " class={$cssClass}" : '';
            $string .= ($k == $count) ? "<li{$class}>{$b['text']}</li>" : "<li{$class}><a href=\"{$b['href']}\">{$b['text']}</a></li>". $divider;
        }
        
        return rtrim($string, $divider);
    }
    
    public function GenerateString($tpl, $first_tpl = null) {}
    
    public static function getList()
    {
        return self::$breadcrumbs;
    }
}