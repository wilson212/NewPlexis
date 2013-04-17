<?php

namespace System\Config;

class ConfigManager
{
	const TYPE_PHP = 0;
	const TYPE_INI = 1;
	const TYPE_XML = 2;
	
	public static function Load($filename, $configType = self::TYPE_PHP)
	{
	
	}
}