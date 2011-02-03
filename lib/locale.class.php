<?php
/**
 * Locale class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.05
 */

class EstatsLocale
{

/**
 * Current locale
 */

	static private $Locale;

/**
 * Gettext availability indicator
 */

	static private $Gettext = NULL;

/**
 * Optional translation table
 */

	static private $Translation = NULL;

/**
 * List of available locales
 */

	static private $Available = NULL;

/**
 * Locale information
 */

	static private $Information;

/**
 * Sets default locale
 * @param string Locale
 * @return boolean
 */

	static public function set($Locale)
	{
		$FileName = './locale/'.$Locale.'/locale.ini';

		if (!is_file($FileName))
		{
			return FALSE;
		}

		self::$Locale = $Locale;
		self::$Information = parse_ini_file($FileName, FALSE);

		setlocale(LC_ALL, explode('|', self::$Information['Locale']));

		if (stristr(PHP_OS, 'win'))
		{
			putenv('LANG='.$Locale);
			putenv('LANGUAGE='.$Locale);
		}

		self::load();

		return TRUE;
	}

/**
 * Loads default locale translations
 * @param string Directory
 */

	static function load($Directory = '')
	{
		if (self::$Gettext === NULL)
		{
			self::$Gettext = extension_loaded('gettext');
		}

		if (!$Directory && self::$Gettext && is_file('./locale/'.self::$Locale.'/LC_MESSAGES/estats.mo'))
		{
			bindtextdomain('estats', './locale/');
			textdomain('estats');
			bind_textdomain_codeset('estats', 'UTF-8');
		}
		else
		{
			if (self::$Locale == 'en')
			{
				return;
			}

			$Path = $Directory.($Directory?'':'./locale/').self::$Locale.($Directory?'':'/locale').'.php';

			if (!is_file($Path))
			{
				return;
			}

			include ($Path);

			if (self::$Translation !== NULL)
			{
				self::$Translation = array_merge($Translation, self::$Translation);
			}
			else
			{
				self::$Translation = $Translation;
			}
		}
	}

/**
 * Returns list of available locales
 * @return array
 */

	static function available()
	{
		if (self::$Available)
		{
			return self::$Available;
		}

		$Array = array();
		$Locales = glob('./locale/*/locale.ini');

		for ($i = 0, $c = count($Locales); $i < $c; ++$i)
		{
			$Information = parse_ini_file($Locales[$i], FALSE);
			$Array[basename(dirname($Locales[$i]))] = $Information['Name'];
		}

		self::$Available = $Array;

		return $Array;
	}

/**
 * Returns option value
 * @param string Option
 * @return string
 */

	static function option($Option)
	{
		return (isset(self::$Information[$Option])?self::$Information[$Option]:'');
	}

/**
 * Returns translated string if found
 * @param string String
 * @return string
 */

	static function translate($String)
	{
		if (self::$Gettext)
		{
			return gettext($String);
		}
		else
		{
			return (isset(self::$Translation[$String])?self::$Translation[$String]:$String);
		}
	}

/**
 * Checks if locale exists
 * @param string Locale
 * @return boolean
 */

	static function exists($Locale)
	{
		return file_exists('./locale/'.$Locale.'/locale.ini');
	}
}
?>