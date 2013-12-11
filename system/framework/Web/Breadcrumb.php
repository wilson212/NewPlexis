<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Utils/Breadcrumb.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Web;

/**
 * A breadcrumb building class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Utils
 */
class Breadcrumb
{
    protected $breadcrumbs = array();
    
    public function append($text, $href)
    {
        $this->breadcrumbs[] = array(
            'text' => $text,
            'href' => $href
        );
    }

    public function set($crumbs)
    {
        $this->breadcrumbs = array();
        foreach($crumbs as $name => $link)
        {
            $this->breadcrumbs[] = array(
                'text' => $name,
                'href' => $link
            );
        }
    }
    
    public function generateListsOnly( $cssClass = "breadcrum", $divider = "" )
    {
        $string = null;
        $count = count($this->breadcrumbs) -1;
        foreach($this->breadcrumbs as $k => $b)
        {
            $class = ($cssClass != null) ? " class={$cssClass}" : '';
            $string .= ($k == $count)
                ? "<li{$class}>{$b['text']}</li>"
                : "<li{$class}><a href=\"{$b['href']}\">{$b['text']}</a></li>". $divider;
        }
        
        return rtrim($string, $divider);
    }
    
    public function getList()
    {
        return $this->breadcrumbs;
    }
}