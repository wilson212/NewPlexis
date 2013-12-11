<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Http/Url.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Http;

/**
 * Url Class
 *
 * @author      Steven Wilson
 * @package     System
 * @subpackage
 */
class Url
{
    /**
     * Converts a URI query string to a full URL
     *
     * @param string $Uri The URI string path
     *
     * @return string
     */
    public static function Create($Uri)
    {
        // If uri is empty, return base url
        $Uri = trim($Uri, '/');
        if(empty($Uri))
            return WebRequest::GetInitial()->baseUrl();

        // if this is a legit url, just return it
        if(preg_match('@^((mailto|ftp|http(s)?)://|www\.)@i', $Uri))
            return $Uri;

        // Fetch config, and parse the URI
        $Config = \Plexis::Config();
        if($Config["enable_query_strings"])
        {
            // convert the paths to query vars
            $parts = explode('/', $Uri);
            $Uri = "?m=". $parts[0];

            // Append controller and action
            if(isset($parts[1]))
                $Uri .= "&c=". $parts[1];
            if(isset($parts[2]))
                $Uri .= "&a=". $parts[1];
            if(isset($parts[3]))
                $Uri .= "&params=". implode('/', array_slice($parts, 3));
        }
        elseif(!MOD_REWRITE)
            // prepend URI
            $Uri = "?uri=". $Uri;

        // Return properly formatted URL
        return WebRequest::GetInitial()->baseUrl() . '/'. $Uri;
    }
}