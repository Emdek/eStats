<?php
/**
 * Core class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.06
 */

class EstatsCore
{

/**
 * Event: script version was changed
 */

	const EVENT_SCRIPTVERSIONCHANGED = 1;

/**
 * Event: configuration was changed
 */

	const EVENT_CONFIGURATIONCHANGED = 2;

/**
 * Event: administrator logged in
 */

	const EVENT_ADMINISTRATORLOGGEDIN = 10;

/**
 * Event: failed administrator login attempt
 */

	const EVENT_FAILEDADMISNISTRATORLOGIN = 11;

/**
 * Event: administrator password was changed
 */

	const EVENT_ADMINISTRATORPASSWORDCHANGED = 12;

/**
 * Event: failed administrator password change attempt
 */

	const EVENT_FAILEDADMISNISTRATORPASSWORDCHANGE = 13;

/**
 * Event: user logged in
 */

	const EVENT_USERLOGGEDIN = 14;

/**
 * Event: failed user login attempt
 */

	const EVENT_FAILEDUSERLOGIN = 15;

/**
 * Event: backup was created
 */

	const EVENT_BACKUPCREATED = 20;

/**
 * Event: failed backup create attempt
 */

	const EVENT_FAILEDBACKUPCREATION = 21;

/**
 * Event: backup was deleted
 */

	const EVENT_BACKUPDELETED = 22;

/**
 * Event: unsuccessful backup delete attempt
 */

	const EVENT_FAILEDBACKUPDELETION = 23;

/**
 * Event: data was restored from backup
 */

	const EVENT_DATARESTORED = 24;

/**
 * Event: all data were deleted
 */

	const EVENT_DATADELETED = 30;

/**
 * Event: data from selected tables were deleted
 */

	const EVENT_TABLESEMPTIED = 31;

/**
 * Event: backups were deleted
 */

	const EVENT_BACKUPSDELETED = 32;

/**
 * Contains current IP address
 */

	static private $IP;

/**
 * Contains proxy name
 */

	static private $Proxy;

/**
 * Contains proxy IP
 */

	static private $ProxyIP;

/**
 * Contains browser language
 */

	static private $Language;

/**
 * Contains session identifier
 */

	static private $Session;

/**
 * Contains visitor ID
 */

	static private $VisitorID;

/**
 * Contains previous ID of current visitor
 */

	static private $PreviousVisitorID;

/**
 * TRUE if current visit is new
 */

	static private $IsNewVisit;

/**
 * TRUE if current visitor has information collected by JavaScript
 */

	static private $HasJSInformation;

/**
 * Contains robot name for current user agent string
 */

	static private $Robot;

/**
 * Contains configuration hash
 */

	static private $Configuration;

/**
 * Contains database driver object
 */

	static private $Driver;

/**
 * Contains path to script directory
 */

	static private $Path;

/**
 * Contains data directory path
 */

	static private $DataDirectory;

/**
 * Contains security string
 */

	static private $Security;

/**
 * TRUE if initialized for use with GUI
 */

	static private $Mode;

/**
 * Contains current date time strings
 */

	static private $Timestamps;

/**
 * Gets configuration from database
 */

	static private function updateConfiguration()
	{
		if (!self::$Mode || EstatsCache::status('configuration', 86400))
		{
			$Data = array();
			$Array = self::$Driver->selectData(array('configuration'), array('name', 'value'), (self::$Mode?NULL:array(array(EstatsDriver::ELEMENT_OPERATION, array('mode', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, 0))))));

			if (count($Array) < 2)
			{
				estats_error_message('Could not retrieve configuration ('.(self::$Mode?'GUI':'CORE').')!', __FILE__, __LINE__, TRUE);
			}

			for ($i = 0, $c = count($Array); $i < $c; ++$i)
			{
				if (in_array($Array[$i]['name'], array('Keywords', 'BlockedIPs', 'IgnoredIPs', 'Referrers', 'Backups|usertables')))
				{
					self::$Configuration[$Array[$i]['name']] = explode('|', $Array[$i]['value']);
				}
				else
				{
					self::$Configuration[$Array[$i]['name']] = &$Array[$i]['value'];
				}
			}

			if (self::$Mode)
			{
				EstatsCache::save('configuration', self::$Configuration);
			}
		}
		else
		{
			self::$Configuration = EstatsCache::read('configuration');
		}
	}

/**
 * Handles detection using regular expressions etc.
 * @param string String
 * @param array Data
 * @return array
 */

	static private function detectString($String, $Data)
	{
		foreach ($Data as $Key => $Value)
		{
			$Version = 0;

			if (isset($Value['ips']) && self::containsIP(self::$IP, $Value['ips']))
			{
				return array($Key, '');
			}

			if (isset($Value['rules']))
			{
				if (strstr($Key, '.'))
				{
					$Version = explode('.', $Key);
					$Key = $Version[0];
				}

				for ($i = 0, $c = count($Value['rules']); $i < $c; ++$i)
				{
					if (($Version && preg_match('#'.$Value['rules'][$i].'#i', $String)) || preg_match('#'.$Value['rules'][$i].'#i', $String, $Version))
					{
						return array($Key, (isset($Version[1])?$Version[1]:''));
					}
				}
			}
			else if (stristr($String, $Key))
			{
				return array($Key, '');
			}
		}

		return NULL;
	}

/**
 * Increments value in the database, general
 * @access public
 * @param string Table
 * @param array Values
 */

	static private function increaseAmount($Table, $Values)
	{
		$Timestamp = self::option('CollectFrequency|'.$Table);
		$Values['time'] = self::$Timestamps[empty(self::$Timestamps[$Timestamp])?'daily':$Timestamp];
		$Where = array();

		foreach ($Values as $Key => $Value)
		{
			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array($Key, EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, $Value)));
		}

		if (!self::$Driver->updateData($Table, array('amount' => array(EstatsDriver::ELEMENT_EXPRESSION, array('amount', EstatsDriver::OPERATOR_INCREASE))), $Where))
		{
			self::$Driver->insertData($Table, array_merge($Values, array('amount' => 1)));
		}
	}

/**
 * Initiates statistics
 * @param integer Mode
 * @param string Security
 * @param string Path
 * @param string DataDirectory
 * @param string Driver
 * @param string Prefix
 * @param string Connection
 * @param string User
 * @param string Password
 * @param boolean Persistent
 */

	static function init($Mode, $Security, $Path, $DataDirectory, $Driver, $Prefix, $Connection, $User, $Password, $Persistent)
	{
		self::$Mode = $Mode;
		self::$Security = $Security;
		self::$Path = realpath($Path).'/';
		self::$DataDirectory = realpath(self::$Path.$DataDirectory).'/';

		$ClassName = 'EstatsDriver'.ucfirst(strtolower($Driver));

		if (class_exists($ClassName))
		{
			self::$Driver = new $ClassName;

			if (!self::$Driver->connect($Connection, $User, $Password, $Prefix, $Persistent))
			{
				estats_error_message('Could not connect to database!', __FILE__, __LINE__, TRUE);

				return FALSE;
			}
		}
		else
		{
			estats_error_message('Can not found class '.$ClassName.'!', __FILE__, __LINE__, TRUE);

			return FALSE;
		}

		self::updateConfiguration();

		self::$Session = md5('estats_'.substr(self::option('UniqueID'), 0, 10));
		self::$VisitorID = -2;
		self::$IsNewVisit = FALSE;
		self::$HasJSInformation = FALSE;
		self::$PreviousVisitorID = 0;
		self::$Robot = '';
		self::$Timestamps = array(
	'yearly' => date('Y-01-01 00:00:00', $_SERVER['REQUEST_TIME']),
	'monthly' => date('Y-m-01 00:00:00', $_SERVER['REQUEST_TIME']),
	'daily' => date('Y-m-d 00:00:00', $_SERVER['REQUEST_TIME']),
	'hourly' => date('Y-m-d H:00:00', $_SERVER['REQUEST_TIME']),
	'full' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
	'none' => 0
	);

		if (isset($_SERVER['HTTP_VIA']))
		{
			self::$IP = $_SERVER[isset($_SERVER['HTTP_X_FORWARDED_FOR'])?'HTTP_X_FORWARDED_FOR':(isset($_SERVER['HTTP_X_FORWARDED'])?'HTTP_X_FORWARDED':$_SERVER['HTTP_CLIENT_IP'])];
			self::$Proxy = $_SERVER['HTTP_VIA'];
			self::$ProxyIP = $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			self::$IP = $_SERVER['REMOTE_ADDR'];
			self::$Proxy = NULL;
			self::$ProxyIP = NULL;
		}

		if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			self::$Language = '?';
		}
		else
		{
			$String = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);

			self::$Language = substr($String, 0, (strlen($String) > 2 && $String[2] == '-')?5:2);
		}

		return TRUE;
	}


/**
 * Saves configuration in the database
 * @param array Configuration
 * @param boolean Temporary
 */

	static function setConfiguration($Configuration, $Temporary = FALSE)
	{
		self::$Configuration = array_merge(self::$Configuration, $Configuration);

		$Options = self::loadData('share/data/configuration.ini', TRUE);

		foreach ($Configuration as $Key => $Value)
		{
			if (in_array($Key, array('Keywords', 'BlockedIPs', 'IgnoredIPs', 'Referrers', 'Backups|usertables')))
			{
				self::$Configuration[$Key] = explode('|', $Value);
			}
			else
			{
				self::$Configuration[$Key] = $Value;
			}
		}

		if (!$Temporary)
		{
			foreach ($Configuration as $Key => $Value)
			{
				if (!self::$Driver->updateData('configuration', array('value' => $Value), array(array(EstatsDriver::ELEMENT_OPERATION, array('name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, $Key))))))
				{
					self::$Driver->insertData('configuration', array('name' => $Key, 'value' => $Value, 'mode' => (isset($Options['Core'][$Key])?0:1)));
				}
			}
		}

		EstatsCache::delete('configuration');
	}

/**
 * Saves event log in database
 * @param integer Event
 * @param string Comment
 */

	static function logEvent($Event, $Comment = '')
	{
		if (self::option('LogEnabled'))
		{
			self::$Driver->insertData('logs', array('time' => date('Y-m-d H:i:s'), 'log' => $Event, 'info' => $Comment));
		}

		$FileName = self::$DataDirectory.'estats_'.self::$Security.'.log';

		if (is_writable($FileName))
		{
			$Events = self::loadData('share/data/events.ini');

			file_put_contents($FileName, '
'.self::$Timestamps['full'].': '.(isset($Events[$Event])?$Events[$Event]:$Event).($Comment?' ('.$Comment.')':''), FILE_APPEND);
		}
	}

/**
 * Returns option value
 * @param string Option
 * @return mixed
 */

	static function option($Option)
	{
		static $Configuration;

		if (isset(self::$Configuration[$Option]))
		{
			return self::$Configuration[$Option];
		}
		else if (self::$Driver)
		{
			if ($Configuration === NULL)
			{
				$Configuration = self::loadData('share/data/configuration.ini');
			}

			$Name = $Option;
			$Option = str_replace('|', '/', $Option);

			if (isset($Configuration['Core'][$Option]) || isset($Configuration['GUI'][$Option]))
			{
				$Mode = (isset($Configuration['Core'][$Option])?0:1);

				self::$Driver->insertData('configuration', array('name' => $Name, 'value' => $Configuration[$Mode?'GUI':'Core'][$Option]['value'], 'mode' => $Mode));

				EstatsCache::delete('configuration');

				return $Configuration[$Mode?'GUI':'Core'][$Option]['value'];
			}

		  return '';
		}
		else
		{
			$Configuration = array(
	'LogFile' => TRUE,
	'DefaultTheme' => 'Fresh',
	'Path|prefix' => 'index.php?vars=',
	'Path|separator' => '&amp;'
	);

		  return (isset($Configuration[$Option])?$Configuration[$Option]:'');
		}
	}

/**
 * Generates time clause
 * @param string Field
 * @param integer From
 * @param integer To
 * @return array
 */

	static function timeClause($Field, $From = 0, $To = 0)
	{
		if ($From)
		{
			$Clause = array(array(EstatsDriver::ELEMENT_OPERATION, array($Field, EstatsDriver::OPERATOR_GREATEROREQUAL, date('Y-m-d H:i:s', $From))));

			if ($To)
			{
				$Clause[] = EstatsDriver::OPERATOR_AND;
				$Clause[] = array(EstatsDriver::ELEMENT_OPERATION, array($Field, EstatsDriver::OPERATOR_LESSOREQUAL, date('Y-m-d H:i:s', $To)));
			}

			return $Clause;
		}

		return array();
	}

/**
 * Checks if IP is on list
 * @param string IP
 * @param array Array
 * @return boolean
 */

	static function containsIP($IP, $Array)
	{
		for ($i = 0, $c = count($Array); $i < $c; ++$i)
		{
			if ($Array[$i] == $IP || (strstr($Array[$i], '*') && substr($IP, 0, (strlen($Array[$i]) - 1)) == substr($Array[$i], 0, - 1)))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

/**
 * Loads data from INI file
 * @param string Path
 * @param boolean ProcessSections
 * @param boolean Required
 * @return array
 */

	static function loadData($Path, $ProcessSections = TRUE, $Required = TRUE)
	{
		static $Hash;

		$Array = array();

		if (!is_readable(self::$Path.$Path))
		{
			if ($Required)
			{
				estats_error_message($Path, __FILE__, __LINE__, FALSE, TRUE);
			}

			return $Array;
		}

		if (!isset($Hash[$Path]))
		{
			$Hash[$Path] = md5($Path);
		}

		$FileName = 'ini-'.$Hash[$Path];

		if (!EstatsCache::status($FileName, 86400))
		{
			return EstatsCache::read($FileName, TRUE);
		}

		$Data = file(self::$Path.$Path);
		$CurrentSection = '';

		for ($i = 0, $c = count($Data); $i < $c; ++$i)
		{
			$Data[$i] = trim($Data[$i]);

			if (strlen($Data[$i]) == 0 || $Data[$i][0] == ';' || $Data[$i][0] == '#')
			{
				continue;
			}

			if ($Data[$i][0] == '[')
			{
				if ($ProcessSections && $Data[$i][strlen($Data[$i]) - 1] == ']')
				{
					if (isset($CurrentSection[0]) && !isset($Array[$CurrentSection]))
					{
						$Array[$CurrentSection] = array();
					}

					$CurrentSection = substr($Data[$i], 1, (strlen($Data[$i]) - 2));
				}

				continue;
			}

			$Row = preg_split('#\s*=\s*#', $Data[$i], 2);

			if (count($Row) < 2)
			{
				continue;
			}

			$Value = str_replace('\"', '"', trim($Row[1], '"\''));
			$Keys = preg_split('#(\[|\])#', $Row[0], -1, PREG_SPLIT_NO_EMPTY);

			if (strlen($CurrentSection) > 0)
			{
				array_unshift($Keys, $CurrentSection);
			}

			if (isset($Row[0][3]) && substr($Row[0], (strlen($Row[0]) - 2)) == '[]')
			{
				$Keys[] = '';
			}

			$TmpArray = &$Array;

			for ($j = 0, $l = count($Keys); $j < $l; ++$j)
			{
				if ($j == ($l - 1))
				{
					if ($Keys[$j] == '')
					{
						$TmpArray[] = $Value;
					}
					else
					{
						$TmpArray[$Keys[$j]] = $Value;
					}
				}
				else
				{
					$TmpArray = &$TmpArray[$Keys[$j]];
				}
			}
		}

		EstatsCache::save($FileName, $Array, TRUE);

		return $Array;
	}

/**
 * Detects network robots
 * @param string String
 * @return string
 */

	static function detectRobot($String)
	{
		static $Data;

		if (!$String)
		{
			return '?';
		}

		if ($Data == NULL)
		{
			$Data = self::loadData('share/data/robots.ini');
		}

		if (!$Data)
		{
			return NULL;
		}

		$Result = self::detectString($String, $Data);

		return (is_array($Result)?$Result[0]:$Result);
	}

/**
 * Detects browser
 * @param string String
 * @return array
 */

	static function detectBrowser($String)
	{
		static $Data;

		if (!$String)
		{
			return array('?', '');
		}

		if ($Data == NULL)
		{
			$Data = self::loadData('share/data/browsers.ini');
		}

		if (!$Data)
		{
			return array();
		}

		$Browser = self::detectString($String, $Data);

		return ($Browser?$Browser:array('?', ''));
	}

/**
 * Detects operating system
 * @param string String
 * @return array
 */

	static function detectOperatingSystem($String)
	{
		static $Data;

		if (!$String)
		{
			return array('?', '');
		}

		if ($Data == NULL)
		{
			$Data = self::loadData('share/data/operating-systems.ini');
		}

		if (!$Data)
		{
			return array();
		}

		$OperatingSystem = self::detectString($String, $Data);

		return ($OperatingSystem?$OperatingSystem:array('?', ''));
	}

/**
 * Detects websearchers
 * @param string String
 * @param boolean Phrase
 * @return array
 */

	static function detectWebsearcher($String, $Phrase = FALSE)
	{
		static $Data;

		$Array = parse_url($String);

		if ($Data == NULL)
		{
			$Data = self::loadData('share/data/websearchers.ini');
		}

		if (!$Data)
		{
			return array();
		}

		if (isset($Array['query']))
		{
			parse_str($Array['query'], $Query);
		}
		else
		{
			$Query = NULL;
		}

		foreach ($Data as $Key => $Value)
		{
			if (strstr($Array['host'], $Key))
			{
				$String = '';

				if ($Query && isset($Value['query']) && isset($Query[$Value['query']]))
				{
					$String = $Query[$Value['query']];
				}
				else if (isset($Value['expression']) && preg_match('#'.$Value['expression'].'#i', $String, $Keywords))
				{
					$String = $Keywords[1];
				}

				if ($String && isset($Value['encoding']) && function_exists('mb_convert_encoding'))
				{
					$String = mb_convert_encoding($String, 'UTF-8', $Value['encoding']);
				}

				$String = str_replace(array('"', '\'', '+', '\\', ','), ' ', $String);

				if ($Phrase)
				{
					$Keywords = array($String);
				}
				else
				{
					$TmpArray = explode(' ', $String);
					$Keywords = array();
					$Ignored = self::option('Keywords');

					for ($i = 0, $c = count($TmpArray); $i < $c; ++$i)
					{
						if (strlen($TmpArray[$i]) > 1 && $TmpArray[$i][0] != '-' && (!$Ignored || !in_array($TmpArray[$i], $Ignored)))
						{
							$Keywords[] = $TmpArray[$i];
						}
					}
				}

				return array('http://'.$Array['host'], $Keywords);
			}
		}

		return array();
	}

/**
 * Returns online visitors amount
 * @param integer Time
 * @return integer
 */

	static function visitsOnline($Time = 300)
	{
		return (int) self::$Driver->selectAmount('visitors', array(array(EstatsDriver::ELEMENT_OPERATION, array('lastvisit', EstatsDriver::OPERATOR_GREATEROREQUAL, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d H:i:s', ($_SERVER['REQUEST_TIME'] - $Time)))))));
	}

/**
 * Returns visits amount
 * @param string Type
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function visitsAmount($Type, $From = 0, $To = 0)
	{
		$Data = self::$Driver->selectRow('time', array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns', EstatsDriver::OPERATOR_PLUS, 'views'))), 'views'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns'))), 'unique'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'returns'), 'returns')), self::timeClause('time', $From, $To));

		return (int) $Data[$Type];
	}

/**
 * Returns visits amount for given page
 * @param string Page
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function visitsPage($Page, $From = 0, $To = 0)
	{
		$Where = array(array(EstatsDriver::ELEMENT_OPERATION, array('address', EstatsDriver::OPERATOR_EQUAL, $Page)));

		if ($From || $To)
		{
			$Where[] = EstatsDriver::OPERATOR_AND;
			$Where[] = self::timeClause('time', $From, $To);
		}

		return (int) self::$Driver->selectField('sites', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount')), $Where);
	}
/**
 * Returns time and amount of most visits
 * @param string Type
 * @param integer From
 * @param integer To
 * @param string Unit
 * @return array
 */

	static function visitsMost($Type, $From = 0, $To = 0, $Unit = 'day')
	{
		$Units = array(
	'hour' => '%Y.%m.%d %H',
	'day' => '%Y.%m.%d',
	'month' => '%Y.%m',
	'year' => '%Y'
	);

		$Data = self::$Driver->selectRow('time', array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $Units[$Unit])), 'unit'), 'time', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns', EstatsDriver::OPERATOR_PLUS, 'views'))), 'views'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns'))), 'unique'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'returns'), 'returns')), self::timeClause('time', $From, $To), 0, array($Type => FALSE), array('unit'));

		if (!$Data)
		{
			return array('time' => 0, 'amount' => 0);
		}

		return array('time' => strtotime($Data['time']), 'amount' => (int) $Data[$Type]);
	}

/**
 * Generates statistics summary
 * @param integer From
 * @param integer To
 * @return array
 */

	static function summary($From = 0, $To = 0)
	{
		$FileName = 'visits'.($From?'-'.$From.($To?'-'.$To:''):'');

		if (EstatsCache::status($FileName, self::option('Cache|others')))
		{
			$Visits = array(
	'unique' => self::visitsAmount('unique', $From, $To),
	'views' => self::visitsAmount('views', $From, $To),
	'returns' => self::visitsAmount('returns', $From, $To),
	'most' => self::visitsMost('unique', $From, $To),
	'excluded' => self::$Driver->selectField('ignored', array(EstatsDriver::FUNCTION_SUM, 'unique'), self::timeClause('lastvisit', $From, $To)),
	'lasthour' => self::visitsAmount('unique', (($To?$To:$_SERVER['REQUEST_TIME']) - 3600), $To),
	'last24hours' => self::visitsAmount('unique', (($To?$To:$_SERVER['REQUEST_TIME']) - 86400), $To),
	'lastweek' => self::visitsAmount('unique', (($To?$To:$_SERVER['REQUEST_TIME']) - 604800), $To),
	'lastmonth' => self::visitsAmount('unique', (($To?$To:$_SERVER['REQUEST_TIME']) - (86400 * date('t', $To))), $To),
	'lastyear' => self::visitsAmount('unique', (($To?$To:$_SERVER['REQUEST_TIME']) - (86400 * (365 + date('L', $To)))), $To)
	);

			$HoursAmount = ceil(($_SERVER['REQUEST_TIME'] - self::option('CollectedFrom')) / 3600);
			$DaysAmount = ceil($HoursAmount / 24);
			$Visits['averageperday'] = ($Visits['unique'] / $DaysAmount);
			$Visits['averageperhour'] = ($Visits['unique'] / $HoursAmount);

			EstatsCache::save($FileName, $Visits);
		}
		else
		{
			$Visits = EstatsCache::read($FileName);
		}

		$Visits['online'] = self::visitsOnline((int) self::option('OnlineTime'));

		return $Visits;
	}

/**
 * Saves information about ignored or blocked visit
 * @param boolean Blocked
 */

	static function ignoreVisit($Blocked)
	{
		$Where = array(array(EstatsDriver::ELEMENT_OPERATION, array('ip', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$IP))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('type', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, (int) $Blocked))));

		if (self::$Driver->selectAmount('ignored', $Where))
		{
			if (self::$Driver->selectAmount('ignored', array(array(EstatsDriver::ELEMENT_OPERATION, array('ip', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$IP))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('lastvisit', EstatsDriver::OPERATOR_GREATER, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d H:i:s', ($_SERVER['REQUEST_TIME'] - 4320))))))))
			{
				$Values = array(
	'views' => array(EstatsDriver::ELEMENT_EXPRESSION, array('views', EstatsDriver::OPERATOR_INCREASE)),
	'lastview' => self::$Timestamps['full']
	);
			}
			else
			{
				$Values = array(
	'unique' => array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_INCREASE)),
	'useragent' => $_SERVER['HTTP_USER_AGENT'],
	'lastview' => self::$Timestamps['full']
	);
			}

			self::$Driver->updateData('ignored', $Values, $Where);
		}
		else
		{
			self::$Driver->insertData('ignored', array(
	'lastview' => self::$Timestamps['full'],
	'lastvisit' => self::$Timestamps['full'],
	'firstvisit' => self::$Timestamps['full'],
	'unique' => 1,
	'views' => 0,
	'useragent' => $_SERVER['HTTP_USER_AGENT'],
	'ip' => self::$IP,
	'type' => $Blocked
	));
		}
	}

/**
 * Collects statistics data
 * @param boolean Count
 * @param string Address
 * @param string Title
 * @param array Data
 * @return array
 */

	static function collectData($Count = TRUE, $Address = NULL, $Title = NULL, $Data = array())
	{
		self::visitorID();

		if ($Count && self::$VisitorID >= 0)
		{
			if (!self::$VisitorID)
			{
				self::$VisitorID = (max(self::$Driver->selectField('visitors', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MAX, 'id'))), self::visitsAmount('unique')) + 1);
				self::$IsNewVisit = TRUE;
			}

			if (isset($_SESSION[self::$Session]['visits']) && count($_SESSION[self::$Session]['visits']) > 0)
			{
				self::$PreviousVisitorID = max(array_keys($_SESSION[self::$Session]['visits']));
			}

			if (isset($_SESSION[self::$Session]['visits'][self::$VisitorID]))
			{
				$_SESSION[self::$Session]['visits'][$_SESSION[self::$Session]['visitor']['id']]['last'] = $_SERVER['REQUEST_TIME'];
			}
			else
			{
				$_SESSION[self::$Session]['visits'][self::$VisitorID] = array('first' => $_SERVER['REQUEST_TIME'], 'last' => $_SERVER['REQUEST_TIME']);
			}

			if (!isset($_SESSION[self::$Session]['visitor']))
			{
				$_SESSION[self::$Session]['visitor'] = array('time' => $_SERVER['REQUEST_TIME'], 'id' => self::$VisitorID);
			}

			EstatsCookie::set('visitor', $_SESSION[self::$Session]['visitor'], 31356000, '/');
			EstatsCookie::set('visits', $_SESSION[self::$Session]['visits'], 31356000, '/');

			if (self::$IsNewVisit)
			{
				$Data = array_merge(array('info' => 0, 'javascript' => 0, 'cookies' => 0, 'flash' => 0, 'java' => 0, 'screen' => 0), $Data);

				if (self::$Proxy)
				{
					$Data['proxy'] = (empty($_SERVER['REMOTE_HOST'])?gethostbyaddr($_SERVER['REMOTE_ADDR']):$_SERVER['REMOTE_HOST']);
					$Data['proxyip'] = self::$ProxyIP;
				}
				else
				{
					$Data['proxy'] = $Data['proxyip'] = '';
				}

				$Host = explode('.', ((self::$IP == 'unknown')?self::$IP:(empty($_SERVER['REMOTE_HOST'])?gethostbyaddr(self::$IP):$_SERVER['REMOTE_HOST'])));
				$Host = (is_numeric(end($Host))?'?':implode('.', ((count($Host) < 3)?$Host:array_slice($Host, 1))));

				$Data['id'] = self::$VisitorID;
				$Data['firstvisit'] = self::$Timestamps['full'];
				$Data['lastvisit'] = self::$Timestamps['full'];
				$Data['visitsamount'] = 1;
				$Data['ip'] = self::$IP;
				$Data['previous'] = self::$PreviousVisitorID;
				$Data['robot'] = (self::$Robot?self::$Robot:'');
				$Data['host'] = ($Host?$Host:'?');
				$Data['language'] = strtoupper(self::$Language);
				$Data['useragent'] = $_SERVER['HTTP_USER_AGENT'];
				$Data['referrer'] = '';

				if (isset($_SERVER['HTTP_REFERER']) && preg_match('#^(ht|f)tps?:\/\/([^/]+)#s', $_SERVER['HTTP_REFERER']))
				{
					$Referrer = parse_url($_SERVER['HTTP_REFERER']);

					if (!in_array($Referrer['host'], self::option('Referrers')))
					{
						$Data['referrer'] = &$_SERVER['HTTP_REFERER'];
					}
				}
				else
				{
					$Referrer = NULL;
				}

				if (!self::$Robot)
				{
					if (self::option('CollectFrequency|languages') !== 'disabled')
					{
						self::increaseAmount('languages', array('name' => $Data['language']));
					}

					if (self::option('CollectFrequency|browsers') !== 'disabled')
					{
						self::increaseAmount('browsers', array_combine(array('name', 'version'), self::detectBrowser($_SERVER['HTTP_USER_AGENT'])));
					}

					if (self::option('CollectFrequency|oses') !== 'disabled')
					{
						self::increaseAmount('oses', array_combine(array('name', 'version'), self::detectOperatingSystem($_SERVER['HTTP_USER_AGENT'])));
					}

					if (self::option('CollectFrequency|hosts') !== 'disabled')
					{
 						self::increaseAmount('hosts', array('name' => $Data['host']));
					}

					if (self::option('CollectFrequency|proxy') !== 'disabled' && $Data['proxy'])
					{
						self::increaseAmount('proxy', array('name' => $Data['proxy']));
					}

					if (self::option('CollectFrequency|geolocation') !== 'disabled' && EstatsGeolocation::isAvailable() && ($GeoData = EstatsGeolocation::information(self::$IP)))
					{
						self::increaseAmount('geoip', $GeoData);
					}

					if (self::option('CollectFrequency|referrers') !== 'disabled' && $Data['referrer'])
					{
						self::increaseAmount('referrers', array('name' => 'http://'.strtolower($Referrer['host'])));
					}

					if ($Data['referrer'] && (self::option('CollectFrequency|websearchers') !== 'disabled' || self::option('CollectFrequency|keywords') !== 'disabled'))
					{
						$WebSearch = self::detectWebsearcher($Data['referrer'], self::option('CountPhrases'));

						if ($WebSearch)
						{
							if (self::option('CollectFrequency|websearchers') !== 'disabled')
							{
								self::increaseAmount('websearchers', array('name' => $WebSearch[0]));
							}

							if (self::option('CollectFrequency|keywords') !== 'disabled')
							{
								for ($i = 0, $c = count($WebSearch[1]); $i < $c; ++$i)
								{
									if ($WebSearch[1][$i])
									{
										self::increaseAmount('keywords', array('name' => $WebSearch[1][$i]));
									}
								}
							}
						}
					}
				}
				else if (self::option('CollectFrequency|robots') !== 'disabled')
				{
					self::increaseAmount('robots', array('name' => self::$Robot));
				}

				self::$Driver->insertData('visitors', $Data);
			}
			else
			{
				self::$Driver->updateData('visitors', array('lastvisit' => self::$Timestamps['full'], 'visitsamount' => array(EstatsDriver::ELEMENT_EXPRESSION, array('visitsamount', EstatsDriver::OPERATOR_INCREASE))), array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$VisitorID)))));
			}

			if (self::option('VisitDetails'))
			{
				self::$Driver->insertData('details', array('id' => self::$VisitorID, 'address' => $Address, 'time' => self::$Timestamps['full']));
			}

			if (self::option('CollectFrequency|sites') !== 'disabled')
			{
				self::increaseAmount('sites', array('name' => $Title, 'address' => $Address));
			}

			if (self::$Robot && !self::option('CountRobots'))
			{
				return;
			}

			if (self::option('CollectFrequency|time') !== 'disabled')
			{
				if (self::$IsNewVisit)
				{
					if (self::$PreviousVisitorID)
					{
						$Type = 'returns';
					}
					else
					{
						$Type = 'unique';
					}
				}
				else
				{
					$Type = 'views';
				}

				if (!self::$Driver->updateData('time', array($Type => array(EstatsDriver::ELEMENT_EXPRESSION, array($Type, EstatsDriver::OPERATOR_INCREASE))), array(array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$Timestamps[self::option('CollectFrequency|time')]))))))
				{
					self::$Driver->insertData('time', array('time' => self::$Timestamps[self::option('CollectFrequency|time')], 'views' => (($Type == 'views')?1:0), 'unique' => (($Type == 'unique')?1:0), 'returns' => (($Type == 'returns')?1:0)));
				}
			}
		}
		else if ($Data && self::$VisitorID > 0 && !self::$HasJSInformation)
		{
			self::$Driver->updateData('visitors', array_merge(array('info' => TRUE), $Data), array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$VisitorID)))));

			foreach ($Data as $Key => $Value)
			{
				if ($Key == 'screen')
				{
					$Key.= 's';
				}

				self::increaseAmount($Key, array('name' => $Value));
			}
		}
	}

/**
 * Returns visitor ID
 * @return integer
 */
	static function visitorID()
	{
		if (self::$VisitorID == -2)
		{
			if (EstatsCookie::exists('ignore'))
			{
				self::$VisitorID = -1;
			}
			else if (self::containsIP(self::$IP, self::option('IgnoredIPs')))
			{
				if (self::option('BlacklistMonitor'))
				{
					self::ignoreVisit(FALSE);
				}

				self::$VisitorID = -1;
			}
			else
			{
				self::$Robot = self::detectRobot($_SERVER['HTTP_USER_AGENT']);

				if (EstatsCookie::get('visitor'))
				{
					if (!isset($_SESSION[self::$Session]['visitor']))
					{
						$_SESSION[self::$Session]['visitor'] = EstatsCookie::get('visitor');
					}

					if (!isset($_SESSION[self::$Session]['visits']))
					{
						$_SESSION[self::$Session]['visits'] = EstatsCookie::get('visits');
					}
				}

				if (!isset($_SESSION[self::$Session]['visits']))
				{
					$_SESSION[self::$Session]['visits'] = array();
				}

				if (isset($_SESSION[self::$Session]['visitor']) && (($_SERVER['REQUEST_TIME'] - $_SESSION[self::$Session]['visitor']['time']) > self::option('VisitTime') || $_SESSION[self::$Session]['visitor']['time'] < self::option('CollectedFrom') || ($_SESSION[self::$Session]['visitor'] && !self::$Driver->selectAmount('visitors', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, $_SESSION[self::$Session]['visitor']['id']))))))))
				{
					unset($_SESSION[self::$Session]['visitor']);
				}

				if (isset($_SESSION[self::$Session]['visitor']))
				{
					self::$VisitorID = $_SESSION[self::$Session]['visitor']['id'];
				}
				else
				{
					self::$VisitorID = (int) self::$Driver->selectField('visitors', 'id', array(array(EstatsDriver::ELEMENT_OPERATION, array('firstvisit', EstatsDriver::OPERATOR_GREATER, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d H:i:s', ($_SERVER['REQUEST_TIME'] - (self::option('VisitTime') / 2)))))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('ip', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$IP)))), 0, array('id'), FALSE);
				}

				if (self::$VisitorID > 0)
				{
					self::$HasJSInformation = self::$Driver->selectField('visitors', 'info', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$VisitorID)))));
				}
			}
		}

		return self::$VisitorID;
	}

/**
 * Returns path to main or data directory
 * @param boolean DataDirectory
 * @return string
 */

	static function path($DataDirectory = FALSE)
	{
		return ($DataDirectory?self::$DataDirectory:self::$Path);
	}

/**
 * Returns session identifier
 * @return string
 */

	static function session()
	{
		return (self::$Session?self::$Session:'gb3kg4lehjl67bnd55fn');
	}

/**
 * Returns security identifier
 * @return string
 */

	static function security()
	{
		return self::$Security;
	}

/**
 * Returns detected language
 * @return string
 */

	static function language()
	{
		return self::$Language;
	}

/**
 * Returns IP address
 * @return string
 */

	static function IP()
	{
		return self::$IP;
	}

/**
 * Returns reference to database driver object
 * @return object
 */

	static function driver()
	{
		return self::$Driver;
	}
}
?>