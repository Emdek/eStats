<?php
/**
 * Installer GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 5.0.00
 */

if (!defined('ESTATS_INSTALL'))
{
	die(header('Location: ./'));
}

if (function_exists('set_time_limit') && !ini_get('safe_mode'))
{
	set_time_limit(60);
}

$error = FALSE;

if (isset($_POST['RootPassword']) && isset($_POST['RepeatPassword']))
{
	if ($_POST['RootPassword'] == $_POST['RepeatPassword'])
	{
		$_SESSION[EstatsCore::session()]['passlength'] = strlen($_POST['RootPassword']);
		$_SESSION[EstatsCore::session()]['password'] = md5($_POST['RootPassword']);

		if ($_SESSION[EstatsCore::session()]['passlength'] < 5)
		{
			EstatsGUI::notify(EstatsLocale::translate('Administrator password has less than five characters, you should choose longer password for greater security!'), 'warning');
		}
	}
	else
	{
		EstatsGUI::notify(EstatsLocale::translate('Given passwords are not the same!'), 'error');
	}
}

if (isset($_POST['Email']))
{
	if (!empty($_POST['Email']) && preg_match('#\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b#i', $_POST['Email']))
	{
		$_SESSION[EstatsCore::session()]['email'] = $_POST['Email'];
	}
	else
	{
		EstatsGUI::notify(EstatsLocale::translate('Invalid email address!'), 'error');
	}
}

if (isset($_POST['DatabaseDriver']) && !isset($_POST['Execute']) && isset($_SESSION[EstatsCore::session()]['password']) && isset($_SESSION[EstatsCore::session()]['email']))
{
	$step = 2;

	if (!include ('./plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php'))
	{
		estats_error_message('plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php', __FILE__, __LINE__);
	}

	$className = 'EstatsDriver'.ucfirst(strtolower($_POST['DatabaseDriver']));

	if (class_exists($className))
	{
		$driver = new $className;

		if (!$driver->isAvailable())
		{
			EstatsGUI::notify(EstatsLocale::translate('This database module is not supported on this server!<br>
Continuation of installation can cause unexpected results!'), 'error');
		}

		if (isset($_POST['TestConnection']))
		{
			if ($driver->connect($driver->connectionString($_POST), (isset($_POST['DatabaseUser'])?$_POST['DatabaseUser']:''), (isset($_POST['DatabasePassword'])?$_POST['DatabasePassword']:'')))
			{
				EstatsGUI::notify(EstatsLocale::translate('Connection established successfully.'), 'success');
			}
			else
			{
				EstatsGUI::notify(EstatsLocale::translate('An error occured during database connection attempt!'), 'error');
			}
		}

		EstatsTheme::add('page', '<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Driver information').'{heading-end}
</h3>
<p>
<a href="'.htmlspecialchars($driver->option('URL')).'" title="'.EstatsLocale::translate('Author').': '.htmlspecialchars($driver->option('Author')).'"><strong>'.htmlspecialchars($driver->option('Name')).' v'.htmlspecialchars($driver->option('Version')).' - '.htmlspecialchars($driver->option('Status')).'</strong></a> ('.date('d.m.Y H:i:s', $driver->option('Time')).').
</p>
');

		$information = EstatsCore::loadData('plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.ini', TRUE);

		$optionSelects = array(
	'PathMode' => array('GET', 'PATH_INFO', 'Rewrite'),
	);
		$optionsNames = array(
	'databasehost' => EstatsLocale::translate('Database host'),
	'databaseaddress' => EstatsLocale::translate('Database address'),
	'databaseport' => EstatsLocale::translate('Database port'),
	'databaseuser' => EstatsLocale::translate('Database user name'),
	'databasepassword' => EstatsLocale::translate('Database user password'),
	'databasename' => EstatsLocale::translate('Database name'),
	'databaseprefix' => EstatsLocale::translate('Database tables prefix'),
	'persistentconnection' => EstatsLocale::translate('Use persistent connection with database'),
	'datadirectory' => EstatsLocale::translate('Data directory'),
	'gzip' => EstatsLocale::translate('Use gzip compression'),
	'overwrite' => EstatsLocale::translate('Overwrite existing tables (if they exist with the same name already)'),
	'pathmode' => EstatsLocale::translate('Mode of passing data in the path'),
	'graphicsenabled' => EstatsLocale::translate('Enable generation of maps and graphical charts (<em>GD</em> extension)'),
	'logfile' => EstatsLocale::translate('Log events to text file'),
	);
		$options = array((isset($information['Defaults'])?$information['Defaults']:array()), array(
	'PersistentConnection' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'Overwrite' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'Gzip' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'GraphicsEnabled' => array(TRUE, EstatsGUI::FIELD_BOOLEAN),
	'LogFile' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'DataDirectory' => array('data/', EstatsGUI::FIELD_VALUE),
	'PathMode' => array('hourly', EstatsGUI::FIELD_SELECT)
	));

		if (count($options[0]))
		{
			EstatsTheme::append('page', '<h3>
{heading-start}'.EstatsLocale::translate('Database settings').'{heading-end}
</h3>
');
		}

		for ($i = 0; $i < 2; ++$i)
		{
			if ($i)
			{
				EstatsTheme::append('page', '<div id="install_advanced">
<h3>
{heading-start}'.EstatsLocale::translate('Advanced settings').'{heading-end}
</h3>
');
			}

			foreach ($options[$i] as $key => $value)
			{
				if (!is_array($value))
				{
					$value = array($value, EstatsGUI::FIELD_VALUE);
					$key = 'Database'.$key;
				}

				EstatsTheme::append('page', EstatsGUI::optionRowWidget((isset($optionsNames[strtolower($key)])?$optionsNames[strtolower($key)]:$key), '', $key, (isset($_POST[$key])?$_POST[$key]:$value[0]), $value[1], (($value[1] == EstatsGUI::FIELD_SELECT)?$optionSelects[$key]:NULL)));
			}
		}

		EstatsTheme::append('page', '</div>
<script type="text/javascript">
document.getElementById(\'install_advanced\').style.display = \'none\';
</script>
<div class="buttons">
<input type="submit" name="TestConnection" value="'.EstatsLocale::translate('Test database connection').'">
<input type="submit" name="Execute" value="'.EstatsLocale::translate('Continue').'">
<input type="reset" value="'.EstatsLocale::translate('Reset').'"><br>
<input type="button" value="'.EstatsLocale::translate('Advanced').'" onclick="document.getElementById(\'install_advanced\').style.display = ((document.getElementById(\'install_advanced\').style.display == \'none\')?\'block\':\'none\')">
<input type="hidden" name="DatabaseDriver" value="'.htmlspecialchars($_POST['DatabaseDriver']).'">
</div>
</form>
');
	}
	else
	{
		estats_error_message(sprintf(EstatsLocale::translate('Can not found class %s!'), $className), __FILE__, __LINE__);
	}
}
else if (isset($_POST['Execute']))
{
	$step = 3;

	if (!include ('./plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php'))
	{
		estats_error_message('plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php', __FILE__, __LINE__);
	}

	$className = 'EstatsDriver'.ucfirst(strtolower($_POST['DatabaseDriver']));

	if (class_exists($className))
	{
		$driver = new $className;
		$connection = $driver->connectionString($_POST);

		if (!$driver->isAvailable())
		{
			EstatsGUI::notify(EstatsLocale::translate('This database module is not supported on this server!<br>
Continuation of installation can cause unexpected results!'), 'error');
		}
		else if (!$driver->connect($connection, (isset($_POST['DatabaseUser'])?$_POST['DatabaseUser']:''), (isset($_POST['DatabasePassword'])?$_POST['DatabasePassword']:''), (isset($_POST['DatabasePrefix'])?$_POST['DatabasePrefix']:'')))
		{
			EstatsGUI::notify(EstatsLocale::translate('Can not connect to database!'), 'error');
		}
		else
		{
			$configuration = EstatsCore::loadData('share/data/configuration.ini');
			$configuration = array_merge($configuration['Core'], $configuration['GUI']);
			$configuration['CollectedFrom'] = $_SERVER['REQUEST_TIME'];
			$configuration['UniqueID'] = md5(uniqid(mt_rand(0, 1000000000)));
			$configuration['Version'] = ESTATS_VERSIONSTRING;
			$configuration['LastBackup'] = 0;
			$configuration['StatsEnabled'] = 1;
			$configuration['Maintenance'] = 0;
			$configuration['LastCheck'] = date('Ymd');
			$configuration['LastClean'] = $_SERVER['REQUEST_TIME'];
			$configuration['Header'] = str_replace('\r\n', "\r\n", $configuration['Header']['value']);

			if ($_POST['PathMode'] == 1)
			{
				$configuration['Path/mode'] = 1;
				$configuration['Path/prefix'] = 'index.php/';
				$configuration['Path/separator'] = '?';
			}
			else if ($_POST['PathMode'] == 2)
			{
				$configuration['Path/prefix'] = '';
				$configuration['Path/suffix'] = '/';
			}

			$security = uniqid(mt_rand(0, 999));
			$dataDirectory = $_POST['DataDirectory'];
			$existingTables = array();
			$schema = EstatsCore::loadData('share/data/database.ini');

			foreach ($schema as $table => $structure)
			{
				if ($driver->tableExists($table) && empty($_POST['Overwrite']))
				{
					$existingTables[] = (isset($_POST['DatabasePrefix'])?$_POST['DatabasePrefix']:'').$table;
				}
				else
				{
					if (!$driver->createTable($table, $structure))
					{
						$errors['DatabaseStructure'] = TRUE;
					}
				}
			}

			if ($existingTables)
			{
				EstatsGUI::notify(sprintf(EstatsLocale::translate('Database tables already exists: <em>%s</em>!'), implode(', ', $existingTables)), 'error');

				$errors['DatabaseStructure'] = TRUE;
			}
			else
			{
				if (!$driver->insertData('users', array('id' => 1, 'email' => $_SESSION[EstatsCore::session()]['email'], 'password' => $_SESSION[EstatsCore::session()]['password'], 'level' => 3)))
				{
					$errors['DatabaseStructure'] = TRUE;
				}

				if (!$driver->insertData('statistics', array('id' => 1, 'key' => md5(uniqid(mt_rand(0, 999))), 'user' => 1)))
				{
					$errors['DatabaseStructure'] = TRUE;
				}

				foreach ($configuration as $key => $value)
				{
					if (!$driver->insertData('configuration', array('statistics' => 1, 'key' => $key, 'value' => (is_array($value)?$value['value']:$value))))
					{
						$errors['DatabaseStructure'] = TRUE;
					}
				}
			}

			$configurationFile = '<?php
define(\'ESTATS_DATABASE_DRIVER\', \''.str_replace('\'', '\\\'', $_POST['DatabaseDriver']).'\');
define(\'ESTATS_DATABASE_CONNECTION\', \''.str_replace('\'', '\\\'', $connection).'\');
define(\'ESTATS_DATABASE_USER\', \''.(isset($_POST['DatabaseUser'])?str_replace('\'', '\\\'', $_POST['DatabaseUser']):'').'\');
define(\'ESTATS_DATABASE_PASSWORD\', \''.(isset($_POST['DatabasePassword'])?str_replace('\'', '\\\'', $_POST['DatabasePassword']):'').'\');
define(\'ESTATS_DATABASE_PREFIX\', \''.(isset($_POST['DatabasePrefix'])?str_replace('\'', '\\\'', $_POST['DatabasePrefix']):'').'\');
define(\'ESTATS_DATABASE_PERSISTENT\', '.(isset($_POST['PersistentConnection'])?'TRUE':'FALSE').');
define(\'ESTATS_SECURITY\', \''.str_replace('\'', '\\\'', $security).'\');
define(\'ESTATS_DATA\', \''.str_replace('\'', '\\\'', $_POST['DataDirectory']).'\');
define(\'ESTATS_GZIP\', '.(int) isset($_POST['Gzip']).');
define(\'eStats\', '.(int) $_SERVER['REQUEST_TIME'].');
define(\'eStatsVersion\', \''.number_format((double) ESTATS_VERSIONSTRING, 1, '.', '').'\');
?>';

			if (isset($errors['DatabaseStructure']))
			{
				EstatsGUI::notify(EstatsLocale::translate('Errors occured during database structure creation!'), 'error');
				EstatsGUI::notify(EstatsLocale::translate('Configuration file could not be saved!'), 'error');

				$errors['SaveConfigurationFile'] = TRUE;
			}
			else
			{
				EstatsGUI::notify(EstatsLocale::translate('Database structure created successfully.'), 'success');

				$driver->insertData('logs', array('time' => date('Y-m-d H:i:s'), 'log' => 0, 'info' => 'Version: '.ESTATS_VERSIONSTRING));

				if (isset($_POST['LogFile']))
				{
					file_put_contents($dataDirectory.'estats_'.$security.'.log', '
'.$_SERVER['REQUEST_TIME'].' ('.date('Y-m-d H:i:s').'): eStats was installed (Version '.ESTATS_VERSIONSTRING.')');
				}

				if (!file_put_contents('conf/config.php', $configurationFile))
				{
					EstatsGUI::notify(EstatsLocale::translate('An error occured during saving of configuration file!'), 'error');

					$errors['SaveConfigurationFile'] = TRUE;
				}
				else
				{
					EstatsGUI::notify(EstatsLocale::translate('Configuration file saved successfully.'), 'success');
				}
			}

			$randomIdentifier = md5(mt_rand());
			$session = md5('estats_'.substr($configuration['UniqueID'], 0, 10));
			$_SESSION[$session]['password'] = $_SESSION[EstatsCore::session()]['password'];
			$_SESSION[$session]['email'] = $_SESSION[EstatsCore::session()]['email'];

			EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('End of installation').'{heading-end}
</h3>
'.(isset($errors['SaveConfigurationFile'])?'
<h4>
'.EstatsLocale::translate('Save configuration file').'
</h4>
<p>
'.EstatsLocale::translate('Copy following code and save it as file <em>conf/config.php</em>').':
<textarea rows="12" cols="100" id="configfile">
'.htmlspecialchars($configurationFile).'</textarea>
</p>
<div class="buttons">
<input type="button" value="'.EstatsLocale::translate('Select').'" onclick="document.getElementById(\'configfile\').select(); document.getElementById(\'configfile\').focus()">
</div>
':'').'<h4>
'.EstatsLocale::translate('Fine tune configuration').'
</h4>
<p>
'.sprintf(EstatsLocale::translate('If no actions remain for execution go to <a href="%s"><strong>configuration</strong></a> page and perform after installation setup.'), '{datapath}index.php'.(($_POST['PathMode'] == 1)?'/':'?path=').$path[0].'/tools/configuration').'
</p>
<h4>
'.EstatsLocale::translate('Place activation code on your page').'
</h4>
<p>
'.EstatsLocale::translate('To enable collecting of statistics you must place activation code on each page.').'
</p>
<p>
<label>
<span>
<select id="canusephp" onchange="document.getElementById(\'canusephpyes\').style.display = ((this.options[selectedIndex].value == \'yes\')?\'block\':\'none\'); document.getElementById(\'canusephpno\').style.display = ((this.options[selectedIndex].value == \'no\')?\'block\':\'none\')">
<option value="yes" selected="selected">'.EstatsLocale::translate('Yes').'</option>
<option value="no">'.EstatsLocale::translate('No').'</option>
</select>
</span>
'.EstatsLocale::translate('Can you use <em>PHP</em> code on your page?').'
</label>
</p>
<div id="canusephpyes">
<p>
<label>
<span>
<input id="websitetitle" onkeyup="updateCode()" onkeydown="updateCode()">
<input type="button" value="'.EstatsLocale::translate('Reset').'" onclick="document.getElementById(\'websitetitle\').value = \'\'; updateCode()">
</span>
'.EstatsLocale::translate('Page title (optional, should be unique for each page, if set)').':
</label>
</p>
<p>
<label>
<strong>'.EstatsLocale::translate('PHP code').'</strong>:
<textarea cols="150" rows="5" id="phpcode">&lt;?php
define(\'ESTATS_COUNT\', 1);
@include(\''.htmlspecialchars(dirname(__FILE__)).'/stats.php\');
?&gt;</textarea>
</label>
</p>
<div class="buttons">
<input type="button" value="'.EstatsLocale::translate('Select').'" onclick="document.getElementById(\'phpcode\').select(); document.getElementById(\'phpcode\').focus()">
</div>
<p>
'.EstatsLocale::translate('Place the <em>PHP</em> code somewhere at start of code of your page. Script will collect the most part of data, technical information (such as screen resolution) will be handled by <em>JavaScript</em> code.').'
</p>
<p>
<label>
<strong>'.EstatsLocale::translate('JavaScript code').'</strong>:
<textarea cols="150" rows="5" id="javascriptcode">&lt;a href="http://estats.emdek.pl/"&gt;
&lt;img src="{datapath}antipixel.php?count=0" alt="eStats" id="estats_'.$randomIdentifier.'" title="eStats" /&gt;
&lt;/a&gt;
&lt;script type="text/javascript"&gt;
var eCount = 0;
var ePath = \'{datapath}\';
var eTitle = \'\';
var eAddress = \'\';
var eAntipixel = \'\';
var eImage = \'estats_'.$randomIdentifier.'\';
&lt;/script&gt;
&lt;script type="text/javascript" src="{datapath}stats.js"&gt;&lt;/script&gt;</textarea>
</label>
</p>
<div class="buttons">
<input type="button" value="'.EstatsLocale::translate('Select').'" onclick="document.getElementById(\'javascriptcode\').select(); document.getElementById(\'javascriptcode\').focus()">
</div>
</div>
<div id="canusephpno">
<p>
<label for="javascriptcodenophp"><strong>'.EstatsLocale::translate('JavaScript code').'</strong></label>:
<textarea cols="150" rows="5" id="javascriptcodenophp">&lt;noscript&gt;
&lt;div&gt;
&lt;a href="http://estats.emdek.pl/"&gt;
&lt;img src="{datapath}antipixel.php?count=1" alt="eStats" title="eStats" /&gt;
&lt;/a&gt;
&lt;/div&gt;
&lt;/noscript&gt;
&lt;script type="text/javascript"&gt;
var eCount = 1;
var ePath = \'{datapath}\';
var eTitle = \'\';
var eAddress = \'\';
var eAntipixel = \'\';
&lt;/script&gt;
&lt;script type="text/javascript" src="{datapath}stats.js"&gt;&lt;/script&gt;</textarea>
</p>
<div class="buttons">
<input type="button" value="'.EstatsLocale::translate('Select').'" onclick="document.getElementById(\'javascriptcodenophp\').select(); document.getElementById(\'javascriptcodenophp\').focus()">
</div>
</div>
<script type="text/javascript">
function updateCode()
{
	document.getElementById(\'phpcode\').value = \'&lt;?php\ndefine(\\\'ESTATS_COUNT\\\', 1);\n\' + (document.getElementById(\'websitetitle\').value ? \'define(\\\'ESTATS_TITLE\\\', \\\'\' + document.getElementById(\'websitetitle\').value + \'\\\');\n\' : \'\') + \'@include(\\\''.htmlspecialchars(dirname(__FILE__)).'/stats.php\\\');\n?&gt;\';
}

document.getElementById(\'canusephpno\').style.display = \'none\';
</script>
<p>
'.EstatsLocale::translate('Place the <em>JavaScript</em> code somewhere between <em>BODY</em> tags on your page. There will be shown an image that will collect the data.').'
</p>
');
		}
	}
	else
	{
		estats_error_message(sprintf(EstatsLocale::translate('Can not found class %s!'), $className), __FILE__, __LINE__);
	}
}
else
{
	$step = 1;

	if (!version_compare(PHP_VERSION, '5.2.0', '>='))
	{
		EstatsGUI::notify(EstatsLocale::translate('Too old PHP version!'), 'error');
		$error = TRUE;
	}

	$locations = array(
	'data/' => TRUE,
	'data/backups/' => TRUE,
	'data/cache/' => TRUE,
	'data/tmp/' => TRUE,
	'conf/config.php' => FALSE
	);

	foreach ($locations as $key => $value)
	{
		$locations[$key] = (file_exists($key)?(((substr(sprintf('%o', fileperms($key)), -1)) >= ($value?7:6))):0);

		if (!$locations[$key])
		{
			$error = TRUE;
		}
	 }

	$requirements = array(
	'gd' => array(function_exists('gd_info'), EstatsLocale::translate('Graphics generation support')),
	'geoip' => array(function_exists('geoip_record_by_name'), EstatsLocale::translate('Geolocation support')),
	'gettext' => array(function_exists('gettext'), EstatsLocale::translate('Reliable translations support')),
	'mbstring' => array(function_exists('mb_convert_encoding'), EstatsLocale::translate('Reliable Unicode support')),
	'bzip' => array(function_exists('bzcompress'), EstatsLocale::translate('Bzip compression support')),
	'zlib' => array(function_exists('gzcompress'), EstatsLocale::translate('Gzip compression support')),
	'zip' => array(class_exists('ZipArchive'), EstatsLocale::translate('ZIP compression support')),
	);

	$databaseDrivers = glob('./plugins/drivers/*');
	$driverSelect = '';

	for ($i = 0, $c = count($databaseDrivers); $i < $c; ++$i)
	{
		if (file_exists($databaseDrivers[$i].'/plugin.php') && include ($databaseDrivers[$i].'/plugin.php'))
		{
			$driverName = basename($databaseDrivers[$i]);
			$className = 'EstatsDriver'.ucfirst(strtolower($driverName));

			if (class_exists($className))
			{
				$object = new $className;

				if ($object->isAvailable())
				{
					$driverSelect.= '<option>'.htmlspecialchars($driverName).'</option>
';
				}
			}
		}
	}

	if (!$driverSelect)
	{
		EstatsGUI::notify(EstatsLocale::translate('Lack of support for any of available modules!'), 'error');

		$error = TRUE;
	}

	if ($error)
	{
		 EstatsGUI::notify(EstatsLocale::translate('Could not continue!'), 'error');
	}

	EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('Introduction').'{heading-end}
</h3>
<p>
'.EstatsLocale::translate('<strong>Welcome in eStats installer!</strong><br><br>
This script allows to install eStats easy and fast on your server.<br>
You were redirected here because file <em>conf/config.php</em> does not exists or is incorrect / incompatible with this eStats version.<br>
Select database type and admin password.<br>
After you fill all fields click <em>Continue</em> button to configure the script.').'
</p>
<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
'.($driverSelect?'<p>
<label>
<span>
<select name="DatabaseDriver">
'.$driverSelect.'</select>
</span>
'.EstatsLocale::translate('Database module').':
</label>
</p>
':'').'<p>
<label>
<span>
<input name="Email">
</span>
'.EstatsLocale::translate('Administrator email').':
</label>
</p>
<p>
<label>
<span>
<input type="password" name="RootPassword">
</span>
'.EstatsLocale::translate('Administrator password').':
</label>
</p>
<p>
<label>
<span>
<input type="password" name="RepeatPassword">
</span>
'.EstatsLocale::translate('Repeat password').':
</label>
</p>
<h3>
{heading-start}'.EstatsLocale::translate('Permissions test').'{heading-end}
</h3>
');

	foreach ($locations as $key => $value)
	{
		EstatsTheme::append('page', '<p>
<em>'.$key.'</em> - <strong class="'.($value?'green':'red').'">'.($value?EstatsLocale::translate('OK'):(file_exists($key)?EstatsLocale::translate('Not writeable'):EstatsLocale::translate('Not exists'))).'</strong>
</p>
');
	}

	EstatsTheme::append('page', '<h3>
{heading-start}'.EstatsLocale::translate('Requirements').'{heading-end}
</h3>
');

	foreach ($requirements as $key => $value)
	{
		EstatsTheme::append('page', '<p>
'.$value[1].' (<em>'.$key.'</em>) - <strong class="'.($value[0]?'green':'yellow').'">'.($value[0]?EstatsLocale::translate('Available'):EstatsLocale::translate('Not available')).'</strong>
</p>
');
	}

	EstatsTheme::append('page', '<div class="buttons">
<input type="submit" value="'.($error?EstatsLocale::translate('Continue anyway'):EstatsLocale::translate('Continue')).'"'.($error?' onclick="alert(\''.EstatsLocale::translate('Continuation despite errors threatens unexpected results!\nYou continue on your own risk!').'\')"':'').'>
</div>
</form>
');
}

EstatsTheme::add('title', EstatsLocale::translate('Installer').' - '.sprintf(EstatsLocale::translate('Step %d of 3'), $step));
?>