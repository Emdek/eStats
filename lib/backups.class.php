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
		$Size = 0;
		$Files = glob(EstatsCore::path(TRUE).'backups/*.bak');

		for ($i = 0, $c = count($Files); $i < $c; ++$i)
		{
			$Size += filesize($Files[$i]);
		}

		return $Size;
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

	static function available($Profile)
	{
		return glob(EstatsCore::path(TRUE).'backups/*.'.$Profile.'.bak', GLOB_BRACE);
	}

/**
 * Creates backup
 * @param string Version
 * @param string Profile
 * @param array Tables
 * @param boolean ReplaceData
 * @return string
 */

	static function create($Version = '', $Profile = '', $Tables = NULL, $ReplaceData = TRUE)
	{
		$Status = TRUE;
		$BackupID = EstatsCore::option('CollectedFrom').'-'.$_SERVER['REQUEST_TIME'].'.'.$Profile;
		$FileName = EstatsCore::path(TRUE).'backups/'.$BackupID.'.bak';

		if (touch($FileName))
		{
			chmod($FileName, 0666);
			file_put_contents($FileName, '/*
eStats v'.$Version.' database backup
Mode: '.$Profile.($ReplaceData?' (replace data)':'').'
Time range: '.date('m.d.Y H:i:s', EstatsCore::option('CollectedFrom')).' - '.date('m.d.Y H:i:s').'
Database: '.EstatsCore::driver()->option('Database').((EstatsCore::driver()->option('DatabaseVersion') != '?')?' '.EstatsCore::driver()->option('DatabaseVersion'):'').'
Module: '.EstatsCore::driver()->option('Name').' v'.EstatsCore::driver()->option('Version').' ('.EstatsCore::driver()->option('URL').')
*/

');

			$Schema = EstatsCore::loadData('share/data/database.ini');

			if (!$Tables)
			{
				$Tables = array_keys($Schema);

				if ($Profile == 'data')
				{
					unset($Tables[array_search('configuration')]);
					unset($Tables[array_search('logs')]);
				}
			}

			for ($i = 0, $c = count($Tables); $i < $c; ++$i)
			{
				$Result = EstatsCore::driver()->selectData(array($Tables[$i]), NULL, NULL, 0, 0, NULL, NULL, NULL, FALSE, EstatsDriver::RETURN_OBJECT);
				$Amount = count($Schema[$Tables[$i]]);

				if (!file_put_contents($FileName, '
/*Table: '.$Tables[$i].'*/

', FILE_APPEND))
				{
					$Status = FALSE;
				}


				while ($Result && ($Row = $Result->fetch(PDO::FETCH_NUM)))
				{
					$Values = array();

					for ($k = 0; $k < $Amount; $k++)
					{
						$Values[] = strtr($Row[$k], array(
	"\r" => '\r',
	"\n" => '\n',
	chr(30) => ''
	));
						}

					if (!file_put_contents($FileName, implode(chr(30), $Values).'
', FILE_APPEND))
					{
						$Status = FALSE;
					}
				}
			}
		}
		else
		{
			$Status = FALSE;
		}

		return ($Status?$BackupID:FALSE);
	}

/**
 * Restores backup
 * @param string BackupID
 * @return boolean
 */

	static function restore($BackupID)
	{
		$Status = TRUE;
		$File = fopen(EstatsCore::path(TRUE).'backups/'.$BackupID.'.bak', 'r');
		$Buffer = '';
		$Replace = $Recreate = $Create = $Table = $Fields = $Line = 0;
		$Schema = EstatsCore::loadData('share/data/database.ini');

		EstatsCore::driver()->beginTransaction();

		while (!feof($File))
		{
			$Byte = fread($File, 1);

			if ($Byte == "\n")
			{
				if (!$Buffer || $Line < 10)
				{
					if ($Line == 3)
					{
						if (strstr($Buffer, 'replace data'))
						{
							$Replace = TRUE;
						}

						if (strstr($Buffer, 'create tables'))
						{
							if (strstr ($Buffer, 'recreate tables'))
							{
								$Recreate = TRUE;
							}
							else
							{
								$Create = TRUE;
							}
						}
					}

					++$Line;
					$Buffer = '';

					continue;
				}

				if (substr($Buffer, 0, 8) == '/*Table:')
				{
					$Table = substr($Buffer, 9, -2);
					$Fields = NULL;
					$Amount = 0;

					if ($Replace)
					{
						EstatsCore::driver()->deleteData($Table);
					}
					else if ($Recreate || $Create)
					{
						if (EstatsCore::driver()->tableExists($Table))
						{
							if ($Create)
							{
								return FALSE;
							}
							else
							{
								EstatsCore::driver()->deleteTable($Table);
							}
						}

						if (!EstatsCore::driver()->createTable($Table, $Schema[$Table]))
						{
							$Status = FALSE;
						}
					}
				}
				else
				{
					$Array = explode(chr(30), strtr($Buffer, array(
	'\r' => "\r",
	'\n' =>"\n"
	)));

					if (!$Fields)
					{
						$Fields = array_keys($Schema[$Table]);
						$Amount = count($Fields);
					}

					$Row = array();

					for ($i = 0; $i < $Amount; ++$i)
					{
						$Row[$Fields[$i]] = &$Array[$i];
					}

					if (!EstatsCore::driver()->insertData($Table, $Row))
					{
						$Status = FALSE;
					}
				}

				$Buffer = '';
			}
			else
			{
				$Buffer.= $Byte;
			}
		}

		EstatsCore::driver()->commitTransaction();

		return $Status;
	}

/**
 * Delete files
 * @param string Pattern
 * @return boolean
 */

	static function delete($Pattern = '*')
	{
		$Status = TRUE;
		$Files = glob(EstatsCore::path(TRUE).'backups/'.$Pattern.'.bak');

		for ($i = 0, $c = count($Files); $i < $c; ++$i)
		{
			if (!unlink($Files[$i]))
			{
				$Status = FALSE;
			}
		}

		return $Status;
	}
}
?>