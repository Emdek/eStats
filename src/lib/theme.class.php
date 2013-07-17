<?php
/**
 * Theme class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.05
 */

class EstatsTheme
{

/**
 * Current theme
 */

	static private $theme;

/**
 * Theme information
 */

	static private $information;

/**
 * Theme elements
 */

	static private $elements;

/**
 * Theme switches
 */

	static private $switches;

/**
 * List of available themes
 */

	static private $available = NULL;

/**
 * Sets default theme
 * @param string Theme
 * @return boolean
 */

	static function set($theme)
	{
		$fileName = './share/themes/'.$theme.'/theme.ini';

		if (!is_file($fileName))
		{
			return FALSE;
		}

		self::$theme = $theme;
		self::$information = parse_ini_file($fileName, FALSE);
		self::$elements['index'] = file_get_contents('./share/themes/'.$theme.'/theme.tpl');
		self::$switches = array();

		return TRUE;
	}

/**
 * Loads theme file
 * @param string File
 * @param string Directory
 * @return boolean
 */

	static function load($file, $directory = '')
	{
		$fileName = ($directory?$directory:'./share/themes/').self::$theme.'/'.$file.'.tpl';

		if (!is_file($fileName))
		{
			$fileName = ($directory?$directory:'./share/themes/').'common/'.$file.'.tpl';
		}

		if (!is_file($fileName))
		{
			return FALSE;
		}

		$theme = file_get_contents($fileName);

		preg_match_all('#\[start:(.*?)\](.*?)\[/end\]#si', $theme, $blocks);

		for ($i = 0, $c = count($blocks[0]); $i < $c; ++$i)
		{
			self::$elements[$blocks[1][$i]] = $blocks[2][$i];
		}

		return TRUE;
	}

/**
 * Returns list of available themes
 * @return array
 */

	static function available()
	{
		if (self::$available)
		{
			return self::$available;
		}

		$array = array();
		$themes = glob('./share/themes/*/theme.ini');

		for ($i = 0, $c = count($themes); $i < $c; ++$i)
		{
			$information = parse_ini_file($themes[$i], FALSE);
			$array[basename(dirname($themes[$i]))] = $information['Name'];
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
 * Returns theme element
 * @param string Element
 * @return string
 */

	static function get($element)
	{
		return (isset(self::$elements[$element])?self::$elements[$element]:(isset(self::$switches[$element])?self::$switches[$element]:''));
	}

/**
 * Adds theme element or switch
 * @param string Element
 * @param mixed Value
 */

	static function add($element, $value)
	{
		if (is_bool($value))
		{
			self::$switches[$element] = $value;
		}
		else
		{
			self::$elements[$element] = $value;
		}
	}

/**
 * Appends to theme element
 * @param string Element
 * @param string String
 */

	static function append($element, $string)
	{
		if (isset(self::$elements[$element]))
		{
			self::$elements[$element].= $string;
		}
		else
		{
			self::$elements[$element] = $string;
		}
	}

/**
 * Links two theme elements
 * @param string Element
 * @param string String
 */

	static function link($from, $to)
	{
		if (isset(self::$elements[$from]))
		{
			self::$elements[$to] = &self::$elements[$from];
		}
		else
		{
			self::$elements[$to] = '';
		}
	}

/**
 * Checks if theme contains element
 * @param string Element
 */

	static function contains($element)
	{
		return isset(self::$elements[$element]);
	}

/**
 * Parses theme element
 * @param string String
 * @param array Elements
 * @param array Switches
 * @return string
 */

	static function parse($string, $elements = NULL, $switches = FALSE)
	{
		if ($elements == NULL)
		{
			$elements = &self::$elements;
		}

		if ($switches)
		{
			if (!is_array($switches))
			{
				$switches = &self::$switches;
			}

			foreach ($switches as $key => $value)
			{
				$string = preg_replace(array('#<!--start:'.$key.'-->(.*?)<!--end:'.$key.'-->#si', '#<!--start:!'.$key.'-->(.*?)<!--end:!'.$key.'-->#si'), array(($value?'\\1':''), ($value?'':'\\1')), $string);
			}
		}

		foreach ($elements as $key => $value)
		{
			$string = str_replace('{'.$key.'}', $value, $string);
		}

		return $string;
	}

/**
 * Checks if theme exists
 * @param string Theme
 * @return boolean
 */

	static function exists($theme)
	{
		return file_exists('./share/themes/'.$theme.'/theme.ini');
	}
}
?>