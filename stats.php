<?php
/**
 * Data collecting script for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

error_reporting(0);
ignore_user_abort(TRUE);

if (!session_id())
{
	session_start();
}

define('ESTATS_PATH', dirname(__FILE__).'/');

/**
 * Generates error message
 * @param string Error
 * @param string File
 * @param string Line
 * @param boolean NotFile
 * @param boolean Warning
 */

function estats_error_message($Error, $File, $Line, $NotFile = FALSE, $Warning = FALSE)
{
	if (!$Warning && !defined('ESTATS_CRITICAL'))
	{
		define('ESTATS_CRITICAL', TRUE);
	}

	if (!defined('ESTATS_JSINFORMATION'))
	{
		echo '<b>eStats '.($Warning?'warning':'error').':</b> <i>'.($NotFile?$Error:'Could not load file: <b>'.$Error.'</b>!').'</i> (<b>'.$File.': '.$Line.'</b>)<br />
';
	}
}

if (defined('ESTATS_COUNT') || defined('ESTATS_JSINFORMATION') || defined('ESTATS_MINISTATS'))
{
	header('Expires: '.gmdate('r', 0));
	header('Last-Modified: '.gmdate('r'));
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Pragma: no-cache');

	if (!include (ESTATS_PATH.'conf/config.php'))
	{
		estats_error_message('conf/config.php', __FILE__, __LINE__);
	}

	if (!include (ESTATS_PATH.'lib/driver.class.php'))
	{
		estats_error_message('lib/driver.class.php', __FILE__, __LINE__);
	}

	if (!include (ESTATS_PATH.'lib/core.class.php'))
	{
		estats_error_message('lib/core.class.php', __FILE__, __LINE__);
	}

	if (!include (ESTATS_PATH.'lib/cookie.class.php'))
	{
		estats_error_message('lib/cookie.class.php', __FILE__, __LINE__);
	}

	if (!include (ESTATS_PATH.'lib/cache.class.php'))
	{
		estats_error_message('lib/cache.class.php', __FILE__, __LINE__);
	}

	if (!include (ESTATS_PATH.'lib/backups.class.php'))
	{
		estats_error_message('lib/backups.class.php', __FILE__, __LINE__);
	}

	if (!include (ESTATS_PATH.'lib/geolocation.class.php'))
	{
		estats_error_message('lib/geolocation.class.php', __FILE__, __LINE__);
	}

	if (!defined('ESTATS_CRITICAL') && !empty($DBType))
	{
		if (include (ESTATS_PATH.'plugins/drivers/'.$DBType.'/plugin.php'))
		{
			if (empty($DBConnection))
			{
				switch ($DBType)
				{
					case 'MySQL':
						$DBConnection = 'mysql:'.$DBHost.';port=3306;dbname='.$DBName;
					break;
					case 'PostgreSQL':
						$DBConnection = 'pgsql:'.$DBHost.';dbname='.$DBName;;
					break;
					case 'SQLite':
						$DBConnection = 'sqlite:'.realpath($DataDir.'estats_'.$DBID.'.sqlite');
					break;
					default:
						$DBConnection = '';
				}
			}

			EstatsCore::init(0, $DBID, ESTATS_PATH, $DataDir, $DBType, $DBPrefix, $DBConnection, $DBUser, $DBPass, $PConnect);
		}
		else
		{
			estats_error_message('plugins/drivers/'.$DBType.'/plugin.php', __FILE__, __LINE__);
		}
	}
	else if (empty($DBType))
	{
		estats_error_message('Variable DBType not defined!', __FILE__, __LINE__);
	}

	if (!defined('ESTATS_CRITICAL'))
	{
		if (EstatsCore::option('StatsEnabled'))
		{
			EstatsCore::collectData(defined('ESTATS_COUNT'), (defined('ESTATS_ADDRESS')?ESTATS_ADDRESS:$_SERVER['REQUEST_URI']), (defined('ESTATS_TITLE')?ESTATS_TITLE:''), (defined('ESTATS_JSINFORMATION')?$JSInformation:array()));
		}

		if (EstatsCore::option('Backups|creationinterval') && ((($_SERVER['REQUEST_TIME'] - EstatsCore::option('LastBackup')) > EstatsCore::option('Backups|creationinterval'))))
		{
			EstatsCore::setConfiguration(array('LastBackup' => $_SERVER['REQUEST_TIME']));

			$BackupID = EstatsBackups::create(ESTATS_VERSIONSTRING, EstatsCore::option('Backups|profile'), EstatsCore::option('Backups|usertables'), EstatsCore::option('Backups|replacedata'));

			if ($BackupID)
			{
				EstatsCore::logEvent(EstatsCore::EVENT_BACKUPCREATED, 'ID: '.$BackupID);
			}
			else
			{
				EstatsCore::logEvent(EstatsCore::EVENT_FAILEDBACKUPCREATION);
			}
		}
	}
}
?>