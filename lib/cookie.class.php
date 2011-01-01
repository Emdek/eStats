<?php
/**
 * Cookies management class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.05
 */

class EstatsCookie
{

/**
 * Cookies data
 */

	static private $Cookies;

/**
 * Security string
 */

	static private $Security;

/**
 * Server name
 */

	static private $Server;

/**
 * Set configuration
 * @param string Security
 */

	static function configure($Security)
	{
		self::$Security = $Security;
		self::$Server = ((substr($_SERVER['SERVER_NAME'], 0, 4) == 'www.')?substr($_SERVER['SERVER_NAME'], 4):$_SERVER['SERVER_NAME']);
	}

/**
 * Checks if cookie exists
 * @param string Key
 * @return boolean
 */

	static function exists($Key)
	{
		return isset($_COOKIE[md5(self::$Security.$Key)]);
	}

/**
 * Returns value of cookie
 * @param string Key
 * @return mixed
 */

	static function get($Key)
	{
		$Name = md5(self::$Security.$Key);

		if (!isset($_COOKIE[$Name]))
		{
			return NULL;
		}

		if (isset(self::$Cookies[$Key]))
		{
			return self::$Cookies[$Key];
		}

		self::$Cookies[$Key] = unserialize(stripslashes($_COOKIE[$Name]));

		return self::$Cookies[$Key];
	}

/**
 * Sets cookie
 * @param string Key
 * @param mixed Value
 * @param integer Time
 * @param string Path
 */

	static function set($Key, $Value, $Time = 31356000, $Path = '')
	{
		$Name = md5(self::$Security.$Key);

		setcookie($Name, serialize($Value), ($_SERVER['REQUEST_TIME'] + $Time), ($Path?$Path:'/'), self::$Server);

		self::$Cookies[$Key] = $Value;

		$_COOKIE[$Name] = serialize($Value);
	}

/**
 * Deletes cookie
 * @param string Key
 * @param string Path
 */

	static function delete($Key, $Path = '')
	{
		$Name = md5(self::$Security.$Key);

		setcookie($Name, '', 1, ($Path?$Path:'/'), self::$Server);

		if (isset(self::$Cookies[$Key]))
		{
			unset(self::$Cookies[$Key]);
		}

		if (isset($_COOKIE[$Name]))
		{
			unset($_COOKIE[$Name]);
		}
	}
}
?>