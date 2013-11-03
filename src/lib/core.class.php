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
 * Event: backups were deleted
 */

	const EVENT_BACKUPSDELETED = 32;

/**
 * Contains current IP address
 */

	static private $iP;

/**
 * Contains proxy name
 */

	static private $proxy;

/**
 * Contains proxy IP
 */

	static private $proxyIP;

/**
 * Contains browser language
 */

	static private $language;

/**
 * Contains session identifier
 */

	static private $session;

/**
 * Contains visitor ID
 */

	static private $visitorID;

/**
 * Contains previous ID of current visitor
 */

	static private $previousVisitorID;

/**
 * TRUE if current visit is new
 */

	static private $isNewVisit;

/**
 * TRUE if current visitor has information collected by JavaScript
 */

	static private $hasJSInformation;

/**
 * Contains robot name for current user agent string
 */

	static private $robot;

/**
 * Contains configuration hash
 */

	static private $configuration;

/**
 * Contains database driver object
 */

	static private $driver;

/**
 * Contains path to script directory
 */

	static private $path;

/**
 * Contains data directory path
 */

	static private $dataDirectory;

/**
 * Contains security string
 */

	static private $security;

/**
 * Statistics identifier
 */

	static private $statistics;

/**
 * Gets configuration from database
 */

	static private function updateConfiguration()
	{
		if (EstatsCache::status('configuration', 86400))
		{
			$data = array();
			$array = self::$driver->selectData(array('configuration'), array('key', 'value'), array(array(EstatsDriver::ELEMENT_OPERATION, array('statistics', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$statistics)))));

			if (count($array) < 2)
			{
				estats_error_message('Could not retrieve configuration!', __FILE__, __LINE__, TRUE);
			}

			for ($i = 0, $c = count($array); $i < $c; ++$i)
			{
				if (in_array($array[$i]['key'], array('Keywords', 'BlockedIPs', 'IgnoredIPs', 'Referrers', 'Backups/usertables')))
				{
					self::$configuration[$array[$i]['key']] = explode('|', $array[$i]['value']);
				}
				else
				{
					self::$configuration[$array[$i]['key']] = &$array[$i]['value'];
				}
			}

			EstatsCache::save('configuration', self::$configuration);
		}
		else
		{
			self::$configuration = EstatsCache::read('configuration');
		}
	}

/**
 * Handles detection using regular expressions etc.
 * @param string String
 * @param array Data
 * @return array
 */

	static private function detectString($string, $data)
	{
		foreach ($data as $key => $value)
		{
			$version = 0;

			if (isset($value['ips']) && self::containsIP(self::$iP, $value['ips']))
			{
				return array($key, '');
			}

			if (isset($value['rules']))
			{
				if (strstr($key, '.'))
				{
					$version = explode('.', $key, 2);
					$key = $version[0];
				}

				for ($i = 0, $c = count($value['rules']); $i < $c; ++$i)
				{
					if (($version && preg_match('#'.$value['rules'][$i].'#i', $string)) || preg_match('#'.$value['rules'][$i].'#i', $string, $version))
					{
						return array($key, (isset($version[1])?$version[1]:''));
					}
				}
			}
			else if (stristr($string, $key))
			{
				return array($key, '');
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

	static private function increaseAmount($table, $values)
	{
		$where = array();

		foreach ($values as $key => $value)
		{
			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array($key, EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, $value)));
		}

		if (!self::$driver->updateData($table, array('amount' => array(EstatsDriver::ELEMENT_EXPRESSION, array('amount', EstatsDriver::OPERATOR_INCREASE))), $where))
		{
			self::$driver->insertData($table, array_merge($values, array('amount' => 1)));
		}
	}

/**
 * Initiates statistics
 * @param integer Statistics
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

	static function init($statistics, $security, $path, $dataDirectory, $driver, $prefix, $connection, $user, $password, $persistent)
	{
		self::$statistics = $statistics;
		self::$security = $security;
		self::$path = realpath($path).'/';
		self::$dataDirectory = realpath(self::$path.$dataDirectory).'/';

		$className = 'EstatsDriver'.ucfirst(strtolower($driver));

		if (class_exists($className))
		{
			self::$driver = new $className;

			if (!self::$driver->connect($connection, $user, $password, $prefix, $persistent))
			{
				estats_error_message('Could not connect to database!', __FILE__, __LINE__, TRUE);

				return FALSE;
			}
		}
		else
		{
			estats_error_message('Can not found class '.$className.'!', __FILE__, __LINE__, TRUE);

			return FALSE;
		}

		self::updateConfiguration();

		self::$session = md5('estats_'.substr(self::option('UniqueID'), 0, 10));
		self::$visitorID = -2;
		self::$isNewVisit = FALSE;
		self::$hasJSInformation = TRUE;
		self::$previousVisitorID = 0;
		self::$robot = '';

		if (isset($_SERVER['HTTP_VIA']))
		{
			self::$iP = $_SERVER[isset($_SERVER['HTTP_X_FORWARDED_FOR'])?'HTTP_X_FORWARDED_FOR':(isset($_SERVER['HTTP_X_FORWARDED'])?'HTTP_X_FORWARDED':$_SERVER['HTTP_CLIENT_IP'])];
			self::$proxy = $_SERVER['HTTP_VIA'];
			self::$proxyIP = $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			self::$iP = $_SERVER['REMOTE_ADDR'];
			self::$proxy = NULL;
			self::$proxyIP = NULL;
		}

		if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			self::$language = '?';
		}
		else
		{
			$string = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);

			self::$language = substr($string, 0, ((strlen($string) > 2 && $string[2] == '-')?5:2));
		}

		return TRUE;
	}

/**
 * Saves configuration in the database
 * @param array Configuration
 * @param boolean Temporary
 */

	static function setConfiguration($configuration, $temporary = FALSE)
	{
		self::$configuration = array_merge(self::$configuration, $configuration);

		$options = self::loadData('share/data/configuration.ini', TRUE);

		foreach ($configuration as $key => $value)
		{
			if (in_array($key, array('Keywords', 'BlockedIPs', 'IgnoredIPs', 'Referrers', 'Backups/usertables')))
			{
				self::$configuration[$key] = explode('|', $value);
			}
			else
			{
				self::$configuration[$key] = $value;
			}
		}

		if (!$temporary)
		{
			foreach ($configuration as $key => $value)
			{
				if (!self::$driver->updateData('configuration', array('value' => $value), array(array(EstatsDriver::ELEMENT_OPERATION, array('name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, $key))))))
				{
					self::$driver->insertData('configuration', array('name' => $key, 'value' => $value, 'mode' => (isset($options['Core'][$key])?0:1)));
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

	static function logEvent($event, $comment = '')
	{
		if (self::option('LogEnabled'))
		{
			self::$driver->insertData('logs', array('time' => date('Y-m-d H:i:s'), 'log' => $event, 'info' => $comment));
		}

		$fileName = self::$dataDirectory.'estats_'.self::$security.'.log';

		if (is_writable($fileName))
		{
			$events = self::loadData('share/data/events.ini');

			file_put_contents($fileName, '
'.$_SERVER['REQUEST_TIME'].': '.(isset($events[$event])?$events[$event]:$event).($comment?' ('.$comment.')':''), FILE_APPEND);
		}
	}

/**
 * Returns option value
 * @param string Option
 * @return mixed
 */

	static function option($option)
	{
		static $configuration;

		if (isset(self::$configuration[$option]))
		{
			return self::$configuration[$option];
		}
		else if (self::$driver)
		{
			if ($configuration === NULL)
			{
				$configuration = self::loadData('share/data/configuration.ini');
			}

			if (isset($configuration['Core'][$option]) || isset($configuration['GUI'][$option]))
			{
				$value = $configuration[isset($configuration['Core'][$option])?'GUI':'Core'][$option]['value'];

				self::$driver->insertData('configuration', array('statistics' => self::$statistics, 'key' => $name, 'value' => $value));

				EstatsCache::delete('configuration');

				return $value;
			}

			return '';
		}
		else
		{
			$configuration = array(
	'LogFile' => TRUE,
	'DefaultTheme' => 'Fresh',
	'Path/prefix' => 'index.php?vars=',
	'Path/separator' => '&amp;'
	);

			return (isset($configuration[$option])?$configuration[$option]:'');
		}
	}

/**
 * Generates time clause
 * @param string Field
 * @param integer From
 * @param integer To
 * @return array
 */

	static function timeClause($field, $from = 0, $to = 0)
	{
		if ($from)
		{
			$clause = array(array(EstatsDriver::ELEMENT_OPERATION, array($field, EstatsDriver::OPERATOR_GREATEROREQUAL, date('Y-m-d H:i:s', $from))));

			if ($to)
			{
				$clause[] = EstatsDriver::OPERATOR_AND;
				$clause[] = array(EstatsDriver::ELEMENT_OPERATION, array($field, EstatsDriver::OPERATOR_LESSOREQUAL, date('Y-m-d H:i:s', $to)));
			}

			return $clause;
		}

		return array();
	}

/**
 * Checks if IP is on list
 * @param string IP
 * @param array Array
 * @return boolean
 */

	static function containsIP($iP, $array)
	{
		for ($i = 0, $c = count($array); $i < $c; ++$i)
		{
			if ($array[$i] == $iP || (strstr($array[$i], '*') && substr($iP, 0, (strlen($array[$i]) - 1)) == substr($array[$i], 0, - 1)))
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

	static function loadData($path, $processSections = TRUE, $required = TRUE)
	{
		static $hash;

		$array = array();

		if (!is_readable(self::$path.$path))
		{
			if ($required)
			{
				estats_error_message($path, __FILE__, __LINE__, FALSE, TRUE);
			}

			return $array;
		}

		if (!isset($hash[$path]))
		{
			$hash[$path] = md5($path);
		}

		$fileName = 'ini-'.$hash[$path];

		if (!EstatsCache::status($fileName, 86400))
		{
			return EstatsCache::read($fileName, TRUE);
		}

		$data = file(self::$path.$path);
		$currentSection = '';

		for ($i = 0, $c = count($data); $i < $c; ++$i)
		{
			$data[$i] = trim($data[$i]);

			if (strlen($data[$i]) == 0 || $data[$i][0] == ';' || $data[$i][0] == '#')
			{
				continue;
			}

			if ($data[$i][0] == '[')
			{
				if ($processSections && $data[$i][strlen($data[$i]) - 1] == ']')
				{
					if (isset($currentSection[0]) && !isset($array[$currentSection]))
					{
						$array[$currentSection] = array();
					}

					$currentSection = substr($data[$i], 1, (strlen($data[$i]) - 2));
				}

				continue;
			}

			$row = preg_split('#\s*=\s*#', $data[$i], 2);

			if (count($row) < 2)
			{
				continue;
			}

			$value = str_replace('\"', '"', trim($row[1], '"\''));
			$keys = preg_split('#(\[|\])#', $row[0], -1, PREG_SPLIT_NO_EMPTY);

			if (strlen($currentSection) > 0)
			{
				array_unshift($keys, $currentSection);
			}

			if (isset($row[0][3]) && substr($row[0], (strlen($row[0]) - 2)) == '[]')
			{
				$keys[] = '';
			}

			$tmpArray = &$array;

			for ($j = 0, $l = count($keys); $j < $l; ++$j)
			{
				if ($j == ($l - 1))
				{
					if ($keys[$j] == '')
					{
						$tmpArray[] = $value;
					}
					else
					{
						$tmpArray[$keys[$j]] = $value;
					}
				}
				else
				{
					$tmpArray = &$tmpArray[$keys[$j]];
				}
			}
		}

		EstatsCache::save($fileName, $array, TRUE);

		return $array;
	}

/**
 * Detects network robots
 * @param string String
 * @return string
 */

	static function detectRobot($string)
	{
		static $data;

		if (!$string)
		{
			return '?';
		}

		if ($data == NULL)
		{
			$data = self::loadData('share/data/robots.ini');
		}

		if (!$data)
		{
			return NULL;
		}

		$result = self::detectString($string, $data);

		return (is_array($result)?$result[0]:$result);
	}

/**
 * Detects browser
 * @param string String
 * @return array
 */

	static function detectBrowser($string)
	{
		static $data;

		if (!$string)
		{
			return array('?', '');
		}

		if ($data == NULL)
		{
			$data = self::loadData('share/data/browsers.ini');
		}

		if (!$data)
		{
			return array();
		}

		$browser = self::detectString($string, $data);

		return ($browser?$browser:array('?', ''));
	}

/**
 * Detects operating system
 * @param string String
 * @return array
 */

	static function detectOperatingSystem($string)
	{
		static $data;

		if (!$string)
		{
			return array('?', '');
		}

		if ($data == NULL)
		{
			$data = self::loadData('share/data/operating-systems.ini');
		}

		if (!$data)
		{
			return array();
		}

		$operatingSystem = self::detectString($string, $data);

		return ($operatingSystem?$operatingSystem:array('?', ''));
	}

/**
 * Detects websearchers
 * @param string String
 * @param boolean Phrase
 * @return array
 */

	static function detectWebsearcher($string, $phrase = FALSE)
	{
		static $data;

		$array = parse_url($string);

		if ($data == NULL)
		{
			$data = self::loadData('share/data/websearchers.ini');
		}

		if (!$data)
		{
			return array();
		}

		if (isset($array['query']))
		{
			parse_str($array['query'], $query);
		}
		else
		{
			$query = NULL;
		}

		foreach ($data as $key => $value)
		{
			if (strstr($array['host'], $key))
			{
				$string = '';

				if ($query && isset($value['query']) && isset($query[$value['query']]))
				{
					$string = $query[$value['query']];
				}
				else if (isset($value['expression']) && preg_match('#'.$value['expression'].'#i', $string, $keywords))
				{
					$string = $keywords[1];
				}

				if ($string && isset($value['encoding']) && function_exists('mb_convert_encoding'))
				{
					$string = mb_convert_encoding($string, 'UTF-8', $value['encoding']);
				}

				$string = str_replace(array('"', '\'', '+', '\\', ','), ' ', $string);

				if ($phrase)
				{
					$keywords = array($string);
				}
				else
				{
					$tmpArray = explode(' ', $string);
					$keywords = array();
					$ignored = self::option('Keywords');

					for ($i = 0, $c = count($tmpArray); $i < $c; ++$i)
					{
						if (strlen($tmpArray[$i]) > 1 && $tmpArray[$i][0] != '-' && (!$ignored || !in_array($tmpArray[$i], $ignored)))
						{
							$keywords[] = $tmpArray[$i];
						}
					}
				}

				return array('http://'.$array['host'], $keywords);
			}
		}

		return array();
	}

/**
 * Returns online visitors amount
 * @param integer Time
 * @return integer
 */

	static function visitsOnline($time = 300)
	{
		return (int) self::$driver->selectAmount('visitors', array(array(EstatsDriver::ELEMENT_OPERATION, array('lastvisit', EstatsDriver::OPERATOR_GREATEROREQUAL, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d H:i:s', ($_SERVER['REQUEST_TIME'] - $time)))))));
	}

/**
 * Returns visits amount
 * @param string Type
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function visitsAmount($type, $from = 0, $to = 0)
	{
		$data = self::$driver->selectRow('time', array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns', EstatsDriver::OPERATOR_PLUS, 'views'))), 'views'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns'))), 'unique'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'returns'), 'returns')), self::timeClause('time', $from, $to));

		return (int) $data[$type];
	}

/**
 * Returns visits amount for given page
 * @param string Page
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function visitsPage($page, $from = 0, $to = 0)
	{
		$where = array(array(EstatsDriver::ELEMENT_OPERATION, array('address', EstatsDriver::OPERATOR_EQUAL, $page)));

		if ($from || $to)
		{
			$where[] = EstatsDriver::OPERATOR_AND;
			$where[] = self::timeClause('time', $from, $to);
		}

		return (int) self::$driver->selectField('sites', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount')), $where);
	}

/**
 * Returns time and amount of most visits
 * @param string Type
 * @param integer From
 * @param integer To
 * @param string Unit
 * @return array
 */

	static function visitsMost($type, $from = 0, $to = 0, $unit = 'day')
	{
		$units = array(
	'hour' => '%Y.%m.%d %H',
	'day' => '%Y.%m.%d',
	'month' => '%Y.%m',
	'year' => '%Y'
	);

		$data = self::$driver->selectRow('time', array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $units[$unit])), 'unit'), 'time', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns', EstatsDriver::OPERATOR_PLUS, 'views'))), 'views'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_PLUS, 'returns'))), 'unique'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'returns'), 'returns')), self::timeClause('time', $from, $to), 0, array($type => FALSE), array('unit'));

		if (!$data)
		{
			return array('time' => 0, 'amount' => 0);
		}

		return array('time' => strtotime($data['time']), 'amount' => (int) $data[$type]);
	}

/**
 * Generates statistics summary
 * @param integer From
 * @param integer To
 * @return array
 */

	static function summary($from = 0, $to = 0)
	{
		$fileName = 'visits'.($from?'-'.$from.($to?'-'.$to:''):'');

		if (EstatsCache::status($fileName, self::option('Cache/others')))
		{
			$visits = array(
	'unique' => self::visitsAmount('unique', $from, $to),
	'views' => self::visitsAmount('views', $from, $to),
	'returns' => self::visitsAmount('returns', $from, $to),
	'most' => self::visitsMost('unique', $from, $to),
	'excluded' => self::$driver->selectField('ignored', array(EstatsDriver::FUNCTION_SUM, 'unique'), self::timeClause('lastvisit', $from, $to)),
	'lasthour' => self::visitsAmount('unique', (($to?$to:$_SERVER['REQUEST_TIME']) - 3600), $to),
	'last24hours' => self::visitsAmount('unique', (($to?$to:$_SERVER['REQUEST_TIME']) - 86400), $to),
	'lastweek' => self::visitsAmount('unique', (($to?$to:$_SERVER['REQUEST_TIME']) - 604800), $to),
	'lastmonth' => self::visitsAmount('unique', (($to?$to:$_SERVER['REQUEST_TIME']) - (86400 * date('t', $to))), $to),
	'lastyear' => self::visitsAmount('unique', (($to?$to:$_SERVER['REQUEST_TIME']) - (86400 * (365 + date('L', $to)))), $to)
	);

			$hoursAmount = ceil(($_SERVER['REQUEST_TIME'] - self::option('CollectedFrom')) / 3600);
			$daysAmount = ceil($hoursAmount / 24);
			$visits['averageperday'] = ($visits['unique'] / $daysAmount);
			$visits['averageperhour'] = ($visits['unique'] / $hoursAmount);

			EstatsCache::save($fileName, $visits);
		}
		else
		{
			$visits = EstatsCache::read($fileName);
		}

		$visits['online'] = self::visitsOnline((int) self::option('OnlineTime'));

		return $visits;
	}

/**
 * Saves information about ignored or blocked visit
 * @param boolean Blocked
 */

	static function ignoreVisit($blocked)
	{
		$where = array(array(EstatsDriver::ELEMENT_OPERATION, array('ip', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$iP))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('type', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, (int) $blocked))));

		if (self::$driver->selectAmount('ignored', $where))
		{
			if (self::$driver->selectAmount('ignored', array(array(EstatsDriver::ELEMENT_OPERATION, array('ip', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$iP))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('lastvisit', EstatsDriver::OPERATOR_GREATER, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d H:i:s', ($_SERVER['REQUEST_TIME'] - 4320))))))))
			{
				$values = array(
	'views' => array(EstatsDriver::ELEMENT_EXPRESSION, array('views', EstatsDriver::OPERATOR_INCREASE)),
	'lastview' => $_SERVER['REQUEST_TIME']
	);
			}
			else
			{
				$values = array(
	'unique' => array(EstatsDriver::ELEMENT_EXPRESSION, array('unique', EstatsDriver::OPERATOR_INCREASE)),
	'useragent' => $_SERVER['HTTP_USER_AGENT'],
	'lastview' => $_SERVER['REQUEST_TIME']
	);
			}

			self::$driver->updateData('ignored', $values, $where);
		}
		else
		{
			self::$driver->insertData('ignored', array(
	'lastview' => $_SERVER['REQUEST_TIME'],
	'lastvisit' => $_SERVER['REQUEST_TIME'],
	'firstvisit' => $_SERVER['REQUEST_TIME'],
	'unique' => 1,
	'views' => 0,
	'useragent' => $_SERVER['HTTP_USER_AGENT'],
	'ip' => self::$iP,
	'type' => $blocked
	));
		}
	}

/**
 * Collects statistics data
 * @param boolean Count
 * @param string Address
 * @param string Title
 * @param array Data
 */

	static function collectData($count = TRUE, $address = NULL, $title = NULL, $data = array())
	{
		if (self::visitorID() < 1)
		{
			return;
		}

		if (!empty($data['info']))
		{
			if (!self::$isNewVisit)
			{
				self::$driver->updateData('visitors', $data, array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$visitorID)))));
			}

			if (self::$isNewVisit || !self::$hasJSInformation)
			{
				$keys = array('javascript', 'cookies', 'flash', 'java', 'screen');

				for ($i = 0, $c = count($keys); $i < $c; ++$i)
				{
					$key = $keys[$i].(($keys[$i] == 'screen')?'s':'');

					self::increaseAmount($key, array('name' => (isset($data[$keys[$i]])?$data[$keys[$i]]:0)));
				}
			}
		}

		if ($count)
		{
			if (isset($_SESSION[self::$session]['visits']) && count($_SESSION[self::$session]['visits']) > 0)
			{
				self::$previousVisitorID = max(array_keys($_SESSION[self::$session]['visits']));
			}

			if (isset($_SESSION[self::$session]['visits'][self::$visitorID]))
			{
				$_SESSION[self::$session]['visits'][$_SESSION[self::$session]['visitor']['id']]['last'] = $_SERVER['REQUEST_TIME'];
			}
			else
			{
				$_SESSION[self::$session]['visits'][self::$visitorID] = array('first' => $_SERVER['REQUEST_TIME'], 'last' => $_SERVER['REQUEST_TIME']);
			}

			if (!isset($_SESSION[self::$session]['visitor']))
			{
				$_SESSION[self::$session]['visitor'] = array('time' => $_SERVER['REQUEST_TIME'], 'id' => self::$visitorID);
			}

			EstatsCookie::set('visitor', $_SESSION[self::$session]['visitor'], 31356000, '/');
			EstatsCookie::set('visits', $_SESSION[self::$session]['visits'], 31356000, '/');

			if (self::$isNewVisit)
			{
				$data = array_merge(array('info' => 0, 'javascript' => 0, 'cookies' => 0, 'flash' => 0, 'java' => 0, 'screen' => 0), $data);

				if (self::$proxy)
				{
					$data['proxy'] = (empty($_SERVER['REMOTE_HOST'])?gethostbyaddr($_SERVER['REMOTE_ADDR']):$_SERVER['REMOTE_HOST']);
					$data['proxyip'] = self::$proxyIP;
				}
				else
				{
					$data['proxy'] = $data['proxyip'] = '';
				}

				$host = explode('.', ((self::$iP == 'unknown')?self::$iP:(empty($_SERVER['REMOTE_HOST'])?gethostbyaddr(self::$iP):$_SERVER['REMOTE_HOST'])));
				$host = (is_numeric(end($host))?'?':implode('.', ((count($host) < 3)?$host:array_slice($host, 1))));

				$data['id'] = self::$visitorID;
				$data['firstvisit'] = $_SERVER['REQUEST_TIME'];
				$data['lastvisit'] = $_SERVER['REQUEST_TIME'];
				$data['visitsamount'] = 1;
				$data['ip'] = self::$iP;
				$data['previous'] = self::$previousVisitorID;
				$data['robot'] = (self::$robot?self::$robot:'');
				$data['host'] = ($host?$host:'?');
				$data['language'] = strtoupper(self::$language);
				$data['useragent'] = $_SERVER['HTTP_USER_AGENT'];
				$data['referrer'] = '';

				if (isset($_SERVER['HTTP_REFERER']) && preg_match('#^(ht|f)tps?:\/\/([^/]+)#s', $_SERVER['HTTP_REFERER']))
				{
					$referrer = parse_url($_SERVER['HTTP_REFERER']);

					if (!in_array($referrer['host'], self::option('Referrers')))
					{
						$data['referrer'] = &$_SERVER['HTTP_REFERER'];
					}
				}
				else
				{
					$referrer = NULL;
				}

				if (!self::$robot)
				{
					self::increaseAmount('languages', array('name' => $data['language']));
					self::increaseAmount('browsers', array_combine(array('name', 'version'), self::detectBrowser($_SERVER['HTTP_USER_AGENT'])));
					self::increaseAmount('operatingsystems', array_combine(array('name', 'version'), self::detectOperatingSystem($_SERVER['HTTP_USER_AGENT'])));
					self::increaseAmount('hosts', array('name' => $data['host']));

					if ($data['proxy'])
					{
						self::increaseAmount('proxy', array('name' => $data['proxy']));
					}

					if (EstatsGeolocation::isAvailable() && ($geoData = EstatsGeolocation::information(self::$iP)))
					{
						self::increaseAmount('geoip', $geoData);
					}

					if ($data['referrer'])
					{
						self::increaseAmount('referrers', array('name' => 'http://'.strtolower($referrer['host'])));
					}

					if ($data['referrer'])
					{
						$webSearch = self::detectWebsearcher($data['referrer'], self::option('CountPhrases'));

						if ($webSearch)
						{
							self::increaseAmount('websearchers', array('name' => $webSearch[0]));

							for ($i = 0, $c = count($webSearch[1]); $i < $c; ++$i)
							{
								if ($webSearch[1][$i])
								{
									self::increaseAmount('keywords', array('name' => $webSearch[1][$i]));
								}
							}
						}
					}
				}
				else
				{
					self::increaseAmount('robots', array('name' => self::$robot));
				}

				self::$driver->insertData('visitors', $data);
			}
			else
			{
				self::$driver->updateData('visitors', array('lastvisit' => $_SERVER['REQUEST_TIME'], 'visitsamount' => array(EstatsDriver::ELEMENT_EXPRESSION, array('visitsamount', EstatsDriver::OPERATOR_INCREASE))), array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$visitorID)))));
			}

			self::$driver->insertData('details', array('id' => self::$visitorID, 'address' => $address, 'time' => $_SERVER['REQUEST_TIME']));
			self::increaseAmount('sites', array('name' => $title, 'address' => $address));

			if (self::$robot && !self::option('CountRobots'))
			{
				return;
			}

			if (self::$isNewVisit)
			{
				if (self::$previousVisitorID)
				{
					$type = 'returns';
				}
				else
				{
					$type = 'unique';
				}
			}
			else
			{
				$type = 'views';
			}

			if (!self::$driver->updateData('time', array($type => array(EstatsDriver::ELEMENT_EXPRESSION, array($type, EstatsDriver::OPERATOR_INCREASE))), array(array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d 00:00:00', $_SERVER['REQUEST_TIME'])))))))
			{
				self::$driver->insertData('time', array('time' => date('Y-m-d 00:00:00', $_SERVER['REQUEST_TIME']), 'views' => (($type == 'views')?1:0), 'unique' => (($type == 'unique')?1:0), 'returns' => (($type == 'returns')?1:0)));
			}
		}
	}

/**
 * Returns visitor ID
 * @return integer
 */

	static function visitorID()
	{
		if (self::$visitorID == -2)
		{
			if (EstatsCookie::exists('ignore'))
			{
				self::$visitorID = -1;
			}
			else if (self::containsIP(self::$iP, self::option('IgnoredIPs')))
			{
				if (self::option('BlacklistMonitor'))
				{
					self::ignoreVisit(FALSE);
				}

				self::$visitorID = -1;
			}
			else
			{
				self::$robot = self::detectRobot($_SERVER['HTTP_USER_AGENT']);

				if (EstatsCookie::get('visitor'))
				{
					if (!isset($_SESSION[self::$session]['visitor']))
					{
						$_SESSION[self::$session]['visitor'] = EstatsCookie::get('visitor');
					}

					if (!isset($_SESSION[self::$session]['visits']))
					{
						$_SESSION[self::$session]['visits'] = EstatsCookie::get('visits');
					}
				}

				if (!isset($_SESSION[self::$session]['visits']))
				{
					$_SESSION[self::$session]['visits'] = array();
				}

				if (isset($_SESSION[self::$session]['visitor']) && (($_SERVER['REQUEST_TIME'] - $_SESSION[self::$session]['visitor']['time']) > self::option('VisitTime') || $_SESSION[self::$session]['visitor']['time'] < self::option('CollectedFrom') || ($_SESSION[self::$session]['visitor'] && !self::$driver->selectAmount('visitors', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, $_SESSION[self::$session]['visitor']['id']))))))))
				{
					unset($_SESSION[self::$session]['visitor']);
				}

				if (isset($_SESSION[self::$session]['visitor']))
				{
					self::$visitorID = $_SESSION[self::$session]['visitor']['id'];
				}
				else
				{
					self::$visitorID = (int) self::$driver->selectField('visitors', 'id', array(array(EstatsDriver::ELEMENT_OPERATION, array('firstvisit', EstatsDriver::OPERATOR_GREATER, array(EstatsDriver::ELEMENT_VALUE, date('Y-m-d H:i:s', ($_SERVER['REQUEST_TIME'] - (self::option('VisitTime') / 2)))))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('ip', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$iP)))), 0, array('id'), FALSE);
				}

				if (self::$visitorID)
				{
					self::$hasJSInformation = self::$driver->selectField('visitors', 'info', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, self::$visitorID)))));
				}
				else
				{
					self::$isNewVisit = TRUE;
					self::$visitorID = (max(self::$driver->selectField('visitors', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MAX, 'id'))), self::visitsAmount('unique')) + 1);
				}
			}
		}

		return self::$visitorID;
	}

/**
 * Returns path to main or data directory
 * @param boolean DataDirectory
 * @return string
 */

	static function path($dataDirectory = FALSE)
	{
		return ($dataDirectory?self::$dataDirectory:self::$path);
	}

/**
 * Returns session identifier
 * @return string
 */

	static function session()
	{
		return (self::$session?self::$session:'gb3kg4lehjl67bnd55fn');
	}

/**
 * Returns security identifier
 * @return string
 */

	static function security()
	{
		return self::$security;
	}

/**
 * Returns detected language
 * @return string
 */

	static function language()
	{
		return self::$language;
	}

/**
 * Returns IP address
 * @return string
 */

	static function IP()
	{
		return self::$iP;
	}

/**
 * Returns identifier of current statistics
 * @return integer
 */

	static function statistics()
	{
		return self::$statistics;
	}

/**
 * Returns reference to database driver object
 * @return object
 */

	static function driver()
	{
		return self::$driver;
	}
}
?>