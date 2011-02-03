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

	static private $Theme;

/**
 * Theme information
 */

	static private $Information;

/**
 * Theme elements
 */

	static private $Elements;

/**
 * Theme switches
 */

	static private $Switches;

/**
 * List of available themes
 */

	static private $Available = NULL;

/**
 * Sets default theme
 * @param string Theme
 * @return boolean
 */

	static function set($Theme)
	{
		$FileName = './share/themes/'.$Theme.'/theme.ini';

		if (!is_file($FileName))
		{
			return FALSE;
		}

		self::$Theme = $Theme;
		self::$Information = parse_ini_file($FileName, FALSE);
		self::$Elements['index'] = file_get_contents('./share/themes/'.$Theme.'/theme.tpl');
		self::$Switches = array();

		return TRUE;
	}

/**
 * Loads theme file
 * @param string File
 * @param string Directory
 * @return boolean
 */

	static function load($File, $Directory = '')
	{
		$FileName = ($Directory?$Directory:'./share/themes/').self::$Theme.'/'.$File.'.tpl';

		if (!is_file($FileName))
		{
			$FileName = ($Directory?$Directory:'./share/themes/').'common/'.$File.'.tpl';
		}

		if (!is_file($FileName))
		{
			return FALSE;
		}

		$Theme = file_get_contents($FileName);

		preg_match_all('#\[start:(.*?)\](.*?)\[/end\]#si', $Theme, $Blocks);

		for ($i = 0, $c = count($Blocks[0]); $i < $c; ++$i)
		{
			self::$Elements[$Blocks[1][$i]] = $Blocks[2][$i];
		}

		return TRUE;
	}

/**
 * Returns list of available themes
 * @return array
 */

	static function available()
	{
		if (self::$Available)
		{
			return self::$Available;
		}

		$Array = array();
		$Themes = glob('./share/themes/*/theme.ini');

		for ($i = 0, $c = count($Themes); $i < $c; ++$i)
		{
			$Information = parse_ini_file($Themes[$i], FALSE);
			$Array[basename(dirname($Themes[$i]))] = $Information['Name'];
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
 * Returns theme element
 * @param string Element
 * @return string
 */

	static function get($Element)
	{
		return (isset(self::$Elements[$Element])?self::$Elements[$Element]:(isset(self::$Switches[$Element])?self::$Switches[$Element]:''));
	}

/**
 * Adds theme element or switch
 * @param string Element
 * @param mixed Value
 */

	static function add($Element, $Value)
	{
		if (is_bool($Value))
		{
			self::$Switches[$Element] = $Value;
		}
		else
		{
			self::$Elements[$Element] = $Value;
		}
	}

/**
 * Appends to theme element
 * @param string Element
 * @param string String
 */

	static function append($Element, $String)
	{
		if (isset(self::$Elements[$Element]))
		{
			self::$Elements[$Element].= $String;
		}
		else
		{
			self::$Elements[$Element] = $String;
		}
	}

/**
 * Links two theme elements
 * @param string Element
 * @param string String
 */

	static function link($From, $To)
	{
		if (isset(self::$Elements[$From]))
		{
			self::$Elements[$To] = &self::$Elements[$From];
		}
		else
		{
			self::$Elements[$To] = '';
		}
	}

/**
 * Checks if theme contains element
 * @param string Element
 */

	static function contains($Element)
	{
		return isset(self::$Elements[$Element]);
	}

/**
 * Parses theme element
 * @param string String
 * @param array Elements
 * @param array Switches
 * @return string
 */

	static function parse($String, $Elements = NULL, $Switches = FALSE)
	{
		if ($Elements == NULL)
		{
			$Elements = &self::$Elements;
		}

		if ($Switches)
		{
			if (!is_array($Switches))
			{
				$Switches = &self::$Switches;
			}

			foreach ($Switches as $Key => $Value)
			{
				$String = preg_replace(array('#<!--start:'.$Key.'-->(.*?)<!--end:'.$Key.'-->#si', '#<!--start:!'.$Key.'-->(.*?)<!--end:!'.$Key.'-->#si'), array(($Value?'\\1':''), ($Value?'':'\\1')), $String);
			}
		}

		foreach ($Elements as $Key => $Value)
		{
			$String = str_replace('{'.$Key.'}', $Value, $String);
		}

		return $String;
	}

/**
 * Checks if theme exists
 * @param string Theme
 * @return boolean
 */

	static function exists($Theme)
	{
		return file_exists('./share/themes/'.$Theme.'/theme.ini');
	}
}
?>