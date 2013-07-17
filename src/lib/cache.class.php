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

	static private $enabled = FALSE;

/**
 * In memory cached data
 */

	static private $cache;

/**
 * Enable or disable cache
 * @param boolean Enabled
 */

	static function enable($enabled = TRUE)
	{
		self::$enabled = $enabled;
	}

/**
 * Returns cache size
 * @return integer
 */

	static function size()
	{
		$size = 0;
		$files = glob(EstatsCore::path(TRUE).'cache/'.(EstatsCore::statistics()?EstatsCore::statistics().'_':'').'*.dat');

		for ($i = 0, $c = count($files); $i < $c; ++$i)
		{
			$size += filesize($files[$i]);
		}

		return $size;
	}

/**
 * Returns path to cached file
 * @param string ID
 * @param string Extension
 * @return string
 */

	static function path($iD, $extension = '.dat')
	{
		return EstatsCore::path(TRUE).'cache/'.EstatsCore::statistics().'_'.$iD.'_'.EstatsCore::security().$extension;
	}

/**
 * Checks if data is available
 * @param string ID
 * @param string Extension
 * @return boolean
 */

	static function exists($iD, $extension = '.dat')
	{
		if (!self::$enabled)
		{
			return FALSE;
		}

		return file_exists(self::path($iD, $extension));
	}

/**
 * Returns file timestamp
 * @param string ID
 * @param string Extension
 * @return integer
 */

	static function timestamp($iD, $extension = '.dat')
	{
		return filemtime(self::path($iD, $extension));
	}

/**
 * Checks cache validity
 * @param string ID
 * @param integer Time
 * @param string Extension
 * @return boolean
 */

	static function status($iD, $time = 0, $extension = '.dat')
	{
		return (!isset(self::$cache[$iD]) && (!self::$enabled || !self::exists($iD, $extension) || ($time &&  ($_SERVER['REQUEST_TIME'] - self::timestamp($iD)) > $time)));
	}

/**
 * Reads serialized data from file
 * @param string ID
 * @param boolean Store
 * @return array
 */

	static function read($iD, $store = FALSE)
	{
		if (isset(self::$cache[$iD]))
		{
			return self::$cache[$iD];
		}
		else if (self::exists($iD))
		{
			$data = unserialize(file_get_contents(self::path($iD, '.dat')));

			if ($store)
			{
				self::$cache[$iD] = $data;
			}

			return $data;
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

	static function save($iD, $data, $store = FALSE)
	{
		if ($store || isset(self::$cache[$iD]))
		{
			self::$cache[$iD] = $data;
		}

		if (!self::$enabled)
		{
			return FALSE;
		}

		$fileName = self::path($iD, '.dat');

		if (!is_writable($fileName))
		{
			touch($fileName);
			chmod($fileName, 0666);
		}

		return (is_writable($fileName)?file_put_contents($fileName, serialize($data)):FALSE);
	}

/**
 * Delete files
 * @param string Pattern
 * @param string Extension
 * @return boolean
 */

	static function delete($pattern = '*', $extension = '{.dat,.png}')
	{
		$status = TRUE;
		$files = glob(EstatsCore::path(TRUE).'cache/'.(EstatsCore::statistics()?EstatsCore::statistics().'_':'').$pattern.'_'.EstatsCore::security().$extension, GLOB_BRACE);

		for ($i = 0, $c = count($files); $i < $c; ++$i)
		{
			if (is_file($files[$i]) && !unlink($files[$i]))
			{
				$status = FALSE;
			}
		}

		return $status;
	}
}
?>