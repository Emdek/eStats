<?php
/**
 * eStats - statistics for web pages
 * @author Emdek <http://emdek.pl>
 * @version 4.9.60
 * @date 2011-05-01 22:52:56
 */

define('ESTATS_VERSIONSTRING', '4.9.60');
define('ESTATS_VERSIONSTATUS', 'stable');
define('ESTATS_VERSIONTIME', 1304283176);

/**
 * Error handler
 * @param integer Number
 * @param string String
 * @param string File
 * @param integer Line
 */

function estats_error_handler($Number, $String, $File, $Line)
{
	if (strstr($String, 'date.timezone'))
	{
		return;
	}

	$ErrorTypes = array(
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
'.$ErrorTypes[$Number].' (<em>'.$File.':'.$Line.'</em>)
</h5>
'.$String.'<br />
';
}

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
	if ($Warning)
	{
		EstatsGUI::notify(($NotFile?$Error:'Could not load file! (<em>'.$Error.'</em>)').'<br />
<strong>'.$File.': <em>'.$Line.'</em></strong>', ($Warning?'warning':'error'));

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
<strong>'.($NotFile?$Error:'Could not load file <em>'.$Error.'</em>!').' (<em>'.$File.':'.$Line.'</em>)</strong>
'.($_SESSION['ERRORS']?'<h2>Debug ('.count($_SESSION['ERRORS']).' errors)</h2>
'.implode('', $_SESSION['ERRORS']):'').'
</body>
</html>');
	}
}

error_reporting(E_ALL);
set_error_handler('estats_error_handler');
session_start();

$Start = microtime(TRUE);
$_SESSION['ERRORS'] = array();
$SelectLocale = $SelectTheme = $SelectYears = $SelectMonths = $SelectDays = $SelectHours = '';
$DirName = (is_dir($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:dirname($_SERVER['SCRIPT_NAME']));

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

EstatsTheme::add('datapath', (($DirName == '/')?'':$DirName).'/');

if (!defined('ESTATS_INSTALL'))
{
	if (!include ('./plugins/drivers/'.$DBType.'/plugin.php'))
	{
		estats_error_message('plugins/drivers/'.$DBType.'/plugin.php', __FILE__, __LINE__);
	}

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

	EstatsCore::init(1, $DBID, './', $DataDir, $DBType, $DBPrefix, $DBConnection, $DBUser, $DBPass, $PConnect);

	if (EstatsCore::option('Version') !== ESTATS_VERSIONSTRING)
	{
		EstatsCore::logEvent(EstatsCore::EVENT_SCRIPTVERSIONCHANGED, 'From: '.EstatsCore::option('Version').', to: '.ESTATS_VERSIONSTRING);
		EstatsCore::setConfiguration(array('Version' => ESTATS_VERSIONSTRING), 0);
	}
}

if (EstatsCore::option('Path|mode'))
{
	$Path = (isset($_SERVER['PATH_INFO'])?explode('/', substr($_SERVER[((!$_SERVER['PATH_INFO'] && isset($_SERVER['ORIG_PATH_INFO']))?'ORIG_':'').'PATH_INFO'], 1)):array());
}
else
{
	$Path = array();
}

if (empty($Path))
{
	$Path = (isset($_GET['path'])?explode('/', $_GET['path']):(isset($_GET['vars'])?explode('/', $_GET['vars']):array()));
}

if (defined('ESTATS_INSTALL'))
{
	$Path[1] = 'install';
}
else if (!isset($Path[1]) || (!is_file('pages/'.$Path[1].'.php') && $Path[1] !== 'tools' && $Path[1] !== 'login'))
{
	$Path[1] = 'general';
}

$Groups = array(
	'general' => array('sites', 'referrers', 'websearchers', 'keywords', 'robots', 'languages', 'hosts', 'proxy',),
	'geolocation' => array('cities', 'countries', 'continents',),
	'technical' => array('browsers', 'operatingsystems', 'browser-versions', 'operatingsystem-versions', 'screens', 'flash', 'java', 'javascript', 'cookies',),
	'time' => array('24hours', 'month', 'year', 'years', 'hours', 'weekdays',),
	);

if (isset($_POST['year']))
{
	if ($_POST['year'])
	{
		$Date = strtotime(((isset($_POST['day']) && $_POST['day'])?(($_POST['day'] < 10)?'0':'').(int) $_POST['day']:'01').'.'.($_POST['month']?(($_POST['month'] < 10)?'0':'').(int) $_POST['month']:'01').'.'.(int) $_POST['year'].(isset($_POST['hour'])?' '.(($_POST['hour'] < 10)?'0':'').(int) $_POST['hour'].':00':''));

		if (isset($_POST['previous']) || isset($_POST['next']))
		{
			if (isset($_POST['hour']))
			{
				$String = 'hour';
			}
			else if (isset($_POST['day']) && $_POST['day'])
			{
				$String = 'day';
			}
			else if ($_POST['month'])
			{
				$String = 'month';
			}
			else if ($_POST['year'])
			{
				$String = 'year';
			}
			else
			{
				$String = '';
			}

			if ($String)
			{
				$Date = strtotime(date('d.m.Y H:00', $Date).' '.(isset($_POST['previous'])?'-':'+').'1 '.$String);
			}
		}

		$Date = date('Y-'.($_POST['month']?'n':'0').'-'.((isset($_POST['day']) && $_POST['day'])?'j':'0').'-G', $Date);
	}
	else
	{
		$Date = '0-0-0-0';
	}

	EstatsCookie::set('date', $Date);

	die(header('Location: '.EstatsTheme::get('datapath').EstatsCore::option('Path|prefix').$Path[0].'/'.$Path[1].(($Path[1] == 'geolocation')?'/'.$_POST['map']:'').(($Path[1] !== 'time' && isset($Path[($Path[1] == 'geolocation')?4:3]))?'/'.$Path[($Path[1] == 'geolocation')?3:2]:'').(($Path[1] == 'time')?((isset($Path[2]) && in_array($Path[2], $Groups['time']))?'/'.$Path[2]:'').((isset($_POST['TimeView']))?'/'.implode('+', $_POST['TimeView']):''):'').'/'.$Date.(($Path[1] == 'time' && isset($_POST['TimeCompare']))?'/compare':'').EstatsCore::option('Path|suffix')));
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
	die(header('Location: '.EstatsTheme::get('datapath').EstatsCore::option('Path|prefix').$_POST['locale'].'/'.implode('/', array_slice($Path, 1)).EstatsCore::option('Path|suffix')));
}

if (isset($_GET['logout']) && !defined('ESTATS_INSTALL'))
{
	unset($_SESSION[EstatsCore::session()]['password']);

	EstatsCookie::delete('password');

	die(header('Location: '.$_SERVER['PHP_SELF']));
}

if ((!isset($_SESSION[EstatsCore::session()]['password']) || !$_SESSION[EstatsCore::session()]['password']) && EstatsCookie::exists('password') && !defined('ESTATS_INSTALL'))
{
	if (EstatsCookie::get('password') == md5(EstatsCore::option('Pass').EstatsCore::option('UniqueID')))
	{
		$_SESSION[EstatsCore::session()]['password'] = EstatsCore::option('Pass');
	}

	if (EstatsCookie::get('password') == md5(EstatsCore::option('AdminPass').EstatsCore::option('UniqueID')))
	{
		if (!isset($_SESSION[EstatsCore::session()]['password']) || $_SESSION[EstatsCore::session()]['password'] !== EstatsCore::option('AdminPass'))
		{
			EstatsCore::logEvent(EstatsCore::EVENT_ADMINISTRATORLOGGEDIN, 'IP: '.EstatsCore::IP());
		}

		$_SESSION[EstatsCore::session()]['password'] = EstatsCore::option('AdminPass');
	}

	if (isset($_SESSION[EstatsCore::session()]['password']))
	{
		EstatsCookie::set('password', md5($_SESSION[EstatsCore::session()]['password'].EstatsCore::option('UniqueID')), 1209600);
	}
}

if (isset($_POST['Password']) && !defined('ESTATS_INSTALL'))
{
	if (defined('ESTATS_DEMO'))
	{
		$_SESSION[EstatsCore::session()]['password'] = EstatsCore::option('AdminPass');
	}
	else
	{
		$_SESSION[EstatsCore::session()]['password'] = md5($_POST['Password']);
	}

	if (isset($_POST['Remember']))
	{
		EstatsCookie::set('password', md5($_SESSION[EstatsCore::session()]['password'].EstatsCore::option('UniqueID')), 1209600);
	}
}

define('ESTATS_USERLEVEL', (isset($_SESSION[EstatsCore::session()]['password'])?($_SESSION[EstatsCore::session()]['password'] == EstatsCore::option('AdminPass'))?2:(($_SESSION[EstatsCore::session()]['password'] == EstatsCore::option('Pass'))?1:0):0));

EstatsTheme::add('meta', '');
EstatsTheme::add('selectmap', '');
EstatsTheme::add('selecthour', '');
EstatsTheme::add('selectday', '');
EstatsTheme::add('selectmonth', '');
EstatsTheme::add('selectyear', '');

if (!isset($_SESSION[EstatsCore::session()]['theme']) || !EstatsTheme::exists($_SESSION[EstatsCore::session()]['theme']))
{
	$Browser = EstatsCore::detectBrowser($_SERVER['HTTP_USER_AGENT']);

	if ((in_array($Browser[0], array('Dillo', 'OffByOne', 'Links', 'ELinks', 'Lynx', 'W3M')) || ($Browser[0] == 'Internet Explorer' && ((double) $Browser[1]) < 6)) && is_file('share/themes/Simple/theme.tpl'))
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

$Locales = EstatsLocale::available();

if (!isset($_SESSION[EstatsCore::session()]['locale']))
{
	$Language = EstatsCore::language();

	if (strlen($Language) > 1)
	{
		$_SESSION[EstatsCore::session()]['locale'] = (array_key_exists($Language, EstatsLocale::available())?$Language:(array_key_exists(substr($Language, 0, 2), EstatsLocale::available())?substr($Language, 0, 2):'en'));
	}
}

if (!isset($Path[0]) || !EstatsLocale::exists($Path[0]))
{
	$Path[0] = (empty($_SESSION[EstatsCore::session()]['locale'])?'en':$_SESSION[EstatsCore::session()]['locale']);
}

if (!is_readable('locale/'.$Path[0].'/locale.ini'))
{
	$Path[0] = 'en';
}

EstatsLocale::set($Path[0], (defined('ESTATS_GETTEXT')?ESTATS_GETTEXT:NULL));

$_SESSION[EstatsCore::session()]['locale'] = $Path[0];

foreach ($Locales as $Key => $Value)
{
	$SelectLocale.= '<option value="'.$Key.'"'.(($Key == $Path[0])?' selected="selected"':'').'>'.$Value.'</option>
';
}

if (defined('ESTATS_DEMO'))
{
	EstatsGUI::notify(EstatsLocale::translate('This is a demo version of <em>eStats</em>.<br />
Therefore parts of its functionality are disabled.'), 'information');
}

if (ESTATS_VERSIONSTATUS !== 'stable')
{
	EstatsGUI::notify(sprintf(EstatsLocale::translate('This is a test version of <em>eStats</em> (status: <em>%s</em>).<br />
Its functionality could be incomplete, could work incorrect and be incompatible with newest versions!<br />
<strong style="text-decoration:underline;">Use at own risk!</strong>'), ESTATS_VERSIONSTATUS), 'warning');
}

if ((ESTATS_USERLEVEL == 2 || defined('ESTATS_INSTALL')) && ini_get('safe_mode'))
{
	EstatsGUI::notify(EstatsLocale::translate('<em>PHP safe mode</em> has been activated on this server!<br />
That could cause problems in case of automatic creation of files and directories.<br />
Solution is change of their owner or manual creation.'), 'warning');
}

if (!EstatsLocale::option('Status'))
{
	EstatsGUI::notify(sprintf(EstatsLocale::translate('This translation (%s) is not complete!'), $Locales[$Path[0]]), 'warning');
}

if (!isset($_SESSION[EstatsCore::session()]['viewTime']))
{
	$_SESSION[EstatsCore::session()]['viewTime'] = 0;
}

$Themes = EstatsTheme::available();

foreach ($Themes as $Key => $Value)
{
	$SelectTheme.= '<option value="'.urlencode($Key).'"'.(($Key == $_SESSION[EstatsCore::session()]['theme'])?' selected="selected"':'').'>'.htmlspecialchars($Value).'</option>
';
}

if (isset($_GET['checkversion']))
{
	if (((ESTATS_USERLEVEL == 2 && EstatsCore::option('CheckVersionTime')) || (defined('ESTATS_INSTALL') && !isset($_POST))) && (!isset($_SESSION[EstatsCore::session()]['LatestVersion']) || ($_SERVER['REQUEST_TIME'] - $_SESSION[EstatsCore::session()]['LatestVersion'][1]) > EstatsCore::option('CheckVersionTime')))
	{
		$Handle = fopen('http://estats.emdek.cba.pl/current.php?'.$_SERVER['SERVER_NAME'].'---'.$_SERVER['SCRIPT_NAME'].'---'.ESTATS_VERSIONSTRING, 'r');

		if ($Handle)
		{
			$_SESSION[EstatsCore::session()]['LatestVersion'] = array(fread($Handle, 6), $_SERVER['REQUEST_TIME']);

			fclose($Handle);

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
EstatsTheme::add('selectform', (count($Locales) > 1 || $SelectTheme));
EstatsTheme::add('antiflood', (!defined('ESTATS_INSTALL') && ($_SERVER['REQUEST_TIME'] - $_SESSION[EstatsCore::session()]['viewTime']) < 2 && !ESTATS_USERLEVEL));
EstatsTheme::add('dir', EstatsLocale::option('Dir'));
EstatsTheme::add('language', $Path[0]);
EstatsTheme::add('theme', $_SESSION[EstatsCore::session()]['theme']);
EstatsTheme::add('lang_gototop', EstatsLocale::translate('Go to top'));
EstatsTheme::add('lang_change', EstatsLocale::translate('Change'));
EstatsTheme::add('header', (defined('ESTATS_INSTALL')?'eStats <em>v'.ESTATS_VERSIONSTRING.'</em> :: '.EstatsLocale::translate('Installer'):preg_replace('#(\{tabindex\})#e', 'EstatsGUI::tabindex()', EstatsCore::option('Header'))));
EstatsTheme::add('selectlocale', ((count($Locales) > 1)?'<select name="locale" title="'.EstatsLocale::translate('Choose language').'" tabindex="'.EstatsGUI::tabindex().'">
'.$SelectLocale.'</select>
':''));
EstatsTheme::add('selecttheme', ((count($Themes) > 2)?'<select name="theme" title="'.EstatsLocale::translate('Choose theme').'" tabindex="'.EstatsGUI::tabindex().'">
'.$SelectTheme.'</select>
':''));
EstatsTheme::add('selectformindex', (EstatsTheme::get('selectform')?EstatsGUI::tabindex():''));
EstatsTheme::add('path', EstatsTheme::get('datapath').EstatsCore::option('Path|prefix').$Path[0].'/');
EstatsTheme::add('suffix', EstatsCore::option('Path|suffix'));

if (ESTATS_USERLEVEL == 2)
{
	if ($_GET)
	{
		$Array = array(
	'keyword' => 'Keywords',
	'referrer' => 'Referrers',
	'ignoredIP' => 'IgnoredIPs',
	'blockedIP' => 'BlockedIPs'
	);
		foreach ($Array as $Key => $Value)
		{
			if (isset($_GET[$Key]))
			{
				if (defined('ESTATS_DEMO'))
				{
					EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
				}
				else
				{
					$TmpArray = $$Value;

					if (in_array($_GET[$Key], $TmpArray))
					{
						unset($TmpArray[array_search($_GET[$Key], $TmpArray)]);
					}
					else
					{
						$TmpArray[] = $_GET[$Key];

						if ($Key == 'keyword' || $Key == 'referrer')
						{
							EstatsCore::driver()->deleteData($Key.'s', array(array(EstatsDriver::ELEMENT_OPERATION, array('name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, urldecode($_GET[$Key]))))));
						}
					}

					EstatsCore::setConfiguration(array($Value => implode('|', $TmpArray)));
				}
			}
		}
	}

	if (isset($_GET['statsenabled']) || isset($_POST['statsenabled']))
	{
		if (defined('ESTATS_DEMO'))
		{
			EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
		}
		else
		{
			EstatsCore::setConfiguration(array('StatsEnabled' => !EstatsCore::option('StatsEnabled')));
		}
	}

	if (isset($_GET['maintenance']) || isset($_POST['maintenance']))
	{
		if (defined('ESTATS_DEMO'))
		{
			EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
		}
		else
		{
			EstatsCore::setConfiguration(array('Maintenance' => !EstatsCore::option('Maintenance')));
		}
	}
}

if (EstatsCore::option('Maintenance') && ESTATS_USERLEVEL == 2)
{
	EstatsGUI::notify(EstatsLocale::translate('Maintenance mode is active!').'<br />
<a href="{selfpath}{separator}maintenance" tabindex="'.EstatsGUI::tabindex().'"><strong>'.EstatsLocale::translate('Disable maintenance mode').'</strong></a>.', 'warning');
}

if (!EstatsCore::option('StatsEnabled') && !defined('ESTATS_INSTALL'))
{
	EstatsGUI::notify(EstatsLocale::translate('Statistics are disabled.').((ESTATS_USERLEVEL == 2)?'<br />
<a href="{selfpath}{separator}statsenabled" tabindex="'.EstatsGUI::tabindex().'"><strong>'.EstatsLocale::translate('Enable statistics').'</strong></a>.':''), 'information');
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
	EstatsCache::enable(ESTATS_USERLEVEL < 2 || EstatsCore::option('Cache|enableforadministrator'));

	if (EstatsCore::option('Cache|clearinterval') && ($_SERVER['REQUEST_TIME'] - EstatsCore::option('LastClean')) > EstatsCore::option('Cache|clearinterval'))
	{
		EstatsCore::setConfiguration(array('LastClean' => $_SERVER['REQUEST_TIME']));

		EstatsCache::delete();
	}

	if (EstatsCore::option('LastCheck') !== date('Ymd') && (EstatsCore::option('Visits|oldvisitspolicy') == 'compact' || EstatsCore::option('Visits|oldvisitspolicy') == 'delete'))
	{
		EstatsCore::setConfiguration(array('LastCheck' => date('Ymd')));

		$Time = EstatsCore::driver()->selectData(array('details'), array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MAX, 'time'), 'maxtime')), NULL, 1, (self::option('Visits|amount') * self::option('Visits|maxpages')), array('maxtime' => FALSE), array('id'));
		$Time = $Time[0]['maxtime'];
		$Result = EstatsCore::driver()->selectData(array('details'), array('id', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MAX, 'time'), 'maxtime'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MIN, 'time'), 'mintime')), array(array(EstatsDriver::ELEMENT_OPERATION, array('maxtime', EstatsDriver::OPERATOR_LESSOREQUAL, $Time)), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('mintime', EstatsDriver::OPERATOR_LESS, ($_SERVER['REQUEST_TIME'] - max(self::option('VisitTime'), self::option('Visits|period')))))));

		if ($Result)
		{
			$UniqueIDs = array();

			for ($i = 0, $c = count($Result); $i < $c; ++$i)
			{
				$UniqueIDs[] = $Result[$i]['id'];
			}

			EstatsCore::driver()->deleteData('details', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_IN, $UniqueIDs))));

			if (EstatsCore::option('Visits|oldvisitspolicy') == 'delete')
			{
				EstatsCore::driver()->deleteData('visitors', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_IN, $UniqueIDs))));
			}
		}
	}

	EstatsTheme::add('loginindex', EstatsGUI::tabindex());
	EstatsTheme::add('startdate', date('d.m.Y', EstatsCore::option('CollectedFrom')));
	EstatsTheme::add('starttime', date('H:i:s', EstatsCore::option('CollectedFrom')));
	EstatsTheme::add('servername', $_SERVER['SERVER_NAME']);
	EstatsTheme::add('lang_statsfor', EstatsLocale::translate('Statistics for'));
	EstatsTheme::add('lang_logout', EstatsLocale::translate('Log out'));
	EstatsTheme::add('lang_login', EstatsLocale::translate('Log in'));
	EstatsTheme::add('lang_collectedfrom', EstatsLocale::translate('Data collected from'));

	$Titles = array(
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
	'oses' => EstatsLocale::translate('Operating systems'),
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

	$Var = $SubMenuVar = 0;
	$PermittedTools = $ToolInformation = array();

	if (EstatsCache::status('tools', 86400))
	{
		$Data = array();
		$Tools = glob('./plugins/tools/*/plugin.ini');

		for ($i = 0, $c = count($Tools); $i < $c; ++$i)
		{
			$Information = EstatsCore::loadData($Tools[$i], FALSE, FALSE);

			if (isset($Information['Level']) && isset($Information['Name']) && isset($Information['Name']['en']))
			{
				$ToolName = basename(dirname($Tools[$i]));
				$ToolInformation[(isset($Information['Position'])?$Information['Position']:1000).'-'.$ToolName] = array('name' => $ToolName, 'title' => $Information['Name'], 'level' => $Information['Level']);
			}
			else
			{
				continue;
			}
		}

		ksort($ToolInformation, SORT_NUMERIC);

		$ToolInformation = array_values($ToolInformation);

		for ($i = 0, $c = count($ToolInformation); $i < $c; ++$i)
		{
			$ToolInformation[$ToolInformation[$i]['name']] = array('title' => &$ToolInformation[$i]['title'], 'level' => &$ToolInformation[$i]['level']);

			unset($ToolInformation[$i]);
		}

		EstatsCache::save('tools', $ToolInformation);
	}
	else
	{
		$ToolInformation = EstatsCache::read('tools');
	}

	foreach ($ToolInformation as $Key => $Value)
	{
		if ($Value['level'] <= ESTATS_USERLEVEL)
		{
			$PermittedTools[] = $Key;
		}
	}

	if ($Path[1] == 'tools' && (!isset($Path[2]) || !is_file('plugins/tools/'.$Path[2].'/plugin.php') || !in_array($Path[2], $PermittedTools)))
	{
		$Path[2] = reset($PermittedTools);
	}

	switch ($Path[1])
	{
		case 'general':
		case 'technical':
			if (isset($Path[2]) && (in_array($Path[2], $Groups[$Path[1]]) || (($Path[2] == 'operatingsystem-versions' || $Path[2] == 'browsers-versions') && $Path[1] == 'technical')))
			{
				$Var = 3;
				$SubMenuVar = 2;
			}
			else
			{
				$Var = 2;
			}
		break;
		case 'geolocation':
			$FileName = 'countries-list';

			if (EstatsCache::status($FileName, 3600) || ESTATS_USERLEVEL == 2)
			{
				$AvailableCountries = array();
				$Data = EstatsCore::driver()->selectData(array('geoip'), array('country'), NULL, 0, 0, NULL, array('country'));

				for ($i = 0, $c = count($Data); $i < $c; ++$i)
				{
					$AvailableCountries[] = $Data[$i]['country'];
				}

				EstatsCache::save($FileName, $AvailableCountries);
			}
			else
			{
				$AvailableCountries = EstatsCache::read($FileName);
			}

			$Var = 3;
			$SubMenuVar = 2;

			if (isset($Path[2]) && ($Path[2] == 'world' || in_array($Path[2], $AvailableCountries)))
			{
				if (isset($Path[3]) && ((in_array($Path[3], array('cities', 'regions')) && in_array($Path[2], $AvailableCountries)) || (in_array($Path[3], array('cities', 'countries', 'continents')) && $Path[2] == 'world')))
				{
					$Var = 4;
					$SubMenuVar = 3;
				}
			}
			else if (empty($Path[2]) || !in_array($Path[2], array('countries', 'continents')))
			{
				if (isset($Path[2]) && $Path[2] == 'cities')
				{
					$Var = 4;
					$SubMenuVar = 3;
					$Path[2] = 'world';
					$Path[3] = 'cities';
				}
				else
				{
					$Path[2] = 'countries';
				}
			}
		break;
		case 'time':
			$Var = 3;
			$SubMenuVar = 2;

			if (isset($Path[2]) && in_array($Path[2], $Groups['time']))
			{
				$Var = 4;
			}
		break;
	}

	$ViewTypes = array();
	$AvailableViewTypes = array('views', 'unique', 'returns');

	if (EstatsCookie::exists('timeview') && in_array($Path[1], array('general', 'technical', 'time')))
	{
		$Path[$Var] = EstatsCookie::get('timeview');
	}

	if (isset($_POST['TimeView']))
	{
		$Path[$Var - 1] = implode('+', $_POST['TimeView']);

		EstatsCookie::set('timeview', $Path[$Var - 1]);
	}

	if (isset($Path[$Var - 1]) && $Path[$Var - 1])
	{
		$TmpTypes = explode('+', $Path[$Var - 1]);

		for ($i = 0, $c = count($TmpTypes); $i < $c; ++$i)
		{
			if (in_array($TmpTypes[$i], $AvailableViewTypes) && !in_array($TmpTypes[$i], $ViewTypes))
			{
				$ViewTypes[] = $TmpTypes[$i];
			}
		}
	}

	if (!$ViewTypes)
	{
		$ViewTypes = $AvailableViewTypes;
	}

	$TimeView = implode('+', $ViewTypes);

	if ($Path[1] == 'time')
	{
		$Path[$Var - 1] = $TimeView;
	}

	if (!isset($Path[$Var]) || !$Path[$Var])
	{
		if ($Path[1] == 'time')
		{
			$Date = array(0, 0, 0, 0);
		}
		else
		{
			if (EstatsCookie::exists('date'))
			{
				$Date = explode('-', EstatsCookie::get('date'));
			}
			else if (date('n') > 3)
			{
				$Date = array(date('Y'), 0, 0, 0);
			}
			else
			{
				$Date = array(0, 0, 0, 0);
			}
		}
	}
	else
	{
		$Date = explode('-', $Path[$Var]);
	}

	$Weights = array(
	'none' => 0,
	'yearly' => 1,
	'monthly' => 2,
	'daily' => 3,
	'hourly' => 4
	);

	if ($Path[1] == 'general' || $Path[1] == 'technical')
	{
		$Frequency = 4;

		if (isset($Path[2]) && isset($Groups[$Path[1]]) && in_array($Path[2], $Groups[$Path[1]]))
		{
			if ($Path[2] == 'operatingsystems' || $Path[2] == 'operatingsystem-versions')
			{
				$Frequency = $Weights[EstatsCore::option('CollectFrequency|oses')];
			}
			else if ($Path[2] == 'browser-versions')
			{
				$Frequency = $Weights[EstatsCore::option('CollectFrequency|browsers')];
			}
			else
			{
				$Frequency = $Weights[EstatsCore::option('CollectFrequency|'.$Path[2])];
			}
		}
		else
		{
			for ($i = 0, $c = count($Groups[$Path[1]]); $i < $c; ++$i)
			{
				$Key = $Groups[$Path[1]][$i];

				if ($Key == 'browser-versions')
				{
					$Key = 'browsers';
				}
				else if ($Key == 'operatingsystems' || $Key == 'operatingsystem-versions')
				{
					$Key = 'oses';
				}

				if ($Weights[EstatsCore::option('CollectFrequency|'.$Key)] < $Frequency)
				{
					$Frequency = $Weights[EstatsCore::option('CollectFrequency|'.$Key)];
				}
			}
		}
	}
	else if ($Path[1] == 'geolocation')
	{
		$Frequency = $Weights[EstatsCore::option('CollectFrequency|geolocation')];
	}
	else if ($Path[1] == 'time')
	{
		$Frequency = $Weights[(EstatsCore::option('CollectFrequency|time') == 'hourly')?'daily':EstatsCore::option('CollectFrequency|time')];
	}
	else
	{
		$Frequency = -1;
	}

	if (empty($Date[0]) || !in_array($Date[0], range(date('Y', EstatsCore::option('CollectedFrom')), date('Y'))))
	{
		$Date = array_fill(0, 4, 0);
	}
	else if (empty($Date[1]) || $Date[0].(($Date[1] < 10)?'0':'').$Date[1] < date('Ym', EstatsCore::option('CollectedFrom')) || $Date[0].(($Date[1] < 10)?'0':'').$Date[1] > date('Ym'))
	{
		$Date[1] = $Date[2] = 0;
	}
	else if (empty($Date[2]) || $Date[2] < 1 || $Date[2] > date('t', strtotime($Date[0].'-'.(($Date[1] < 10)?'0':'').$Date[1].'-01')))
	{
		$Date[2] = 0;
	}
	else if (empty($Date[3]) || $Date[3] < 0 || $Date[3] > 23)
	{
		$Date[3] = 0;
	}

	EstatsTheme::add('period', implode('-', $Date));

	if (in_array($Path[1], array('general', 'technical', 'time')))
	{
		$Path[$Var] = EstatsTheme::get('period');
	}

	$Menu = array('general', 'technical', 'geolocation', 'time', 'visits');

	if (count($ToolInformation) > 0)
	{
		$Menu[] = 'tools';
	}

	$AccessKeys = array();

	for ($i = 0, $c = count($Menu); $i < $c; ++$i)
	{
		if (($Menu[$i] == 'geolocation' && !EstatsGeolocation::isAvailable()) || ($Menu[$i] == 'time' && EstatsCore::option('CollectFrequency|time') !== 'disabled' && !EstatsCore::driver()->selectAmount('time')))
		{
			continue;
		}

		for ($j = 0, $l = strlen($Titles[$Menu[$i]]); $j < $l; ++$j)
		{
			$AccessKey = strtoupper($Titles[$Menu[$i]][$j]);

			if (!in_array($AccessKey, $AccessKeys) && $AccessKey >= 'A' && $AccessKey <= 'Z')
			{
				$AccessKeys[] = $AccessKey;

				break;
			}
		}

		EstatsTheme::add('menu-'.$Menu[$i], EstatsTheme::parse(EstatsTheme::get('menu-entry'), array(
	'link' => '{path}'.$Menu[$i].'{suffix}',
	'text' => $Titles[$Menu[$i]],
	'class' => (($Path[1] == $Menu[$i])?'active':''),
	'id' => $Menu[$i],
	'icon' => EstatsGUI::iconPath($Menu[$i], 'pages'),
	'entry' => $Menu[$i],
	'accesskey' => $AccessKey,
	'tabindex' => EstatsGUI::tabindex()
	)));
		EstatsTheme::add('submenu-'.$Menu[$i], '');
		EstatsTheme::add('submenu-'.$Menu[$i], FALSE);

		if (isset($Groups[$Menu[$i]]))
		{
			EstatsTheme::add('submenu-'.$Menu[$i], TRUE);

			for ($j = 0, $l = count($Groups[$Menu[$i]]); $j < $l; ++$j)
			{
				EstatsTheme::add('submenu-'.$Menu[$i].'_'.$Groups[$Menu[$i]][$j], FALSE);

				if ((isset($GroupAmount[$Groups[$Menu[$i]][$j]]) && !$GroupAmount[$Groups[$Menu[$i]][$j]]) || ($Menu[$i] == 'time' && EstatsCore::option('CollectFrequency|time') !== 'hourly' && in_array($Groups[$Menu[$i]][$j], array('24hours', 'hourspopularity'))))
				{
					continue;
				}

				EstatsTheme::append('submenu-'.$Menu[$i], EstatsTheme::parse(EstatsTheme::get((EstatsTheme::contains('submenu-entry')?'sub':'').'menu-entry'), array(
	'link' => '{path}'.$Menu[$i].'/'.$Groups[$Menu[$i]][$j].'{suffix}',
	'text' => $Titles[$Groups[$Menu[$i]][$j]],
	'class' => ((isset($Path[$SubMenuVar]) && $Path[$SubMenuVar] == $Groups[$Menu[$i]][$j])?'active':''),
	'id' => $Menu[$i].'_'.$Groups[$Menu[$i]][$j],
	'icon' => EstatsGUI::iconPath($Groups[$Menu[$i]][$j], 'pages'),
	'entry' => $Groups[$Menu[$i]][$j],
	'accesskey' => '',
	'tabindex' => EstatsGUI::tabindex()
	)));
			}
		}
		else if ($Menu[$i] == 'tools')
		{
			EstatsTheme::add('submenu-'.$Menu[$i], (count($PermittedTools) > 0));

			for ($j = 0, $l = count($PermittedTools); $j < $l; ++$j)
			{
				EstatsTheme::add('submenu-'.$Menu[$i].'_'.$PermittedTools[$j], FALSE);
				EstatsTheme::append('submenu-'.$Menu[$i], EstatsTheme::parse(EstatsTheme::get((EstatsTheme::contains('submenu-entry')?'sub':'').'menu-entry'), array(
	'link' => '{path}tools/'.$PermittedTools[$j].'{suffix}',
	'text' => $ToolInformation[$PermittedTools[$j]]['title'][isset($ToolInformation[$PermittedTools[$j]]['title'][$_SESSION[EstatsCore::session()]['locale']])?$_SESSION[EstatsCore::session()]['locale']:'en'],
	'class' => ((isset($Path[2]) && $Path[2] == $PermittedTools[$j])?'active':''),
	'id' => 'tools_'.$PermittedTools[$j],
	'icon' => EstatsGUI::iconPath($PermittedTools[$j], 'pages'),
	'entry' => $PermittedTools[$j],
	'accesskey' => '',
	'tabindex' => EstatsGUI::tabindex()
	)));
			}
		}

		EstatsTheme::append('menu', str_replace('{submenu}', EstatsTheme::get('submenu-'.$Menu[$i]), EstatsTheme::get('menu-'.$Menu[$i])));
	}

	switch ($Frequency)
	{
		case 4:
			for ($Hour = 0; $Hour < 24; ++$Hour)
			{
				$SelectHours.= '<option'.(((int) $Date[3] == $Hour && $Date[2])?' selected="selected"':'').'>'.$Hour.'</option>
';
			}

			EstatsTheme::add('selecthour', '<select name="hour" id="hour" title="'.EstatsLocale::translate('Hour').'" tabindex="'.EstatsGUI::tabindex().'">
<option'.(($Date[3] && $Date[2])?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$SelectHours.'</select>
');
		case 3:
			for ($Day = 1; $Day <= 31; ++$Day)
			{
				$SelectDays.= '<option'.(((int) $Date[2] == $Day)?' selected="selected"':'').'>'.$Day.'</option>
';
			}

			EstatsTheme::add('selectday', '<select name="day" id="day" title="'.EstatsLocale::translate('Day').'" tabindex="'.EstatsGUI::tabindex().'">
<option'.($Date[2]?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$SelectDays.'</select>
');
		case 2:
			for ($Month = 1; $Month <= 12; ++$Month)
			{
				$SelectMonths.= '<option value="'.$Month.'"'.(((int) $Date[1] == $Month)?' selected="selected"':'').'>'.ucfirst(strftime('%B', (mktime(0, 0, 0, $Month, 1)))).'</option>
';
			}

			EstatsTheme::add('selectmonth', '<select name="month" id="month" title="'.EstatsLocale::translate('Month').'" tabindex="'.EstatsGUI::tabindex().'">
<option'.($Date[1]?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$SelectMonths.'</select>
');
		case 1:
			for ($Year = date('Y', EstatsCore::option('CollectedFrom')); $Year <= date('Y'); ++$Year)
			{
				$SelectYears.= '<option value="'.$Year.'"'.(($Date[0] == $Year)?' selected="selected"':'').'>'.$Year.'</option>
';
			}

			EstatsTheme::add('selectyear', '<select name="year" id="year" title="'.EstatsLocale::translate('Year').'" tabindex="'.EstatsGUI::tabindex().'">
<option'.($Date[0]?'':' selected="selected"').' value="0">'.EstatsLocale::translate('All').'</option>
'.$SelectYears.'</select>
');
	}

	if ($Frequency >= 0)
	{
		EstatsTheme::add('dateform', (boolean) $Frequency);
		EstatsTheme::add('dateformindex', EstatsGUI::tabindex());
		EstatsTheme::add('lang_showdatafor', EstatsLocale::translate('Show data for'));
		EstatsTheme::add('lang_show', EstatsLocale::translate('Show'));

		if ($Frequency)
		{
			EstatsTheme::add('dateprevious', '<input type="submit" name="previous" value="'.EstatsLocale::translate('Previous').'" tabindex="'.EstatsGUI::tabindex().'" />
');
			EstatsTheme::add('datenext', '<input type="submit" name="next" value="'.EstatsLocale::translate('Next').'" tabindex="'.EstatsGUI::tabindex().'" />
');
		}
	}

	if (!EstatsCore::option('Pass'))
	{
		$Feeds = array(
	'daily' => EstatsLocale::translate('Daily summary'),
	'weekly' => EstatsLocale::translate('Weekly summary'),
	'monthly' => EstatsLocale::translate('Monthly summary')
	);

		foreach ($Feeds as $Key => $Value)
 		{
			EstatsTheme::append('meta', '<link rel="alternate" type="application/atom+xml" href="{path}feed/'.$Key.'{suffix}" title="'.$Value.'" />
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
	else if ((EstatsCore::option('Pass') && !ESTATS_USERLEVEL) || (ESTATS_USERLEVEL < 2 && ($Path[1] === 'login' || ($Path[1] === 'tools' && (!isset($Path[2]) || EstatsGUI::toolLevel($Path[2]) > ESTATS_USERLEVEL)))))
	{
		if (isset($_POST['Password']) && !ESTATS_USERLEVEL)
		{
			EstatsCore::logEvent((($Path[1] == 'tools')?EstatsCore::EVENT_FAILEDADMISNISTRATORLOGIN:EstatsCore::EVENT_FAILEDUSERLOGIN), 'IP: '.EstatsCore::IP());
			EstatsGUI::notify(EstatsLocale::translate('Wrong password!'), 'error');
		}

		EstatsTheme::load('login');
		EstatsTheme::link('login', 'page');
		EstatsTheme::add('title', EstatsLocale::translate('Login'));
		EstatsTheme::add('lang_pass', EstatsLocale::translate('Password'));
		EstatsTheme::add('lang_remember', EstatsLocale::translate('Remember password'));
		EstatsTheme::add('lang_loginto', EstatsLocale::translate('Log into'));
	}
	else if (($_SERVER['REQUEST_TIME'] - $_SESSION[EstatsCore::session()]['viewTime']) < 2 && !ESTATS_USERLEVEL && $Path[1] !== 'image')
	{
		EstatsTheme::append('meta', '<meta http-equiv="Refresh" content="2" />
');
		EstatsGUI::notify(EstatsLocale::translate('You can not refresh page so quickly!'), 'error');
		EstatsTheme::add('title', EstatsLocale::translate('Access denied'));
	}
	else
	{
		if (isset($_POST['Password']) && ($Path[1] == 'tools' || EstatsCore::option('Pass')))
		{
			EstatsCore::logEvent((($Path[1] == 'tools')?EstatsCore::EVENT_ADMINISTRATORLOGGEDIN:EstatsCore::EVENT_USERLOGGEDIN), 'IP: '.EstatsCore::IP());
		}

		if ($Path[1] == 'tools')
		{
			if (EstatsGUI::toolLevel($Path[2]) > ESTATS_USERLEVEL)
			{
				estats_error_message(EstatsLocale::translate('You are not allowed to use this tool!'), __FILE__, __LINE__, TRUE);
			}
		}
		else
		{
			if (!is_file('./pages/'.$Path[1].'.php'))
			{
				$Path[1] = 'general';
			}

			EstatsTheme::load($Path[1]);

			EstatsTheme::link((EstatsTheme::contains($Path[1])?$Path[1]:''), 'page');
		}

		if ($Path[1] !== 'image' && $Path[1] !== 'feed')
		{
			EstatsTheme::add('title', EstatsLocale::translate($Titles[$Path[1]]).(($Path[1] == 'tools')?' - '.$ToolInformation[$Path[2]]['title'][isset($ToolInformation[$Path[2]]['title'][$_SESSION[EstatsCore::session()]['locale']])?$_SESSION[EstatsCore::session()]['locale']:'en']:''));
		}

		$PagePath = (($Path[1] == 'tools')?'plugins/tools/'.$Path[2].'/plugin.php':'pages/'.$Path[1].'.php');

		if (!include ('./'.$PagePath))
		{
			estats_error_message($PagePath, __FILE__, __LINE__);
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

$Notifications = EstatsGUI::notifications();

if ($Notifications)
{
	for ($i = 0, $c = count($Notifications); $i < $c; ++$i)
	{
		$Message = explode('|', $Notifications[$i][0]);

		EstatsTheme::append('announcements', EstatsGUI::notificationWidget((is_numeric($Message[0])?EstatsLocale::translate($Logs[$Message[0]]).'.':$Message[0]).(isset($Message[1])?'<br />
<em>'.$Message[1].'</em>.':''), $Notifications[$i][1]));
	}
}
else
{
	EstatsTheme::add('announcements', '');
}

if (file_exists('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/images/'))
{
	$Images = glob('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/images/*.{png,jpg,gif}', GLOB_BRACE);

	for ($i = 0, $c = count($Images); $i < $c; ++$i)
	{
		EstatsTheme::append('preloader', '<img src="'.$Images[$i].'" alt="" />
');
	}
}

EstatsTheme::add('selfpath', EstatsTheme::get('datapath').EstatsCore::option('Path|prefix').implode('/', $Path).EstatsCore::option('Path|suffix'));
EstatsTheme::add('separator', htmlspecialchars(EstatsCore::option('Path|separator'), ENT_QUOTES, 'UTF-8', FALSE));
EstatsTheme::add('date', date('d.m.Y H:i:s T'));
EstatsTheme::add('announcements', (count($_SESSION['ERRORS']) > 0 || count(EstatsGUI::notifications()) > 0));
EstatsTheme::add('menu', EstatsTheme::contains('menu'));

if (is_file('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/theme.php'))
{
	include ('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/theme.php');
}

$Page = EstatsTheme::parse(EstatsTheme::parse(EstatsTheme::parse(EstatsTheme::get('index'), array('page' => EstatsTheme::get('page')))), array('pagegeneration' => sprintf(EstatsLocale::translate('Page generation time: %.3lf (s)'), (microtime(TRUE) - $Start))), TRUE);

if ($_SESSION['ERRORS'] && !defined('ESTATS_DEMO') && (ESTATS_USERLEVEL == 2 || defined('ESTATS_INSTALL')))
{
	$Debug = EstatsGUI::notificationWidget('<h4 id="debug_header" onclick="document.getElementById(\'debug\').style.display = ((document.getElementById(\'debug\').style.display == \'none\')?\'block\':\'none\')">'.EstatsLocale::translate('Debug').' ('.count($_SESSION['ERRORS']).')</h4>
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
	$Debug = '';
}

$Page = str_replace('{debug}', $Debug, EstatsTheme::parse($Page));

if (EstatsTheme::option('Type') !== 'xhtml')
{
	$Page = str_replace(' />', '>', $Page);
}

header(EstatsTheme::option('Header'));

if (!empty($Gzip) && function_exists('ob_gzhandler') && stristr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && !(ini_get('zlib.output_compression') == 'On' || ini_get('zlib.output_compression_level') > 0 || ini_get('output_handler') == 'ob_gzhandler'))
{
	header('Content-Encoding: gzip');
	ob_start('ob_gzhandler');
}

die(preg_replace('#(\{tabindex\})#e', 'EstatsGUI::tabindex()', $Page));
?>