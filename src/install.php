<?php
/**
 * Installer GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('ESTATS_INSTALL'))
{
	die(header('Location: ./'));
}

if (function_exists('set_time_limit') && !ini_get('safe_mode'))
{
	set_time_limit(60);
}

$Error = FALSE;

if (isset($_POST['AdminPass']) && isset($_POST['RepeatPassword']))
{
	if ($_POST['AdminPass'] == $_POST['RepeatPassword'])
	{
		$_SESSION[EstatsCore::session()]['passlength'] = strlen($_POST['AdminPass']);
		$_SESSION[EstatsCore::session()]['password'] = md5($_POST['AdminPass']);
	}
	else
	{
		EstatsGUI::notify(EstatsLocale::translate('Given passwords are not the same!'), 'error');
	}
}

if (isset($_POST['DatabaseDriver']) && !isset($_POST['Execute']) && isset($_SESSION[EstatsCore::session()]['password']))
{
	$Step = 2;

	if (!include ('./plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php'))
	{
		estats_error_message('plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php', __FILE__, __LINE__);
	}

	$ClassName = 'EstatsDriver'.ucfirst(strtolower($_POST['DatabaseDriver']));

	if (class_exists($ClassName))
	{
		$Driver = new $ClassName;

		if (!$Driver->isAvailable())
		{
			EstatsGUI::notify(EstatsLocale::translate('This database module is not supported on this server!<br />
Continuation of installation can cause unexpected results!'), 'error');
		}

		if ($_SESSION[EstatsCore::session()]['passlength'] < 5)
		{
			EstatsGUI::notify(EstatsLocale::translate('Administrator password has less than five characters, you should choose longer password for greater security!'), 'warning');
		}

		if (isset($_POST['TestConnection']))
		{
			if ($Driver->connect($Driver->connectionString($_POST), (isset($_POST['DatabaseUser'])?$_POST['DatabaseUser']:''), (isset($_POST['DatabasePassword'])?$_POST['DatabasePassword']:'')))
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
<a href="'.htmlspecialchars($Driver->option('URL')).'" tabindex="'.EstatsGUI::tabindex().'" title="'.EstatsLocale::translate('Author').': '.htmlspecialchars($Driver->option('Author')).'"><strong>'.htmlspecialchars($Driver->option('Name')).' v'.htmlspecialchars($Driver->option('Version')).' - '.htmlspecialchars($Driver->option('Status')).'</strong></a> ('.date('d.m.Y H:i:s', $Driver->option('Time')).').
</p>
');

		$Information = EstatsCore::loadData('plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.ini', TRUE);

		$OptionSelects = array(
	'PathMode' => array('GET', 'PATH_INFO', 'Rewrite'),
	'TimeCollectFrequency' => array('yearly', 'monthly', 'daily', 'hourly', 'none'),
	'DataCollectFrequency' => array('yearly', 'monthly', 'daily', 'hourly', 'none')
	);
		$OptionsNames = array(
	'databasehost' => EstatsLocale::translate('Database host'),
	'databaseaddress' => EstatsLocale::translate('Database address'),
	'databaseport' => EstatsLocale::translate('Database port'),
	'databaseuser' => EstatsLocale::translate('Database user name'),
	'databasepassword' => EstatsLocale::translate('Database user password'),
	'databasename' => EstatsLocale::translate('Database name'),
	'databaseprefix' => EstatsLocale::translate('Database tables prefix'),
	'persistentconnection' => EstatsLocale::translate('Use persistent connection with database'),
	'datadir' => EstatsLocale::translate('Data directory'),
	'gzip' => EstatsLocale::translate('Use gzip compression'),
	'overwrite' => EstatsLocale::translate('Overwrite existing tables (if they exist with the same name already)'),
	'timecollectfrequency' => EstatsLocale::translate('Frequency of data collecting for time statistics'),
	'datacollectfrequency' => EstatsLocale::translate('Frequency of data collecting for other data'),
	'pathmode' => EstatsLocale::translate('Mode of passing data in the path'),
	'graphicsenabled' => EstatsLocale::translate('Enable generation of maps and graphical charts (<em>GD</em> extension)'),
	'logfile' => EstatsLocale::translate('Log events to text file'),
	);
		$Options = array((isset($Information['Defaults'])?$Information['Defaults']:array()), array(
	'PersistentConnection' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'Overwrite' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'Gzip' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'GraphicsEnabled' => array(TRUE, EstatsGUI::FIELD_BOOLEAN),
	'LogFile' => array(FALSE, EstatsGUI::FIELD_BOOLEAN),
	'DataDir' => array('data/', EstatsGUI::FIELD_VALUE),
	'TimeCollectFrequency' => array('hourly', EstatsGUI::FIELD_SELECT),
	'DataCollectFrequency' => array('monthly', EstatsGUI::FIELD_SELECT),
	'PathMode' => array('hourly', EstatsGUI::FIELD_SELECT)
	));

		if (count($Options[0]))
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

			foreach ($Options[$i] as $Key => $Value)
			{
				if (!is_array($Value))
				{
					$Value = array($Value, EstatsGUI::FIELD_VALUE);
					$Key = 'Database'.$Key;
				}

				EstatsTheme::append('page', EstatsGUI::optionRowWidget((isset($OptionsNames[strtolower($Key)])?$OptionsNames[strtolower($Key)]:$Key), '', $Key, (isset($_POST[$Key])?$_POST[$Key]:$Value[0]), $Value[1], (($Value[1] == EstatsGUI::FIELD_SELECT)?$OptionSelects[$Key]:NULL)));
			}
		}

		EstatsTheme::append('page', '</div>
<script type="text/javascript">
document.getElementById(\'install_advanced\').style.display = \'none\';
</script>
<div class="buttons">
<input type="submit" name="TestConnection" value="'.EstatsLocale::translate('Test database connection').'" tabindex="'.EstatsGUI::tabindex().'" />
<input type="submit" name="Execute" value="'.EstatsLocale::translate('Continue').'" tabindex="'.EstatsGUI::tabindex().'" />
<input type="reset" value="'.EstatsLocale::translate('Reset').'" tabindex="'.EstatsGUI::tabindex().'" /><br />
<input type="button" value="'.EstatsLocale::translate('Advanced').'" tabindex="'.EstatsGUI::tabindex().'" onclick="document.getElementById(\'install_advanced\').style.display = ((document.getElementById(\'install_advanced\').style.display == \'none\')?\'block\':\'none\')" />
<input type="hidden" name="DatabaseDriver" value="'.htmlspecialchars($_POST['DatabaseDriver']).'" />
</div>
</form>
');
	}
	else
	{
		estats_error_message(sprintf(EstatsLocale::translate('Can not found class %s!'), $ClassName), __FILE__, __LINE__);
	}
}
else if (isset($_POST['Execute']))
{
	$Step = 3;

	if (!include ('./plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php'))
	{
		estats_error_message('plugins/drivers/'.$_POST['DatabaseDriver'].'/plugin.php', __FILE__, __LINE__);
	}

	$ClassName = 'EstatsDriver'.ucfirst(strtolower($_POST['DatabaseDriver']));

	if (class_exists($ClassName))
	{
		$Driver = new $ClassName;
		$Connection = $Driver->connectionString($_POST);

		if (!$Driver->isAvailable())
		{
			EstatsGUI::notify(EstatsLocale::translate('This database module is not supported on this server!<br />
Continuation of installation can cause unexpected results!'), 'error');
		}
		else if (!$Driver->connect($Connection, (isset($_POST['DatabaseUser'])?$_POST['DatabaseUser']:''), (isset($_POST['DatabasePassword'])?$_POST['DatabasePassword']:''), (isset($_POST['DatabasePrefix'])?$_POST['DatabasePrefix']:'')))
		{
			EstatsGUI::notify(EstatsLocale::translate('Can not connect to database!'), 'error');
		}
		else
		{
			$Configuration = EstatsCore::loadData('share/data/configuration.ini');
			$Configuration['Core']['CollectedFrom'] = $_SERVER['REQUEST_TIME'];
			$Configuration['Core']['UniqueID'] = md5(uniqid(mt_rand(0, 1000000000)));
			$Configuration['Core']['Version'] = ESTATS_VERSIONSTRING;
			$Configuration['Core']['CollectFrequency']['time'] = $_POST['TimeCollectFrequency'];
			$Configuration['Core']['LastBackup'] = 0;
			$Configuration['Core']['StatsEnabled'] = 1;
			$Configuration['GUI']['EditMode'] = 1;
			$Configuration['GUI']['Maintenance'] = 0;
			$Configuration['GUI']['LastCheck'] = date('Ymd');
			$Configuration['GUI']['LastClean'] = $_SERVER['REQUEST_TIME'];
			$Configuration['GUI']['Header'] = str_replace('\r\n', "\r\n", $Configuration['GUI']['Header']['value']);
			$Configuration['GUI']['AdminPass'] = $_SESSION[EstatsCore::session()]['password'];
			$Configuration['GUI']['Pass'] = '';

			$_SESSION[md5('estats_'.substr($Configuration['Core']['UniqueID'], 0, 10))]['password'] = $_SESSION[EstatsCore::session()]['password'];

			foreach ($Configuration['Core'] as $Key => $Value)
			{
				if (strstr($Key, 'CollectFrequency'))
				{
					$Configuration['Core'][$Key] = $_POST['DataCollectFrequency'];
				}
			}

			if ($_POST['PathMode'] == 1)
			{
				$Configuration['GUI']['Path|mode'] = 1;
				$Configuration['GUI']['Path|prefix'] = 'index.php/';
				$Configuration['GUI']['Path|separator'] = '?';
			}
			else if ($_POST['PathMode'] == 2)
			{
				$Configuration['GUI']['Path|prefix'] = '';
				$Configuration['GUI']['Path|suffix'] = '/';
			}

			$Security = uniqid(mt_rand(0, 999));
			$DataDir = $_POST['DataDir'];
			$Schema = EstatsCore::loadData('share/data/database.ini');
			$ExistingTables = array();

			foreach ($Schema as $Table => $Structure)
			{
				if ($Driver->tableExists($Table) && empty($_POST['Overwrite']))
				{
					$ExistingTables[] = (isset($_POST['DatabasePrefix'])?$_POST['DatabasePrefix']:'').$Table;
				}
				else
				{
					if (!$Driver->createTable($Table, $Structure))
					{
						$Errors['DatabaseStructure'] = TRUE;
					}
				}
			}

			if ($ExistingTables)
			{
				EstatsGUI::notify(sprintf(EstatsLocale::translate('Database tables already exists: <em>%s</em>!'), implode(', ', $ExistingTables)), 'error');

				$Errors['DatabaseStructure'] = TRUE;
			}
			else
			{
				foreach ($Configuration as $Group => $Value)
				{
					$Mode = (int) ($Group != 'Core');

					foreach ($Value as $Option => $Value)
					{
						if (!$Driver->insertData('configuration', array('name' => str_replace('/', '|', $Option), 'value' => (is_array($Value)?$Value['value']:$Value), 'mode' => $Mode)))
						{
							$Errors['DatabaseStructure'] = TRUE;
						}
					}
				}
			}

			$ConfigurationFile = '<?php
$DBType = \''.str_replace('\'', '\\\'', $_POST['DatabaseDriver']).'\';
$DBConnection = \''.str_replace('\'', '\\\'', $Connection).'\';
$DBUser = \''.(isset($_POST['DatabaseUser'])?str_replace('\'', '\\\'', $_POST['DatabaseUser']):'').'\';
$DBPass = \''.(isset($_POST['DatabasePassword'])?str_replace('\'', '\\\'', $_POST['DatabasePassword']):'').'\';
$DBPrefix = \''.(isset($_POST['DatabasePrefix'])?str_replace('\'', '\\\'', $_POST['DatabasePrefix']):'').'\';
$PConnect = '.(isset($_POST['PersistentConnection'])?'TRUE':'FALSE').';
$DBID = \''.str_replace('\'', '\\\'', $Security).'\';
$DataDir = \''.str_replace('\'', '\\\'', $_POST['DataDir']).'\';
$Gzip = '.(int) isset($_POST['Gzip']).';
define(\'eStats\', '.(int) $_SERVER['REQUEST_TIME'].');
define(\'eStatsVersion\', \''.number_format((double) ESTATS_VERSIONSTRING, 1, '.', '').'\');
?>';

			if (isset($Errors['DatabaseStructure']))
			{
				EstatsGUI::notify(EstatsLocale::translate('Errors occured during database structure creation!'), 'error');
				EstatsGUI::notify(EstatsLocale::translate('Configuration file could not be saved!'), 'error');

				$Errors['SaveConfigurationFile'] = TRUE;
			}
			else
			{
				EstatsGUI::notify(EstatsLocale::translate('Database structure created successfully.'), 'success');

				$Driver->insertData('logs', array('time' => date('Y-m-d H:i:s'), 'log' => 0, 'info' => 'Version: '.ESTATS_VERSIONSTRING));

				if (isset($_POST['LogFile']))
				{
					file_put_contents($DataDir.'estats_'.$Security.'.log', '
'.$_SERVER['REQUEST_TIME'].' ('.date('Y-m-d H:i:s').'): eStats was installed (Version '.ESTATS_VERSIONSTRING.')');
				}

				if (!file_put_contents('conf/config.php', $ConfigurationFile))
				{
					EstatsGUI::notify(EstatsLocale::translate('An error occured during saving of configuration file!'), 'error');

					$Errors['SaveConfigurationFile'] = TRUE;
				}
				else
				{
					EstatsGUI::notify(EstatsLocale::translate('Configuration file saved successfully.'), 'success');
				}
			}

			EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('End of installation').'{heading-end}
</h3>
'.(isset($Errors['SaveConfigurationFile'])?'
<h4>
'.EstatsLocale::translate('Save configuration file').'
</h4>
<p>
'.EstatsLocale::translate('Copy following code and save it as file <em>conf/config.php</em>').':
<textarea rows="12" cols="100" id="configfile">
'.htmlspecialchars($ConfigurationFile).'</textarea>
</p>
<div class="buttons">
<input type="button" value="'.EstatsLocale::translate('Select').'" tabindex="'.EstatsGUI::tabindex().'" onclick="document.getElementById(\'configfile\').select(); document.getElementById(\'configfile\').focus()" />
</div>
':'').'<h4>
'.EstatsLocale::translate('Fine tune configuration').'
</h4>
<p>
'.sprintf(EstatsLocale::translate('If no actions remain for execution go to <a href="%s" tabindex="%d"><strong>configuration</strong></a> page and perform after installation setup.'), '{datapath}index.php'.(($_POST['PathMode'] == 1)?'/':'?path=').$Path[0].'/tools/configuration',EstatsGUI::tabindex()).'
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
<select id="canusephp" tabindex="'.EstatsGUI::tabindex().'" onchange="document.getElementById(\'canusephpyes\').style.display = ((this.options[selectedIndex].value == \'yes\')?\'block\':\'none\'); document.getElementById(\'canusephpno\').style.display = ((this.options[selectedIndex].value == \'no\')?\'block\':\'none\')">
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
<input id="websitetitle" tabindex="'.EstatsGUI::tabindex().'" onkeyup="updateCode()" onkeydown="updateCode()" />
<input type="button" value="'.EstatsLocale::translate('Reset').'" tabindex="'.EstatsGUI::tabindex().'" onclick="document.getElementById(\'websitetitle\').value = \'\'; updateCode()" />
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
<input type="button" value="'.EstatsLocale::translate('Select').'" tabindex="'.EstatsGUI::tabindex().'" onclick="document.getElementById(\'phpcode\').select(); document.getElementById(\'phpcode\').focus()" />
</div>
<p>
'.EstatsLocale::translate('Place the <em>PHP</em> code somewhere at start of code of your page. Script will collect the most part of data, technical information (such as screen resolution) will be handled by <em>JavaScript</em> code.').'
</p>
<p>
<label>
<strong>'.EstatsLocale::translate('JavaScript code').'</strong>:
<textarea cols="150" rows="5" id="javascriptcode">&lt;noscript&gt;
&lt;div&gt;
&lt;a href="http://estats.emdek.cba.pl/"&gt;
&lt;img src="{datapath}antipixel.php?count=0" alt="eStats" title="eStats" /&gt;
&lt;/a&gt;
&lt;/div&gt;
&lt;/noscript&gt;
&lt;script type="text/javascript"&gt;
var eCount = 0;
var ePath = \'{datapath}\';
var eTitle = \'\';
var eAddress = \'\';
var eAntipixel = \'\';
&lt;/script&gt;
&lt;script type="text/javascript" src="{datapath}stats.js"&gt;&lt;/script&gt;</textarea>
</label>
</p>
<div class="buttons">
<input type="button" value="'.EstatsLocale::translate('Select').'" tabindex="'.EstatsGUI::tabindex().'" onclick="document.getElementById(\'javascriptcode\').select(); document.getElementById(\'javascriptcode\').focus()" />
</div>
</div>
<div id="canusephpno">
<p>
<label for="javascriptcodenophp"><strong>'.EstatsLocale::translate('JavaScript code').'</strong></label>:
<textarea cols="150" rows="5" id="javascriptcodenophp">&lt;noscript&gt;
&lt;div&gt;
&lt;a href="http://estats.emdek.cba.pl/"&gt;
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
<input type="button" value="'.EstatsLocale::translate('Select').'" tabindex="'.EstatsGUI::tabindex().'" onclick="document.getElementById(\'javascriptcodenophp\').select(); document.getElementById(\'javascriptcodenophp\').focus()" />
</div>
</div>
<script type="text/javascript">
function updateCode()
{
	document.getElementById(\'phpcode\').value = \'&lt;?php\ndefine(\\\'ESTATS_COUNT\\\', 1);\n\' + (document.getElementById(\'websitetitle\').value?\'define(\\\'ESTATS_TITLE\\\', \\\'\' + document.getElementById(\'websitetitle\').value + \'\\\');\n\':\'\') + \'@include(\\\''.substr(dirname(__FILE__), 0, -8).'/stats.php\\\');\n?&gt;\';
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
		estats_error_message(sprintf(EstatsLocale::translate('Can not found class %s!'), $ClassName), __FILE__, __LINE__);
	}
}
else
{
	$Step = 1;

	if (!version_compare(PHP_VERSION, '5.2.0', '>='))
	{
		EstatsGUI::notify(EstatsLocale::translate('Too old PHP version!'), 'error');
		$Error = TRUE;
	}

	$Locations = array(
	'data/' => TRUE,
	'data/backups/' => TRUE,
	'data/cache/' => TRUE,
	'data/tmp/' => TRUE,
	'conf/config.php' => FALSE
	);

	foreach ($Locations as $Key => $Value)
	{
		$Locations[$Key] = (file_exists($Key)?(((substr(sprintf('%o', fileperms($Key)), -1)) >= ($Value?7:6))):0);

		if (!$Locations[$Key])
		{
			$Error = TRUE;
		}
	 }

	$Requirements = array(
	'gd' => array(function_exists('gd_info'), EstatsLocale::translate('Graphics generation support')),
	'geoip' => array(function_exists('geoip_record_by_name'), EstatsLocale::translate('Geolocation support')),
	'gettext' => array(function_exists('gettext'), EstatsLocale::translate('Reliable translations support')),
	'mbstring' => array(function_exists('mb_convert_encoding'), EstatsLocale::translate('Reliable Unicode support')),
	'bzip' => array(function_exists('bzcompress'), EstatsLocale::translate('Bzip compression support')),
	'zlib' => array(function_exists('gzcompress'), EstatsLocale::translate('Gzip compression support')),
	'zip' => array(class_exists('ZipArchive'), EstatsLocale::translate('ZIP compression support')),
	);

	$DatabaseDrivers = glob('./plugins/drivers/*');
	$DriverSelect = '';

	for ($i = 0, $c = count($DatabaseDrivers); $i < $c; ++$i)
	{
		if (file_exists($DatabaseDrivers[$i].'/plugin.php') && include ($DatabaseDrivers[$i].'/plugin.php'))
		{
			$DriverName = basename($DatabaseDrivers[$i]);
			$ClassName = 'EstatsDriver'.ucfirst(strtolower($DriverName));

			if (class_exists($ClassName))
			{
				$Object = new $ClassName;

				if ($Object->isAvailable())
				{
					$DriverSelect.= '<option>'.htmlspecialchars($DriverName).'</option>
';
				}
			}
		}
	}

	if (!$DriverSelect)
	{
		EstatsGUI::notify(EstatsLocale::translate('Lack of support for any of available modules!'), 'error');

		$Error = TRUE;
	}

	if ($Error)
	{
		 EstatsGUI::notify(EstatsLocale::translate('Could not continue!'), 'error');
	}

	EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('Introduction').'{heading-end}
</h3>
<p>
'.EstatsLocale::translate('<strong>Welcome in eStats installer!</strong><br /><br />
This script allows to install eStats easy and fast on your server.<br />
You were redirected here because file <em>conf/config.php</em> does not exists or is incorrect / incompatible with this eStats version.<br />
Select database type and admin password.<br />
After you fill all fields click <em>Continue</em> button to configure the script.').'
</p>
<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
'.($DriverSelect?'<p>
<label>
<span>
<select name="DatabaseDriver" tabindex="'.EstatsGUI::tabindex().'">
'.$DriverSelect.'</select>
</span>
'.EstatsLocale::translate('Database module').':
</label>
</p>
':'').'<p>
<label>
<span>
<input type="password" name="AdminPass" tabindex="'.EstatsGUI::tabindex().'" />
</span>
'.EstatsLocale::translate('Administrator password').':
</label>
</p>
<p>
<label>
<span>
<input type="password" name="RepeatPassword" tabindex="'.EstatsGUI::tabindex().'" />
</span>
'.EstatsLocale::translate('Repeat password').':
</label>
</p>
<h3>
{heading-start}'.EstatsLocale::translate('Permissions test').'{heading-end}
</h3>
');

	foreach ($Locations as $Key => $Value)
	{
		EstatsTheme::append('page', '<p>
<em>'.$Key.'</em> - <strong class="'.($Value?'green':'red').'">'.($Value?EstatsLocale::translate('OK'):(file_exists($Key)?EstatsLocale::translate('Not writeable'):EstatsLocale::translate('Not exists'))).'</strong>
</p>
');
	}

	EstatsTheme::append('page', '<h3>
{heading-start}'.EstatsLocale::translate('Requirements').'{heading-end}
</h3>
');

	foreach ($Requirements as $Key => $Value)
	{
		EstatsTheme::append('page', '<p>
'.$Value[1].' (<em>'.$Key.'</em>) - <strong class="'.($Value[0]?'green':'yellow').'">'.($Value[0]?EstatsLocale::translate('Available'):EstatsLocale::translate('Not available')).'</strong>
</p>
');
	}

	EstatsTheme::append('page', '<div class="buttons">
<input type="submit" value="'.($Error?EstatsLocale::translate('Continue anyway'):EstatsLocale::translate('Continue')).'" tabindex="'.EstatsGUI::tabindex().'"'.($Error?' onclick="alert(\''.EstatsLocale::translate('Continuation despite errors threatens unexpected results!\nYou continue on your own risk!').'\')"':'').' />
</div>
</form>
');
}

EstatsTheme::add('title', EstatsLocale::translate('Installer').' - '.sprintf(EstatsLocale::translate('Step %d of 3'), $Step));
?>