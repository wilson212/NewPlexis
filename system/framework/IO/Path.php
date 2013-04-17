<?php
/**
 * Plexis Content Management System
 *
 * @file        System/Framework/IO/Path.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 * @author      Plexis Dev Team
 * @package     System
 * @subpackage  IO
 */
namespace System\IO;

class Path
{
	/**
	 * Changes the extension of a path string
	 *
	 * @param string $path The path information to modify
	 * @param string $extension The new extension. Leave null to remove extension
	 *
	 * @return string Returns the full path using the correct system 
	 *   directory separator
	 */
	public static function ChangeExtension($path, $extension)
	{
		// If the path has an extension, change it
		if(($pos = strripos($path, '.')) !== false)
		{
			$parts = substr($path, 0, $pos);
			return (empty($extension)) ? $parts : $parts .'.'. ltrim($extension, '.');
		}
		else
		{
			// Add extension
			return (empty($extension)) ? $path : $path .'.'. ltrim($extension, '.');
		}
	}
	
	/**
	 * Combines several string arguments into a file path.
	 *
	 * @param string|string[] $args The pieces of the path, passed as 
	 *   individual arguments. Each argument can be a single dimmensional 
	 *   array of paths, a string folder / filename, or a mixture of the two.
	 *   Dots may also be passed ( . & .. ) to change directory levels
	 *
	 * @return string Returns the full path using the correct system 
	 *   directory separater
	 */
	public static function Combine($args)
	{
		// Get our path parts
        $args = func_get_args();
        $parts = array();
        
        // Trim our paths to remvove spaces and new lines
        foreach($args as $part)
        {
            // If part is array, then implode and continue
            if(is_array($part))
            {
                // Remove empty entries
                $part = array_filter($part, 'strlen');
                $parts[] = implode(DIRECTORY_SEPARATOR, $part);
                continue;
            }
            
            // String
            $part = trim($part);
            if($part == '.' || empty($part))
                continue;
            elseif($part == '..')
                array_pop($parts);
            else
                $parts[] = $part;
        }

        // Get our cleaned path into a variable with the correct directory seperator
        return implode( DIRECTORY_SEPARATOR, $parts );
	}
	
	/**
	 * Returns the directory name for the specified path string.
	 *
	 * @param string $path The path we are getting the directory name for
	 *
	 * @return string Returns the full path using the correct system 
	 *   directory separater
	 */
	public static function GetDirectoryName($path)
	{
		return dirname($path);
	}
	
	/**
	 * Returns the extension of the specified path string.
	 *
	 * @param string $path The filepath we are getting the extension for
	 *
	 * @return string
	 */
	public static function GetExtension($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}
	
	/**
	 * Returns the file name and extension of the specified path string
	 *
	 * @param string $path The filepath we are getting the name of
	 *
	 * @return string
	 */
	public static function GetFilename($path)
	{
		return basename($path);
	}
	
	/**
	 * Returns the file name of the specified path string without the extension
	 *
	 * @param string $path The filepath we are getting the name of
	 *
	 * @return string
	 */
	public static function GetFilenameWithoutExtension($path)
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}
}