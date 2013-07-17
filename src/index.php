<?php
/**
 * eStats - statistics for web pages
 * @author Emdek <http://emdek.pl>
 * @version 5.0.00
 * @date 2011-01-15 14:30:00
 */

define('ESTATS_VERSIONSTRING', '5.0.00');
define('ESTATS_VERSIONSTATUS', 'snapshot');
define('ESTATS_VERSIONTIME', 1295098200);

/**
 * Error handler
 * @param integer Number
 * @param string String
 * @param string File
 * @param integer Line
 */

function estats_error_handler($number, $string, $file, $line)
{
	if (strstr($string, 'date.timezone'))
	{
		return;
	}

	$errorTypes = array(
	2 => 'Warning',
	8 => 'Notice',
	32 => 'Core warning',
	128 => 'Compile warning',
	512 => 'User warning',
	1024 => 'User notice',
	2048 => 'Strict',
	4096 => 'Recoverable error',
	8192 => 'Deprecated',
	16384 => 'User deprecated',
);

	$_SESSION['ERRORS'][] = '<h5>
<big>#'.(count($_SESSION['ERRORS']) + 1).'</big>
'.$errorTypes[$number].' (<em>'.$file.':'.$line.'</em>)
</h5>
'.$string.'<br>
';
}

/**
 * Generates error message
 * @param string Message
 * @param string File
 * @param string Line
 * @param boolean NotFile
 * @param boolean Warning
 */

function estats_error_message($message, $file, $line, $notFile = FALSE, $warning = FALSE)
{
	if ($warning)
	{
		EstatsGUI::notify(($notFile?$message:'Could not load file! (<em>'.$message.'</em>)').'<br>
<strong>'.$file.': <em>'.$line.'</em></strong>', ($warning?'warning':'error'));

	}
	else
	{
		header('Content-type: text/html; charset=utf-8');
		die('<!DOCTYPE html>
<html>
<head>
<title>Critical error!</title>
</head>
<body>
<h1>
Critical error!
</h1>
<strong>'.($notFile?$message:'Could not load file <em>'.$message.'</em>!').' (<em>'.$file.':'.$line.'</em>)</strong>
'.($_SESSION['ERRORS']?'<h2>Debug ('.count($_SESSION['ERRORS']).' errors)</h2>
'.implode('', $_SESSION['ERRORS']):'').'
</body>
</html>');
	}
}

error_reporting(E_ALL);
set_error_handler('estats_error_handler');
session_start();

$start = microtime(TRUE);
$_SESSION['ERRORS'] = array();
$selectLocale = $selectTheme = $selectYears = $selectMonths = $selectDays = $selectHours = '';
$dirName = (is_dir($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:dirname($_SERVER['SCRIPT_NAME']));

if (is_readable('./conf/config.php'))
{
	include ('./conf/config.php');
}

if (!include ('./lib/driver.class.php'))
{
	estats_error_message('lib/driver.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/core.class.php'))
{
	estats_error_message('lib/core.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/cookie.class.php'))
{
	estats_error_message('lib/cookie.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/cache.class.php'))
{
	estats_error_message('lib/cache.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/backups.class.php'))
{
	estats_error_message('lib/backups.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/geolocation.class.php'))
{
	estats_error_message('lib/geolocation.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/locale.class.php'))
{
	estats_error_message('lib/locale.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/theme.class.php'))
{
	estats_error_message('lib/theme.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/gui.class.php'))
{
	estats_error_message('lib/gui.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/graphics.class.php'))
{
	estats_error_message('lib/graphics.class.php', __FILE__, __LINE__);
}

if (!include ('./lib/group.class.php'))
{
	estats_error_message('lib/group.class.php', __FILE__, __LINE__);
}

if (!include('./lib/chart.class.php'))
{
	estats_error_message('lib/chart.class.php', __FILE__, __LINE__);
}

if (!defined('eStats') || !defined('eStatsVersion') || eStatsVersion !== substr(ESTATS_VERSIONSTRING, 0, 3))
{
	define('ESTATS_INSTALL', TRUE);
}

EstatsTheme::add('datapath', (($dirName == '/')?'':$dirName).'/');

if (!defined('ESTATS_INSTALL'))
{
	if (!include ('./plugins/drivers/'.ESTATS_DATABASE_DRIVER.'/plugin.php'))
	{
		estats_error_message('plugins/drivers/'.ESTATS_DATABASE_DRIVER.'/plugin.php', __FILE__, __LINE__);
	}

	EstatsCore::init(1, ESTATS_SECURITY, './', ESTATS_DATA, ESTATS_DATABASE_DRIVER, ESTATS_DATABASE_PREFIX, ESTATS_DATABASE_CONNECTION, ESTATS_DATABASE_USER, ESTATS_DATABASE_PASSWORD, ESTATS_DATABASE_PERSISTENT);

	if (EstatsCore::option('Version') !== ESTATS_VERSIONSTRING)
	{
		EstatsCore::logEvent(EstatsCore::EVENT_SCRIPTVERSIONCHANGED, 'From: '.EstatsCore::option('Version').', to: '.ESTATS_VERSIONSTRING);
		EstatsCore::setConfiguration(array('Version' => ESTATS_VERSIONSTRING), 0);
	}
}

if (EstatsCore::option('Path/mode'))
{
	$path = (isset($_SERVER['PATH_INFO'])?explode('/', substr($_SERVER[((!$_SERVER['PATH_INFO'] && isset($_SERVER['ORIG_PATH_INFO']))?'ORIG_':'').'PATH_INFO'], 1)):array());
}
else
{
	$path = array();
}

if (empty($path))
{
	$path = (isset($_GET['path'])?explode('/', $_GET['path']):(isset($_GET['vars'])?explode('/', $_GET['vars']):array()));
}

if (defined('ESTATS_INSTALL'))
{
	$path[1] = 'install';
}
else if (!isset($path[1]) || (!is_file('pages/'.$path[1].'.php') && $path[1] !== 'tools' && $path[1] !== 'login'))
{
	$path[1] = 'general';
}

$groups = array(
	'general' => array('sites', 'referrers', 'websearchers', 'keywords', 'robots', 'languages', 'hosts', 'proxy',),
	'geolocation' => array('cities', 'countries', 'continents',),
	'technical' => array('browsers', 'operatingsystems', 'browser-versions', 'operatingsystem-versions', 'screens', 'flash', 'java', 'javascript', 'cookies',),
	'time' => array('24hours', 'month', 'year', 'years', 'hours', 'weekdays',),
	);

if (isset($_POST['year']))
{
	if ($_POST['year'])
	{
		$date = strtotime(((isset($_POST['day']) && $_POST['day'])?(($_POST['day'] < 10)?'0':'').(int) $_POST['day']:'01').'.'.($_POST['month']?(($_POST['month'] < 10)?'0':'').(int) $_POST['month']:'01').'.'.(int) $_POST['year'].(isset($_POST['hour'])?' '.(($_POST['hour'] < 10)?'0':'').(int) $_POST['hour'].':00':''));

		if (isset($_POST['previous']) || isset($_POST['next']))
		{
			if (isset($_POST['hour']))
			{
				$string = 'hour';
			}
			else if (isset($_POST['day']) && $_POST['day'])
			{
				$string = 'day';
			}
			else if ($_POST['month'])
			{
				$string = 'month';
			}
			else if ($_POST['year'])
			{
				$string = 'year';
			}
			else
			{
				$string = '';
			}

			if ($string)
			{
				$date = strtotime(date('d.m.Y H:00', $date).' '.(isset($_POST['previous'])?'-':'+').'1 '.$string);
			}
		}

		$date = date('Y-'.($_POST['month']?'n':'0').'-'.((isset($_POST['day']) && $_POST['day'])?'j':'0').'-G', $date);
	}
	else
	{
		$date = '0-0-0-0';
	}

	EstatsCookie::set('date', $date);

	die(header('Location: '.EstatsTheme::get('datapath').EstatsCore::option('Path/prefix').$path[0].'/'.$path[1].(($path[1] == 'geolocation')?'/'.$_POST['map']:'').(($path[1] !== 'time' && isset($path[($path[1] == 'geolocation')?4:3]))?'/'.$path[($path[1] == 'geolocation')?3:2]:'').(($path[1] == 'time')?((isset($path[2]) && in_array($path[2], $groups['time']))?'/'.$path[2]:'').((isset($_POST['TimeView']))?'/'.implode('+', $_POST['TimeView']):''):'').'/'.$date.(($path[1] == 'time' && isset($_POST['TimeCompare']))?'/compare':'').EstatsCore::option('Path/suffix')));
}

if (EstatsCookie::exists('theme'))
{
	$_SESSION[EstatsCore::session()]['theme'] = EstatsCookie::get('theme');
}

if (isset($_GET['theme']))
{
	$_SESSION[EstatsCore::session()]['theme'] = $_GET['theme'];
}

if (isset($_POST['theme']))
{
	$_SESSION[EstatsCore::session()]['theme'] = $_POST['theme'];

	EstatsCookie::set('theme', $_POST['theme']);
}

if (isset($_POST['locale']))
{
	die(header('Location: '.EstatsTheme::get('datapath').EstatsCore::option('Path/prefix').$_POST['locale'].'/'.implode('/', array_slice($path, 1)).EstatsCore::option('Path/suffix')));
}

$locales = EstatsLocale::available();

if (!isset($_SESSION[EstatsCore::session()]['locale']))
{
	$language = EstatsCore::language();

	if (strlen($language) > 1)
	{
		$_SESSION[EstatsCore::session()]['locale'] = (array_key_exists($language, EstatsLocale::available())?$language:(array_key_exists(substr($language, 0, 2), EstatsLocale::available())?substr($language, 0, 2):'en'));
	}
}

if (!isset($path[0]) || !EstatsLocale::exists($path[0]))
{
	$path[0] = (empty($_SESSION[EstatsCore::session()]['locale'])?'en':$_SESSION[EstatsCore::session()]['locale']);
}

if (!is_readable('locale/'.$path[0].'/locale.ini'))
{
	$path[0] = 'en';
}

EstatsLocale::set($path[0], (defined('ESTATS_GETTEXT')?ESTATS_GETTEXT:NULL));

$_SESSION[EstatsCore::session()]['locale'] = $path[0];

foreach ($locales as $key => $value)
{
	$selectLocale.= '<option value="'.$key.'"'.(($key == $path[0])?' selected="selected"':'').'>'.$value.'</option>
';
}

if (defined('ESTATS_INSTALL'))
{
	define('ESTATS_USERLEVEL', 0);
}
else
{
	if (isset($_GET['logout']))
	{
		unset($_SESSION[EstatsCore::session()]['email']);
		unset($_SESSION[EstatsCore::session()]['password']);

		EstatsCookie::delete('email');
		EstatsCookie::delete('password');

		die(header('Location: '.$_SERVER['PHP_SELF']));
	}

	if (empty($_SESSION[EstatsCore::session()]['password']) && EstatsCookie::exists('password'))
	{
		if (!EstatsCookie::exists('email') && EstatsCookie::get('password') == md5(EstatsCore::option('AccessPassword').EstatsCore::option('UniqueID')))
		{
			$_SESSION[EstatsCore::session()]['password'] = EstatsCore::option('AccessPassword');

			EstatsCookie::set('password', md5($_SESSION[EstatsCore::session()]['password'].EstatsCore::option('UniqueID')), 1209600);
		}

		if (EstatsCookie::exists('email'))
		{
			$data = EstatsCore::driver()->selectRow('users', NULL, array(array(EstatsDriver::ELEMENT_OPERATION, array('email', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, EstatsCookie::get('email'))))));

			if ($data && EstatsCookie::get('password') == md5($data['password'].EstatsCore::option('UniqueID')))
			{
				if (!isset($_SESSION[EstatsCore::session()]['password']) || $_SESSION[EstatsCore::session()]['password'] !== $data['password'])
				{
					EstatsCore::logEvent((($data['level'] < 3)?EstatsCore::EVENT_USERLOGGEDIN:EstatsCore::EVENT_ADMINISTRATORLOGGEDIN), 'IP: '.EstatsCore::IP());
				}

				$_SESSION[EstatsCore::session()]['email'] = $data['email'];
				$_SESSION[EstatsCore::session()]['password'] = $data['password'];

				EstatsCookie::set('email', $_SESSION[EstatsCore::session()]['email'], 1209600);
				EstatsCookie::set('password', md5($_SESSION[EstatsCore::session()]['password'].EstatsCore::option('UniqueID')), 1209600);
			}
		}
	}

	if (!empty($_POST['Email']))
	{
		$_SESSION[EstatsCore::session()]['email'] = $_POST['Email'];

		if (isset($_POST['Remember']))
		{
			EstatsCookie::set('email', $_POST['Email'], 1209600);
		}
	}

	if (isset($_POST['Password']))
	{
		$_SESSION[EstatsCore::session()]['password'] = md5($_POST['Password']);

		if (isset($_POST['Remember']))
		{
			EstatsCookie::set('password', md5($_SESSION[EstatsCore::session()]['password'].EstatsCore::option('UniqueID')), 1209600);
		}
	}

	if (isset($_SESSION[EstatsCore::session()]['password']))
	{
		if (empty($_SESSION[EstatsCore::session()]['email']))
		{
			define('ESTATS_USERLEVEL', (($_SESSION[EstatsCore::session()]['password'] == EstatsCore::option('AccessPassword'))?1:0));
		}
		else
		{
			$data = EstatsCore::driver()->selectRow('users', NULL, array(array(EstatsDriver::ELEMENT_OPERATION, array('email', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, EstatsCookie::get('email'))))));

			if (!empty($data['password']) && $_SESSION[EstatsCore::session()]['password'] == $data['password'])
			{
				define('ESTATS_USERLEVEL', $data['level']);
			}
			else
			{
				define('ESTATS_USERLEVEL', 0);
			}
		}
	}
	else
	{
		define('ESTATS_USERLEVEL', 0);
	}

	if (isset($_POST['Password']))
	{
		if (ESTATS_USERLEVEL)
		{
			EstatsCore::logEvent(((ESTATS_USERLEVEL < 3)?EstatsCore::EVENT_USERLOGGEDIN:EstatsCore::EVENT_ADMINISTRATORLOGGEDIN), 'IP: '.EstatsCore::IP());
		}
		else
		{
			EstatsCore::logEvent((($path[1] == 'tools')?EstatsCore::EVENT_FAILEDADMISNISTRATORLOGIN:EstatsCore::EVENT_FAILEDUSERLOGIN), 'IP: '.EstatsCore::IP());
			EstatsGUI::notify(EstatsLocale::translate('Wrong password or email!'), 'error');
		}
	}
}

EstatsTheme::add('meta', '');
EstatsTheme::add('selectmap', '');
EstatsTheme::add('selecthour', '');
EstatsTheme::add('selectday', '');
EstatsTheme::add('selectmonth', '');
EstatsTheme::add('selectyear', '');

if (!isset($_SESSION[EstatsCore::session()]['theme']) || !EstatsTheme::exists($_SESSION[EstatsCore::session()]['theme']))
{
	$browser = EstatsCore::detectBrowser($_SERVER['HTTP_USER_AGENT']);

	if ((in_array($browser[0], array('Dillo', 'OffByOne', 'Links', 'ELinks', 'Lynx', 'W3M')) || ($browser[0] == 'Internet Explorer' && ((double) $browser[1]) < 6)) && is_file('share/themes/Simple/theme.tpl'))
	{
		$_SESSION[EstatsCore::session()]['theme'] = 'Simple';
	}
	else
	{
		$_SESSION[EstatsCore::session()]['theme'] = EstatsCore::option('DefaultTheme');
	}
}

if (!EstatsTheme::set($_SESSION[EstatsCore::session()]['theme']))
{
	estats_error_message(sprintf(EstatsLocale::translate('Can not load theme %s!'), $_SESSION[EstatsCore::session()]['theme']), __FILE__, __LINE__, TRUE);
}

EstatsTheme::load('common');

if (!EstatsLocale::option('Status'))
{
	EstatsGUI::notify(sprintf(EstatsLocale::translate('This translation (%s) is not complete!'), $locales[$path[0]]), 'warning');
}

if (!isset($_SESSION[EstatsCore::session()]['viewTime']))
{
	$_SESSION[EstatsCore::session()]['viewTime'] = 0;
}

$themes = EstatsTheme::available();

foreach ($themes as $key => $value)
{
	$selectTheme.= '<option value="'.urlencode($key).'"'.(($key == $_SESSION[EstatsCore::session()]['theme'])?' selected="selected"':'').'>'.htmlspecialchars($value).'</option>
';
}

if (isset($_GET['checkversion']))
{
	if (((ESTATS_USERLEVEL == 2 && EstatsCore::option('CheckVersionTime')) || (defined('ESTATS_INSTALL') && !isset($_POST))) && (!isset($_SESSION[EstatsCore::session()]['LatestVersion']) || ($_SERVER['REQUEST_TIME'] - $_SESSION[EstatsCore::session()]['LatestVersion'][1]) > EstatsCore::option('CheckVersionTime')))
	{
		$handle = fopen('http://estats.emdek.pl/current.php?'.$_SERVER['SERVER_NAME'].'---'.$_SERVER['SCRIPT_NAME'].'---'.ESTATS_VERSIONSTRING, 'r');

		if ($handle)
		{
			$_SESSION[EstatsCore::session()]['LatestVersion'] = array(fread($handle, 6), $_SERVER['REQUEST_TIME']);

			fclose($handle);

			if (str_replace('.', '', $_SESSION[EstatsCore::session()]['LatestVersion'][0]) > str_replace('.', '', ESTATS_VERSIONSTRING))
			{
				$_SESSION[EstatsCore::session()]['NewerVersion'] = &$_SESSION[EstatsCore::session()]['LatestVersion'][0];
			}
		}
		else
		{
			$_SESSION[EstatsCore::session()]['CheckVersionError'] = TRUE;
		}
	}

	if (isset($_GET['script']))
	{
		die();
	}
}

EstatsTheme::add('loggedin', (ESTATS_USERLEVEL && !defined('ESTATS_INSTALL')));
EstatsTheme::add('administrator', (ESTATS_USERLEVEL == 2));
EstatsTheme::add('installation', defined('ESTATS_INSTALL'));
EstatsTheme::add('graphics', EstatsGraphics::isAvailable());
EstatsTheme::add('geolocation', EstatsGeolocation::isAvailable());
EstatsTheme::add('selectform', (count($locales) > 1 || $selectTheme));
EstatsTheme::add('antiflood', (!defined('ESTATS_INSTALL') && ($_SERVER['REQUEST_TIME'] - $_SESSION[EstatsCore::session()]['viewTime']) < 2 && !ESTATS_USERLEVEL));
EstatsTheme::add('dir', EstatsLocale::option('Dir'));
EstatsTheme::add('language', $path[0]);
EstatsTheme::add('theme', $_SESSION[EstatsCore::session()]['theme']);
EstatsTheme::add('lang_gototop', EstatsLocale::translate('Go to top'));
EstatsTheme::add('lang_change', EstatsLocale::translate('Change'));
EstatsTheme::add('header', (defined('ESTATS_INSTALL')?'eStats <em>v'.ESTATS_VERSIONSTRING.'</em> :: '.EstatsLocale::translate('Installer'):EstatsCore::option('Header')));
EstatsTheme::add('selectlocale', ((count($locales) > 1)?'<select name="locale" title="'.EstatsLocale::translate('Choose language').'">
'.$selectLocale.'</select>
':''));
EstatsTheme::add('selecttheme', ((count($themes) > 2)?'<select name="theme" title="'.EstatsLocale::translate('Choose theme').'">
'.$selectTheme.'</select>
':''));
EstatsTheme::add('path', EstatsTheme::get('datapath').EstatsCore::option('Path/prefix').$path[0].'/');
EstatsTheme::add('suffix', EstatsCore::option('Path/suffix'));

if (ESTATS_USERLEVEL == 2)
{
	if ($_GET)
	{
		$array = array(
	'keyword' => 'Keywords',
	'referrer' => 'Referrers',
	'ignoredIP' => 'IgnoredIPs',
	'blockedIP' => 'BlockedIPs'
	);
		foreach ($array as $key => $value)
		{
			if (isset($_GET[$key]))
			{
				$tmpArray = $$value;

				if (in_array($_GET[$key], $tmpArray))
				{
					unset($tmpArray[array_search($_GET[$key], $tmpArray)]);
				}
				else
				{
					$tmpArray[] = $_GET[$key];

					if ($key == 'keyword' || $key == 'referrer')
					{
						EstatsCore::driver()->deleteData($key.'s', array(array(EstatsDriver::ELEMENT_OPERATION, array('name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, urldecode($_GET[$key]))))));
					}
				}

				EstatsCore::setConfiguration(array($value => implode('|', $tmpArray)));
			}
		}
	}

	if (isset($_GET['enable']))
	{
		EstatsCore::setConfiguration(array('StatsEnabled' => TRUE));
	}

	if (isset($_GET['maintenance']))
	{
		EstatsCore::setConfiguration(array('Maintenance' => FALSE));
	}
}

if (defined('ESTATS_INSTALL'))
{
	if (!include ('./install.php'))
	{
		estats_error_message('install.php', __FILE__, __LINE__);
	}
}
else
{
	EstatsCache::enable(ESTATS_USERLEVEL < 2 || EstatsCore::option('Cache/enableforadministrator'));

	if (EstatsCore::option('Cache/clearinterval') && ($_SERVER['REQUEST_TIME'] - EstatsCore::option('LastClean')) > EstatsCore::option('Cache/clearinterval'))
	{
		EstatsCore::setConfiguration(array('LastClean' => $_SERVER['REQUEST_TIME']));

		EstatsCache::delete();
	}

	EstatsTheme::add('startdate', date('d.m.Y', EstatsCore::option('CollectedFrom')));
	EstatsTheme::add('starttime', date('H:i:s', EstatsCore::option('CollectedFrom')));
	EstatsTheme::add('servername', $_SERVER['SERVER_NAME']);
	EstatsTheme::add('lang_statsfor', EstatsLocale::translate('Statistics for'));
	EstatsTheme::add('lang_logout', EstatsLocale::translate('Log out'));
	EstatsTheme::add('lang_login', EstatsLocale::translate('Log in'));
	EstatsTheme::add('lang_collectedfrom', EstatsLocale::translate('Data collected from'));

	$titles = array(
	'general' => EstatsLocale::translate('General'),
	'technical' => EstatsLocale::translate('Technical'),
	'geolocation' => EstatsLocale::translate('Geolocation'),
	'time' => EstatsLocale::translate('Time'),
	'visits' => EstatsLocale::translate('Visits'),
	'tools' => EstatsLocale::translate('Tools'),
	'sites' => EstatsLocale::translate('Sites'),
	'referrers' => EstatsLocale::translate('Referrers'),
	'hosts' => EstatsLocale::translate('Hosts'),
	'keywords' => EstatsLocale::translate('Keywords'),
	'languages' => EstatsLocale::translate('Languages'),
	'cities' => EstatsLocale::translate('Cities'),
	'countries' => EstatsLocale::translate('Countries'),
	'continents' => EstatsLocale::translate('Continents'),
	'regions' => EstatsLocale::translate('Regions'),
	'browsers' => EstatsLocale::translate('Browsers'),
	'browser-versions' => EstatsLocale::translate('Browser versions'),
	'operatingsystems' => EstatsLocale::translate('Operating systems'),
	'operatingsystem-versions' => EstatsLocale::translate('Operating system versions'),
	'websearchers' => EstatsLocale::translate('Web searchers'),
	'robots' => EstatsLocale::translate('Network robots'),
	'proxy' => EstatsLocale::translate('Proxy'),
	'screens' => EstatsLocale::translate('Screen resolutions'),
	'flash' => EstatsLocale::translate('Flash plugin'),
	'java' => EstatsLocale::translate('Java'),
	'javascript' => EstatsLocale::translate('JavaScript'),
	'cookies' => EstatsLocale::translate('Cookies'),
	'24hours' => EstatsLocale::translate('24 hours'),
	'week' => EstatsLocale::translate('Week'),
	'month' => EstatsLocale::translate('Month'),
	'year' => EstatsLocale::translate('Year'),
	'years' => EstatsLocale::translate('Years'),
	'hours' => EstatsLocale::translate('Hours'),
	'weekdays' => EstatsLocale::translate('Days of week')
	);

	$var = $subMenuVar = 0;
	$permittedTools = $toolInformation = array();

	if (EstatsCache::status('tools', 86400))
	{
		$data = array();
		$tools = glob('./plugins/tools/*/plugin.ini');

		for ($i = 0, $c = count($tools); $i < $c; ++$i)
		{
			$information = EstatsCore::loadData($tools[$i], FALSE, FALSE);

			if (isset($information['Level']) && isset($information['Name']) && isset($information['Name']['en']))
			{
				$toolName = basename(dirname($tools[$i]));
				$toolInformation[(isset($information['Position'])?$information['Position']:1000).'-'.$toolName] = array('name' => $toolName, 'title' => $information['Name'], 'level' => $information['Level']);
			}
			else
			{
				continue;
			}
		}

		ksort($toolInformation, SORT_NUMERIC);

		$toolInformation = array_values($toolInformation);

		for ($i = 0, $c = count($toolInformation); $i < $c; ++$i)
		{
			$toolInformation[$toolInformation[$i]['name']] = array('title' => &$toolInformation[$i]['title'], 'level' => &$toolInformation[$i]['level']);

			unset($toolInformation[$i]);
		}

		EstatsCache::save('tools', $toolInformation);
	}
	else
	{
		$toolInformation = EstatsCache::read('tools');
	}

	foreach ($toolInformation as $key => $value)
	{
		if ($value['level'] <= ESTATS_USERLEVEL)
		{
			$permittedTools[] = $key;
		}
	}

	if ($path[1] == 'tools' && (!isset($path[2]) || !is_file('plugins/tools/'.$path[2].'/plugin.php') || !in_array($path[2], $permittedTools)))
	{
		$path[2] = reset($permittedTools);
	}

	switch ($path[1])
	{
		case 'general':
		case 'technical':
			if (isset($path[2]) && (in_array($path[2], $groups[$path[1]]) || (($path[2] == 'operatingsystem-versions' || $path[2] == 'browsers-versions') && $path[1] == 'technical')))
			{
				$var = 3;
				$subMenuVar = 2;
			}
			else
			{
				$var = 2;
			}
		break;
		case 'geolocation':
			$fileName = 'countries-list';

			if (EstatsCache::status($fileName, 3600) || ESTATS_USERLEVEL == 2)
			{
				$availableCountries = array();
				$data = EstatsCore::driver()->selectData(array('geoip'), array('country'), NULL, 0, 0, NULL, array('country'));

				for ($i = 0, $c = count($data); $i < $c; ++$i)
				{
					$availableCountries[] = $data[$i]['country'];
				}

				EstatsCache::save($fileName, $availableCountries);
			}
			else
			{
				$availableCountries = EstatsCache::read($fileName);
			}

			$var = 3;
			$subMenuVar = 2;

			if (isset($path[2]) && ($path[2] == 'world' || in_array($path[2], $availableCountries)))
			{
				if (isset($path[3]) && ((in_array($path[3], array('cities', 'regions')) && in_array($path[2], $availableCountries)) || (in_array($path[3], array('cities', 'countries', 'continents')) && $path[2] == 'world')))
				{
					$var = 4;
					$subMenuVar = 3;
				}
			}
			else if (empty($path[2]) || !in_array($path[2], array('countries', 'continents')))
			{
				if (isset($path[2]) && $path[2] == 'cities')
				{
					$var = 4;
					$subMenuVar = 3;
					$path[2] = 'world';
					$path[3] = 'cities';
				}
				else
				{
					$path[2] = 'countries';
				}
			}
		break;
		case 'time':
			$var = 3;
			$subMenuVar = 2;

			if (isset($path[2]) && in_array($path[2], $groups['time']))
			{
				$var = 4;
			}
		break;
	}

	$viewTypes = array();
	$availableViewTypes = array('views', 'unique', 'returns');

	if (EstatsCookie::exists('timeview') && in_array($path[1], array('general', 'technical', 'time')))
	{
		$path[$var] = EstatsCookie::get('timeview');
	}

	if (isset($_POST['TimeView']))
	{
		$path[$var - 1] = implode('+', $_POST['TimeView']);

		EstatsCookie::set('timeview', $path[$var - 1]);
	}

	if (isset($path[$var - 1]) && $path[$var - 1])
	{
		$tmpTypes = explode('+', $path[$var - 1]);

		for ($i = 0, $c = count($tmpTypes); $i < $c; ++$i)
		{
			if (in_array($tmpTypes[$i], $availableViewTypes) && !in_array($tmpTypes[$i], $viewTypes))
			{
				$viewTypes[] = $tmpTypes[$i];
			}
		}
	}

	if (!$viewTypes)
	{
		$viewTypes = $availableViewTypes;
	}

	$timeView = implode('+', $viewTypes);

	if ($path[1] == 'time')
	{
		$path[$var - 1] = $timeView;
	}

	if (!isset($path[$var]) || !$path[$var])
	{
		if ($path[1] == 'time')
		{
			$date = array(0, 0, 0, 0);
		}
		else
		{
			if (EstatsCookie::exists('date'))
			{
				$date = explode('-', EstatsCookie::get('date'));
			}
			else if (date('n') > 3)
			{
				$date = array(date('Y'), 0, 0, 0);
			}
			else
			{
				$date = array(0, 0, 0, 0);
			}
		}
	}
	else
	{
		$date = explode('-', $path[$var]);
	}

	if (empty($date[0]) || !in_array($date[0], range(date('Y', EstatsCore::option('CollectedFrom')), date('Y'))))
	{
		$date = array_fill(0, 4, 0);
	}
	else if (empty($date[1]) || $date[0].(($date[1] < 10)?'0':'').$date[1] < date('Ym', EstatsCore::option('CollectedFrom')) || $date[0].(($date[1] < 10)?'0':'').$date[1] > date('Ym'))
	{
		$date[1] = $date[2] = 0;
	}
	else if (empty($date[2]) || $date[2] < 1 || $date[2] > date('t', strtotime($date[0].'-'.(($date[1] < 10)?'0':'').$date[1].'-01')))
	{
		$date[2] = 0;
	}
	else if (empty($date[3]) || $date[3] < 0 || $date[3] > 23)
	{
		$date[3] = 0;
	}

	EstatsTheme::add('period', implode('-', $date));

	if (in_array($path[1], array('general', 'technical', 'time')))
	{
		$path[$var] = EstatsTheme::get('period');
	}

	$menu = array('general', 'technical', 'geolocation', 'time', 'visits');

	if (count($toolInformation) > 0)
	{
		$menu[] = 'tools';
	}

	$accessKeys = array();

	for ($i = 0, $c = count($menu); $i < $c; ++$i)
	{
		if (($menu[$i] == 'geolocation' && !EstatsGeolocation::isAvailable()) || ($menu[$i] == 'time' && !EstatsCore::driver()->selectAmount('time')))
		{
			continue;
		}

		for ($j = 0, $l = strlen($titles[$menu[$i]]); $j < $l; ++$j)
		{
			$accessKey = strtoupper($titles[$menu[$i]][$j]);

			if (!in_array($accessKey, $accessKeys) && $accessKey >= 'A' && $accessKey <= 'Z')
			{
				$accessKeys[] = $accessKey;

				break;
			}
		}

		EstatsTheme::add('menu-'.$menu[$i], EstatsTheme::parse(EstatsTheme::get('menu-entry'), array(
	'link' => '{path}'.$menu[$i].'{suffix}',
	'text' => $titles[$menu[$i]],
	'class' => (($path[1] == $menu[$i])?'active':''),
	'id' => $menu[$i],
	'icon' => EstatsGUI::iconPath($menu[$i], 'pages'),
	'entry' => $menu[$i],
	'accesskey' => $accessKey
	)));
		EstatsTheme::add('submenu-'.$menu[$i], '');
		EstatsTheme::add('submenu-'.$menu[$i], FALSE);

		if (isset($groups[$menu[$i]]))
		{
			EstatsTheme::add('submenu-'.$menu[$i], TRUE);

			for ($j = 0, $l = count($groups[$menu[$i]]); $j < $l; ++$j)
			{
				EstatsTheme::add('submenu-'.$menu[$i].'_'.$groups[$menu[$i]][$j], FALSE);

				if (isset($groupAmount[$groups[$menu[$i]][$j]]) && !$groupAmount[$groups[$menu[$i]][$j]])
				{
					continue;
				}

				EstatsTheme::append('submenu-'.$menu[$i], EstatsTheme::parse(EstatsTheme::get((EstatsTheme::contains('submenu-entry')?'sub':'').'menu-entry'), array(
	'link' => '{path}'.$menu[$i].'/'.$groups[$menu[$i]][$j].'{suffix}',
	'text' => $titles[$groups[$menu[$i]][$j]],
	'class' => ((isset($path[$subMenuVar]) && $path[$subMenuVar] == $groups[$menu[$i]][$j])?'active':''),
	'id' => $menu[$i].'_'.$groups[$menu[$i]][$j],
	'icon' => EstatsGUI::iconPath($groups[$menu[$i]][$j], 'pages'),
	'entry' => $groups[$menu[$i]][$j],
	'accesskey' => ''
	)));
			}
		}
		else if ($menu[$i] == 'tools')
		{
			EstatsTheme::add('submenu-'.$menu[$i], (count($permittedTools) > 0));

			for ($j = 0, $l = count($permittedTools); $j < $l; ++$j)
			{
				EstatsTheme::add('submenu-'.$menu[$i].'_'.$permittedTools[$j], FALSE);
				EstatsTheme::append('submenu-'.$menu[$i], EstatsTheme::parse(EstatsTheme::get((EstatsTheme::contains('submenu-entry')?'sub':'').'menu-entry'), array(
	'link' => '{path}tools/'.$permittedTools[$j].'{suffix}',
	'text' => $toolInformation[$permittedTools[$j]]['title'][isset($toolInformation[$permittedTools[$j]]['title'][$_SESSION[EstatsCore::session()]['locale']])?$_SESSION[EstatsCore::session()]['locale']:'en'],
	'class' => ((isset($path[2]) && $path[2] == $permittedTools[$j])?'active':''),
	'id' => 'tools_'.$permittedTools[$j],
	'icon' => EstatsGUI::iconPath($permittedTools[$j], 'pages'),
	'entry' => $permittedTools[$j],
	'accesskey' => ''
	)));
			}
		}

		EstatsTheme::append('menu', str_replace('{submenu}', EstatsTheme::get('submenu-'.$menu[$i]), EstatsTheme::get('menu-'.$menu[$i])));
	}

	if (in_array($path[1], array('general', 'technical', 'geolocation', 'time')))
	{
		for ($hour = 0; $hour < 24; ++$hour)
		{
			$selectHours.= '<option'.(((int) $date[3] == $hour && $date[2])?' selected="selected"':'').'>'.$hour.'</option>
';
		}

		EstatsTheme::add('selecthour', '<select name="hour" id="hour" title="'.EstatsLocale::translate('Hour').'">
<option'.(($date[3] && $date[2])?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$selectHours.'</select>
');

		for ($day = 1; $day <= 31; ++$day)
		{
			$selectDays.= '<option'.(((int) $date[2] == $day)?' selected="selected"':'').'>'.$day.'</option>
';
		}

		EstatsTheme::add('selectday', '<select name="day" id="day" title="'.EstatsLocale::translate('Day').'">
<option'.($date[2]?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$selectDays.'</select>
');

		for ($month = 1; $month <= 12; ++$month)
		{
			$selectMonths.= '<option value="'.$month.'"'.(((int) $date[1] == $month)?' selected="selected"':'').'>'.ucfirst(strftime('%B', (mktime(0, 0, 0, $month, 1)))).'</option>
';
		}

		EstatsTheme::add('selectmonth', '<select name="month" id="month" title="'.EstatsLocale::translate('Month').'">
<option'.($date[1]?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$selectMonths.'</select>
');

		for ($year = date('Y', EstatsCore::option('CollectedFrom')); $year <= date('Y'); ++$year)
		{
			$selectYears.= '<option value="'.$year.'"'.(($date[0] == $year)?' selected="selected"':'').'>'.$year.'</option>
';
		}

		EstatsTheme::add('selectyear', '<select name="year" id="year" title="'.EstatsLocale::translate('Year').'">
<option'.($date[0]?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$selectYears.'</select>
');
		EstatsTheme::add('lang_showdatafor', EstatsLocale::translate('Show data for'));
		EstatsTheme::add('lang_show', EstatsLocale::translate('Show'));
		EstatsTheme::add('dateprevious', '<input type="submit" name="previous" value="'.EstatsLocale::translate('Previous').'">
');
		EstatsTheme::add('datenext', '<input type="submit" name="next" value="'.EstatsLocale::translate('Next').'">
');
	}

	if (!EstatsCore::option('AccessPassword'))
	{
		$feeds = array(
	'daily' => EstatsLocale::translate('Daily summary'),
	'weekly' => EstatsLocale::translate('Weekly summary'),
	'monthly' => EstatsLocale::translate('Monthly summary')
	);

		foreach ($feeds as $key => $value)
 		{
			EstatsTheme::append('meta', '<link rel="alternate" type="application/atom+xml" href="{path}feed/'.$key.'{suffix}" title="'.$value.'">
');
		}
	}

	if (ESTATS_USERLEVEL < 2 && EstatsCore::option('Maintenance'))
	{
		EstatsGUI::notify(EstatsLocale::translate('Page unavailable due to maintenance.'), 'information');
		EstatsTheme::add('title', EstatsLocale::translate('Maintenance'));
	}
	else if (EstatsCore::containsIP(EstatsCore::IP(), EstatsCore::option('BlockedIPs')))
	{
		EstatsCore::ignoreVisit(TRUE);
		EstatsGUI::notify(EstatsLocale::translate('This IP address was blocked!'), 'error');
		EstatsTheme::add('title', EstatsLocale::translate('Access denied'));
	}
	else if ((EstatsCore::option('AccessPassword') && !ESTATS_USERLEVEL) || (ESTATS_USERLEVEL < 2 && ($path[1] === 'login' || ($path[1] === 'tools' && (!isset($path[2]) || EstatsGUI::toolLevel($path[2]) > ESTATS_USERLEVEL)))))
	{
		EstatsTheme::load('login');
		EstatsTheme::link('login', 'page');
		EstatsTheme::add('title', EstatsLocale::translate('Login'));
		EstatsTheme::add('lang_email', EstatsLocale::translate('Email'));
		EstatsTheme::add('lang_password', EstatsLocale::translate('Password'));
		EstatsTheme::add('lang_remember', EstatsLocale::translate('Remember password'));
		EstatsTheme::add('lang_loginto', EstatsLocale::translate('Log into'));
	}
	else if (($_SERVER['REQUEST_TIME'] - $_SESSION[EstatsCore::session()]['viewTime']) < 2 && !ESTATS_USERLEVEL && $path[1] !== 'image')
	{
		EstatsTheme::append('meta', '<meta http-equiv="Refresh" content="2">
');
		EstatsGUI::notify(EstatsLocale::translate('You can not refresh page so quickly!'), 'error');
		EstatsTheme::add('title', EstatsLocale::translate('Access denied'));
	}
	else
	{
		if ($path[1] == 'tools')
		{
			if (EstatsGUI::toolLevel($path[2]) > ESTATS_USERLEVEL)
			{
				estats_error_message(EstatsLocale::translate('You are not allowed to use this tool!'), __FILE__, __LINE__, TRUE);
			}
		}
		else
		{
			if (!is_file('./pages/'.$path[1].'.php'))
			{
				$path[1] = 'general';
			}

			EstatsTheme::load($path[1]);

			EstatsTheme::link((EstatsTheme::contains($path[1])?$path[1]:''), 'page');
		}

		if ($path[1] !== 'image' && $path[1] !== 'feed')
		{
			EstatsTheme::add('title', EstatsLocale::translate($titles[$path[1]]).(($path[1] == 'tools')?' - '.$toolInformation[$path[2]]['title'][isset($toolInformation[$path[2]]['title'][$_SESSION[EstatsCore::session()]['locale']])?$_SESSION[EstatsCore::session()]['locale']:'en']:''));
		}

		$pagePath = (($path[1] == 'tools')?'plugins/tools/'.$path[2].'/plugin.php':'pages/'.$path[1].'.php');

		if (!include ('./'.$pagePath))
		{
			estats_error_message($pagePath, __FILE__, __LINE__);
		}
	}

	$_SESSION[EstatsCore::session()]['viewTime'] = $_SERVER['REQUEST_TIME'];
}

if (ESTATS_USERLEVEL == 2 || defined('ESTATS_INSTALL'))
{
	if (isset($_SESSION[EstatsCore::session()]['NewerVersion']) && $_SESSION[EstatsCore::session()]['NewerVersion'] !== ESTATS_VERSIONSTRING)
	{
		EstatsGUI::notify(sprintf(EstatsLocale::translate('New version is available (%s)!'), $_SESSION[EstatsCore::session()]['NewerVersion']), 'information');
	}
	else if (isset($_SESSION[EstatsCore::session()]['CheckVersionError']))
	{
		EstatsGUI::notify(EstatsLocale::translate('Could not check for new version availability.'), 'warning');

		unset($_SESSION[EstatsCore::session()]['CheckVersionError']);
	}
}

if (ESTATS_VERSIONSTATUS !== 'stable')
{
	EstatsGUI::notify(sprintf(EstatsLocale::translate('This is a test version of <em>eStats</em> (status: <em>%s</em>).<br>
Its functionality could be incomplete, could work incorrect and be incompatible with newest versions!<br>
<strong style="text-decoration:underline;">Use at own risk!</strong>'), ESTATS_VERSIONSTATUS), 'warning');
}

if ((ESTATS_USERLEVEL == 2 || defined('ESTATS_INSTALL')) && ini_get('safe_mode'))
{
	EstatsGUI::notify(EstatsLocale::translate('<em>PHP safe mode</em> has been activated on this server!<br>
That could cause problems in case of automatic creation of files and directories.<br>
Solution is change of their owner or manual creation.'), 'warning');
}

if (EstatsCore::option('Maintenance') && ESTATS_USERLEVEL == 2)
{
	EstatsGUI::notify(EstatsLocale::translate('Maintenance mode is active!').'<br>
<a href="{selfpath}{separator}maintenance"><strong>'.EstatsLocale::translate('Disable maintenance mode').'</strong></a>.', 'warning');
}

if (!EstatsCore::option('StatsEnabled') && !defined('ESTATS_INSTALL'))
{
	EstatsGUI::notify(EstatsLocale::translate('Statistics are disabled.').((ESTATS_USERLEVEL == 2)?'<br>
<a href="{selfpath}{separator}enable"><strong>'.EstatsLocale::translate('Enable data collecting').'</strong></a>.':''), 'information');
}

if (EstatsTheme::contains('css'))
{
	EstatsTheme::add('css', '<style type="text/css">
'.EstatsTheme::get('css').'</style>
');
}
else
{
	EstatsTheme::add('css', '');
}

$notifications = EstatsGUI::notifications();

if ($notifications)
{
	for ($i = 0, $c = count($notifications); $i < $c; ++$i)
	{
		$message = explode('|', $notifications[$i][0]);

		EstatsTheme::append('announcements', EstatsGUI::notificationWidget((is_numeric($message[0])?EstatsLocale::translate($logs[$message[0]]).'.':$message[0]).(isset($message[1])?'<br>
<em>'.$message[1].'</em>.':''), $notifications[$i][1]));
	}
}
else
{
	EstatsTheme::add('announcements', '');
}

if (file_exists('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/images/'))
{
	$images = glob('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/images/*.{png,jpg,gif}', GLOB_BRACE);

	for ($i = 0, $c = count($images); $i < $c; ++$i)
	{
		EstatsTheme::append('preloader', '<img src="'.$images[$i].'" alt="">
');
	}
}

EstatsTheme::add('selfpath', EstatsTheme::get('datapath').EstatsCore::option('Path/prefix').implode('/', $path).EstatsCore::option('Path/suffix'));
EstatsTheme::add('separator', htmlspecialchars(EstatsCore::option('Path/separator'), ENT_QUOTES, 'UTF-8', FALSE));
EstatsTheme::add('date', date('d.m.Y H:i:s T'));
EstatsTheme::add('announcements', (count($_SESSION['ERRORS']) > 0 || count(EstatsGUI::notifications()) > 0));
EstatsTheme::add('menu', EstatsTheme::contains('menu'));

if (is_file('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/theme.php'))
{
	include ('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/theme.php');
}

$page = EstatsTheme::parse(EstatsTheme::parse(EstatsTheme::parse(EstatsTheme::get('index'), array('page' => EstatsTheme::get('page')))), array('pagegeneration' => sprintf(EstatsLocale::translate('Page generation time: %.3lf (s)'), (microtime(TRUE) - $start))), TRUE);

if ($_SESSION['ERRORS'] && (ESTATS_USERLEVEL == 2 || defined('ESTATS_INSTALL')))
{
	$debug = EstatsGUI::notificationWidget('<h4 id="debug_header" onclick="document.getElementById(\'debug\').style.display = ((document.getElementById(\'debug\').style.display == \'none\')?\'block\':\'none\')">'.EstatsLocale::translate('Debug').' ('.count($_SESSION['ERRORS']).')</h4>
<div id="debug">
'.rtrim(implode('', $_SESSION['ERRORS']), "\r\n").'
</div>
<script type="text/javascript">
document.getElementById(\'debug\').style.display = \'none\';
document.getElementById(\'debug_header\').style.cursor = \'pointer\';
</script>
', 'information');
}
else
{
	$debug = '';
}

$page = str_replace('{debug}', $debug, EstatsTheme::parse($page));

header(EstatsTheme::option('Header'));

if (defined('ESTATS_GZIP') && ESTATS_GZIP && function_exists('ob_gzhandler') && stristr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && !(ini_get('zlib.output_compression') == 'On' || ini_get('zlib.output_compression_level') > 0) || ini_get('output_handler') == 'ob_gzhandler')
{
	header('Content-Encoding: gzip');
	ob_start('ob_gzhandler');
}

die($page);
?>