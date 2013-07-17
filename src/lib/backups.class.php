<?php
/**
 * Backups management class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.07
 */

class EstatsBackups
{

/**
 * Returns backups size
 * @return integer
 */

	static function size()
	{
		$size = 0;
		$files = glob(EstatsCore::path(TRUE).'backups/*.bak');

		for ($i = 0, $c = count($files); $i < $c; ++$i)
		{
			$size += filesize($files[$i]);
		}

		return $size;
	}

/**
 * Returns backups amount
 * @return integer
 */

	static function amount()
	{
		return count(glob(EstatsCore::path(TRUE).'backups/*.bak'));
	}

/**
 * Returns list of available backups
 * @param string Profile
 * @return array
 */

	static function available($profile)
	{
		return glob(EstatsCore::path(TRUE).'backups/*.'.$profile.'.bak', GLOB_BRACE);
	}

/**
 * Creates backup
 * @param string Version
 * @param string Profile
 * @param array Tables
 * @param boolean ReplaceData
 * @return string
 */

	static function create($version = '', $profile = '', $tables = NULL, $replaceData = TRUE)
	{
		$status = TRUE;
		$backupID = EstatsCore::option('CollectedFrom').'-'.$_SERVER['REQUEST_TIME'].'.'.$profile;
		$fileName = EstatsCore::path(TRUE).'backups/'.$backupID.'.bak';

		if (touch($fileName))
		{
			chmod($fileName, 0666);
			file_put_contents($fileName, '/*
eStats v'.$version.' database backup
Mode: '.$profile.($replaceData?' (replace data)':'').'
Time range: '.date('m.d.Y H:i:s', EstatsCore::option('CollectedFrom')).' - '.date('m.d.Y H:i:s').'
Database: '.EstatsCore::driver()->option('Database').((EstatsCore::driver()->option('DatabaseVersion') != '?')?' '.EstatsCore::driver()->option('DatabaseVersion'):'').'
Module: '.EstatsCore::driver()->option('Name').' v'.EstatsCore::driver()->option('Version').' ('.EstatsCore::driver()->option('URL').')
*/

');

			$schema = EstatsCore::loadData('share/data/database.ini');

			if (!$tables)
			{
				$tables = array_keys($schema);

				if ($profile == 'data')
				{
					unset($tables[array_search('configuration')]);
					unset($tables[array_search('logs')]);
				}
			}

			for ($i = 0, $c = count($tables); $i < $c; ++$i)
			{
				$result = EstatsCore::driver()->selectData(array($tables[$i]), NULL, NULL, 0, 0, NULL, NULL, NULL, FALSE, EstatsDriver::RETURN_OBJECT);
				$amount = count($schema[$tables[$i]]);

				if (!file_put_contents($fileName, '
/*Table: '.$tables[$i].'*/

', FILE_APPEND))
				{
					$status = FALSE;
				}

				while ($result && ($row = $result->fetch(PDO::FETCH_NUM)))
				{
					$values = array();

					for ($k = 0; $k < $amount; ++$k)
					{
						$values[] = strtr($row[$k], array(
	"\r" => '\r',
	"\n" => '\n',
	chr(30) => ''
	));
					}

					if (!file_put_contents($fileName, implode(chr(30), $values).'
', FILE_APPEND))
					{
						$status = FALSE;
					}
				}
			}
		}
		else
		{
			$status = FALSE;
		}

		return ($status?$backupID:FALSE);
	}

/**
 * Restores backup
 * @param string BackupID
 * @return boolean
 */

	static function restore($backupID)
	{
		$file = fopen(EstatsCore::path(TRUE).'backups/'.$backupID.'.bak', 'r');

		if ($file == FALSE)
		{
			return FALSE;
		}

		$status = TRUE;
		$buffer = '';
		$replace = $recreate = $create = $table = $fields = $line = 0;
		$schema = EstatsCore::loadData('share/data/database.ini');

		while (!feof($file))
		{
			$byte = fread($file, 1);

			if ($byte == "\n")
			{
				if (!$buffer || $line < 10)
				{
					if ($line == 2)
					{
						if (preg_match('#eStats v[\d\.]+ database backup#', $buffer))
						{
							EstatsCore::driver()->beginTransaction();
						}
						else
						{
							return FALSE;
						}
					}
					else if ($line == 3)
					{
						if (strstr($buffer, 'replace data'))
						{
							$replace = TRUE;
						}

						if (strstr($buffer, 'create tables'))
						{
							if (strstr ($buffer, 'recreate tables'))
							{
								$recreate = TRUE;
							}
							else
							{
								$create = TRUE;
							}
						}
					}

					++$line;

					$buffer = '';

					continue;
				}

				if (substr($buffer, 0, 8) == '/*Table:')
				{
					$table = substr($buffer, 9, -2);
					$fields = NULL;
					$amount = 0;

					if ($replace)
					{
						EstatsCore::driver()->deleteData($table);
					}
					else if ($recreate || $create)
					{
						if (EstatsCore::driver()->tableExists($table))
						{
							if ($create)
							{
								return FALSE;
							}
							else
							{
								EstatsCore::driver()->deleteTable($table);
							}
						}

						if (!EstatsCore::driver()->createTable($table, $schema[$table]))
						{
							$status = FALSE;
						}
					}
				}
				else
				{
					$array = explode(chr(30), strtr($buffer, array(
	'\r' => "\r",
	'\n' =>"\n"
	)));

					if (!$fields)
					{
						$fields = array_keys($schema[$table]);
						$amount = count($fields);
					}

					$row = array();

					for ($i = 0; $i < $amount; ++$i)
					{
						$row[$fields[$i]] = &$array[$i];
					}

					if (!EstatsCore::driver()->insertData($table, $row))
					{
						$status = FALSE;
					}
				}

				$buffer = '';
			}
			else
			{
				$buffer.= $byte;
			}
		}

		EstatsCore::driver()->commitTransaction();

		return $status;
	}

/**
 * Delete files
 * @param string Pattern
 * @return boolean
 */

	static function delete($pattern = '*')
	{
		$status = TRUE;
		$files = glob(EstatsCore::path(TRUE).'backups/'.$pattern.'.bak');

		for ($i = 0, $c = count($files); $i < $c; ++$i)
		{
			if (!unlink($files[$i]))
			{
				$status = FALSE;
			}
		}

		return $status;
	}
}
?>