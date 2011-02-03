<?php
/**
 * Data cache class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.07
 */

class EstatsCache
{

/**
 * Defines if cache is enabled
 */

	static private $Enabled = FALSE;

/**
 * In memory cached data
 */

	static private $Cache;

/**
 * Set configuration
 * @param boolean Enabled
 */

	static function configure($Enabled = TRUE)
	{
		self::$Enabled = $Enabled;
	}

/**
 * Returns cache size
 * @return integer
 */

	static function size()
	{
		$Size = 0;
		$Files = glob(EstatsCore::path('data').'cache/*.dat');

		for ($i = 0, $c = count($Files); $i < $c; ++$i)
		{
			$Size += filesize($Files[$i]);
		}

		return $Size;
	}

/**
 * Returns path to cached file
 * @param string ID
 * @param string Extension
 * @return string
 */

	static function path($ID, $Extension = '.dat')
	{
		return EstatsCore::path('data').'cache/'.$ID.'_'.EstatsCore::security().$Extension;
	}

/**
 * Checks if data is available
 * @param string ID
 * @param string Extension
 * @return boolean
 */

	static function exists($ID, $Extension = '.dat')
	{
		if (!self::$Enabled)
		{
			return FALSE;
		}

		return file_exists(self::path($ID, $Extension));
	}

/**
 * Returns file timestamp
 * @param string ID
 * @param string Extension
 * @return integer
 */

	static function timestamp($ID, $Extension = '.dat')
	{
		return filemtime(self::path($ID, $Extension));
	}

/**
 * Checks cache validity
 * @param string ID
 * @param integer Time
 * @param string Extension
 * @return boolean
 */

	static function status($ID, $Time = 0, $Extension = '.dat')
	{
		return (!isset(self::$Cache[$ID]) && (!self::$Enabled || !self::exists($ID, $Extension) || ($Time &&  ($_SERVER['REQUEST_TIME'] - self::timestamp($ID)) > $Time)));
	}

/**
 * Reads serialized data from file
 * @param string ID
 * @param boolean Store
 * @return array
 */

	static function read($ID, $Store = FALSE)
	{
		if (isset(self::$Cache[$ID]))
		{
			return self::$Cache[$ID];
		}
		else if (self::exists($ID))
		{
			$Data = unserialize(file_get_contents(self::path($ID, '.dat')));

			if ($Store)
			{
				self::$Cache[$ID] = $Data;
			}

			return $Data;
		}
		else
		{
			return array();
		}
	}

/**
 * Writes serialized data to file
 * @param string ID
 * @param array Data
 * @param boolean Store
 * @return boolean
 */

	static function save($ID, $Data, $Store = FALSE)
	{
		if ($Store || isset(self::$Cache[$ID]))
		{
			self::$Cache[$ID] = $Data;
		}

		if (!self::$Enabled)
		{
			return FALSE;
		}

		$FileName = self::path($ID, '.dat');

		if (!is_writable($FileName))
		{
			touch($FileName);
			chmod($FileName, 0666);
		}

		return (is_writable($FileName)?file_put_contents($FileName, serialize($Data)):FALSE);
	}

/**
 * Delete files
 * @param string Pattern
 * @param string Extension
 * @return boolean
 */

	static function delete($Pattern = '*', $Extension = '{.dat,.png}')
	{
		$Status = TRUE;
		$Files = glob(EstatsCore::path('data').'cache/'.$Pattern.'_'.EstatsCore::security().$Extension, GLOB_BRACE);

		for ($i = 0, $c = count($Files); $i < $c; ++$i)
		{
			if (is_file($Files[$i]) && !unlink($Files[$i]))
			{
				$Status = FALSE;
			}
		}

		return $Status;
	}
}
?>