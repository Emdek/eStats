<?php
/**
 * Cookies management class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.06
 */

class EstatsCookie
{

/**
 * Cookies data
 */

	static private $cookies;

/**
 * Server name
 */

	static private $server = NULL;

/**
 * Checks if cookie exists
 * @param string Key
 * @return boolean
 */

	static function exists($key)
	{
		return isset($_COOKIE[md5(EstatsCore::security().$key)]);
	}

/**
 * Returns value of cookie
 * @param string Key
 * @return mixed
 */

	static function get($key)
	{
		$name = md5(EstatsCore::security().$key);

		if (!isset($_COOKIE[$name]))
		{
			return NULL;
		}

		if (isset(self::$cookies[$key]))
		{
			return self::$cookies[$key];
		}

		self::$cookies[$key] = unserialize(stripslashes($_COOKIE[$name]));

		return self::$cookies[$key];
	}

/**
 * Sets cookie
 * @param string Key
 * @param mixed Value
 * @param integer Time
 * @param string Path
 */

	static function set($key, $value, $time = 31356000, $path = '')
	{
		if (empty(self::$server))
		{
			self::$server = ((substr($_SERVER['SERVER_NAME'], 0, 4) == 'www.')?substr($_SERVER['SERVER_NAME'], 4):$_SERVER['SERVER_NAME']);
		}

		$name = md5(EstatsCore::security().$key);

		setcookie($name, serialize($value), ($_SERVER['REQUEST_TIME'] + $time), ($path?$path:'/'), self::$server);

		self::$cookies[$key] = $value;

		$_COOKIE[$name] = serialize($value);
	}

/**
 * Deletes cookie
 * @param string Key
 * @param string Path
 */

	static function delete($key, $path = '')
	{
		$name = md5(EstatsCore::security().$key);

		setcookie($name, '', 1, ($path?$path:'/'), self::$server);

		if (isset(self::$cookies[$key]))
		{
			unset(self::$cookies[$key]);
		}

		if (isset($_COOKIE[$name]))
		{
			unset($_COOKIE[$name]);
		}
	}
}
?>