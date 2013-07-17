<?php
/**
 * Locale class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.11
 */

class EstatsLocale
{

/**
 * Current locale
 */

	static private $locale;

/**
 * Gettext availability indicator
 */

	static private $gettext = NULL;

/**
 * Optional translation table
 */

	static private $translation = NULL;

/**
 * List of available locales
 */

	static private $available = NULL;

/**
 * Locale information
 */

	static private $information;

/**
 * Sets default locale
 * @param string Locale
 * @param boolean Gettext
 * @return boolean
 */

	static public function set($locale, $gettext = NULL)
	{
		$fileName = './locale/'.$locale.'/locale.ini';

		if (!is_file($fileName))
		{
			return FALSE;
		}

		self::$locale = $locale;
		self::$information = parse_ini_file($fileName, FALSE);

		setlocale(LC_ALL, explode('|', self::$information['Locale']));

		if (stristr(PHP_OS, 'win'))
		{
			putenv('LANG='.$locale);
			putenv('LANGUAGE='.$locale);
		}

		if ($gettext !== NULL)
		{
			self::$gettext = $gettext;
		}

		self::load();

		return TRUE;
	}

/**
 * Loads default locale translations
 * @param string Directory
 */

	static function load($directory = '')
	{
		if (self::$gettext === NULL)
		{
			self::$gettext = extension_loaded('gettext');
		}

		if (!$directory && self::$gettext && is_file('./locale/'.self::$locale.'/LC_MESSAGES/estats.mo'))
		{
			bindtextdomain('estats', './locale/');
			textdomain('estats');
			bind_textdomain_codeset('estats', 'UTF-8');
		}
		else
		{
			if (self::$locale == 'en')
			{
				return;
			}

			$path = $directory.($directory?'':'./locale/').self::$locale.($directory?'':'/locale').'.dat';

			if (!is_file($path))
			{
				return;
			}

			$translation = unserialize(file_get_contents($path));

			if (self::$translation !== NULL)
			{
				self::$translation = array_merge($translation, self::$translation);
			}
			else
			{
				self::$translation = $translation;
			}
		}
	}

/**
 * Returns list of available locales
 * @return array
 */

	static function available()
	{
		if (self::$available)
		{
			return self::$available;
		}

		$array = array();
		$locales = glob('./locale/*/locale.ini');

		for ($i = 0, $c = count($locales); $i < $c; ++$i)
		{
			$information = parse_ini_file($locales[$i], FALSE);
			$array[basename(dirname($locales[$i]))] = $information['Name'];
		}

		self::$available = $array;

		return $array;
	}

/**
 * Returns option value
 * @param string Option
 * @return string
 */

	static function option($option)
	{
		return (isset(self::$information[$option])?self::$information[$option]:'');
	}

/**
 * Returns translated string if found
 * @param string String
 * @return string
 */

	static function translate($string)
	{
		if (self::$gettext)
		{
			return gettext($string);
		}
		else
		{
			return (isset(self::$translation[$string])?self::$translation[$string]:$string);
		}
	}

/**
 * Checks if locale exists
 * @param string Locale
 * @return boolean
 */

	static function exists($locale)
	{
		return file_exists('./locale/'.$locale.'/locale.ini');
	}
}
?>