<?php
/**
 * Configuration GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

if (isset($path[3]) && $path[3] == 'advanced')
{
	$configuration = EstatsCore::loadData('share/data/configuration.ini');
	$groupNames = array(
	'Core' => EstatsLocale::translate('Settings requeired for correct data collecting'),
	'Backups' => EstatsLocale::translate('Backups creation system configuration'),
	'GUI' => EstatsLocale::translate('User interface behavior settings'),
	'Cache' => EstatsLocale::translate('Database cache settings'),
	'Visits' => EstatsLocale::translate('Configuration of visits information'),
	'GroupAmount' => EstatsLocale::translate('Settings of amounts of displayed elements'),
	'Path' => EstatsLocale::translate('Settings of passing variables in address'),
	);
	$optionsNames = array(
	'Backups/profile' => EstatsLocale::translate('Backup creating profile'),
	'Backups/usertables' => EstatsLocale::translate('Tables to archivize (user profile)'),
	'Backups/creationinterval' => EstatsLocale::translate('Create backups after specified time (seconds)'),
	'Backups/replacedata' => EstatsLocale::translate('Replace existing data (user profile)'),
	'Backups/sqlformat' => EstatsLocale::translate('Use SQL format (user profile)'),
	'Cache/clearinterval' => EstatsLocale::translate('Interval of full cache clearing (days)'),
	'Cache/detailed' => EstatsLocale::translate('Cache time for visitors list (seconds)'),
	'Cache/enableforadministrator' => EstatsLocale::translate('Enable caching for administrator'),
	'Cache/others' => EstatsLocale::translate('Cache time for other data (seconds)'),
	'Cache/time' => EstatsLocale::translate('Cache time for time statistics data (seconds)'),
	'Visits/amount' => EstatsLocale::translate('Amount of entries per page in Visits'),
	'Visits/detailsamount' => EstatsLocale::translate('Amount of entries per page in Visit details'),
	'Visits/maxpages' => EstatsLocale::translate('Max amount of pages available for user (0 - all available)'),
	'Path/mode' => EstatsLocale::translate('Mode of passing data in the path'),
	'Path/prefix' => EstatsLocale::translate('Address prefix'),
	'Path/separator' => EstatsLocale::translate('Separator between address and GET query'),
	'Path/suffix' => EstatsLocale::translate('Address suffix'),
	'CountRobots' => EstatsLocale::translate('Add robots visits to visits'),
	'VisitTime' => EstatsLocale::translate('Time after that visit is count again (seconds)'),
	'BlockedIPs' => EstatsLocale::translate('Disallow stats viewing for selected IP addresses'),
	'IgnoredIPs' => EstatsLocale::translate('Ignored IPs'),
	'Keywords' => EstatsLocale::translate('Ignored keywords'),
	'OnlineTime' => EstatsLocale::translate('On-line visit time'),
	'Referrers' => EstatsLocale::translate('Ignored referrers'),
	'StatsEnabled' => EstatsLocale::translate('Enable data collecting'),
	'BlacklistMonitor' => EstatsLocale::translate('Save information about ignored and blocked visits'),
	'LogEnabled' => EstatsLocale::translate('Log errors and important information'),
	'CountPhrases' => EstatsLocale::translate('Count whole phrases instead of keywords'),
	'Antipixel' => EstatsLocale::translate('Statistics antipixel'),
	'DefaultTheme' => EstatsLocale::translate('Default theme'),
	'GraphicsEnabled' => EstatsLocale::translate('Use graphical charts and maps if possible'),
	'ChartsType' => EstatsLocale::translate('Chart type in Time stats'),
	'Header' => EstatsLocale::translate('Page header syntax'),
	'CheckVersionTime' => EstatsLocale::translate('Time interval between checking for new version availability (0 to disable) (seconds)'),
	'MapLink' => EstatsLocale::translate('Link for showing locations on map'),
	'WhoisLink' => EstatsLocale::translate('Link to Whois service')
	);
	$optionSelects['DefaultLanguage'] = $locales;
	$optionSelects['Antipixel'] = $optionSelects['DefaultTheme'] = array();
	$optionSelects['Backups/profile'] = array('data', 'full', 'user');
	$optionSelects['ChartsType'] = array('areas', 'bars', 'html', 'lines');
	$optionSelects['Path/mode'] = range(0, 2);
	$optionSelects['DefaultTheme'] = array_keys(EstatsTheme::available());
	$optionSelects['Antipixel'] = glob('share/antipixels/*/*.{png,gif,jpg}', GLOB_BRACE);

	for ($i = 0, $c = count($optionSelects['Antipixel']); $i < $c; ++$i)
	{
		$optionSelects['Antipixel'][$i] = str_replace('share/antipixels/', '', $optionSelects['Antipixel'][$i]);
	}

	if (isset($_POST['SaveConfiguration']) || isset($_POST['Defaults']))
	{
		EstatsGUI::saveConfiguration(array_keys(array_merge($configuration['Core'], $configuration['GUI'])), $_POST, isset($_POST['Defaults']));
	}

	EstatsTheme::add('page', '<div id="advanced">
<noscript>
'.EstatsGUI::notificationWidget(EstatsLocale::translate('Enabled JavaScript is required for correct work of this tool!'), 'error').'</noscript>
<div id="search">
<span>
<label for="AdvancedSearch">'.EstatsLocale::translate('Filter').'</label>:&nbsp;
<input value="'.EstatsLocale::translate('Search').'" id="AdvancedSearch" onblur="if (!this.value) this.value = \''.EstatsLocale::translate('Search').'\'; if (this.value == \''.EstatsLocale::translate('Search').'\') this.style.color = \'gray\';" onfocus="this.style.color = \'black\'; if (this.value == \''.EstatsLocale::translate('Search').'\') this.value = \'\'; else search(this.value)" onkeyup="search(this.value)" onkeyup="search(this.value)">
<input type="button" value="'.EstatsLocale::translate('Search').'" onclick="document.getElementById(\'AdvancedSearch\').focus(); search(document.getElementById(\'AdvancedSearch\').value);"><br>
'.EstatsLocale::translate('Meeting conditions').': <em id="ResultsAmount">{resultsamount}</em>.
</span>
<input type="checkbox" id="ShowAll" onclick="showAll()">
<label for="ShowAll">'.EstatsLocale::translate('Show all').'</label><br>
<input type="checkbox" id="ShowModified" onclick="showModified()">
<label for="ShowModified">'.EstatsLocale::translate('Show only modified').'</label>
</div>
<form action="{selfpath}" method="post">
');
	$resultsAmount = 0;
	$currentSubGroup = '';

	foreach ($configuration as $group => $options)
	{
		EstatsTheme::append('page', '<fieldset class="expanded" id="g_'.$group.'">
<legend class="parent" onclick="changeClassName(\'g_'.$group.'\')" title="'.$groupNames[$group].'">'.$group.'</legend>
<div>
<dfn class="groupdesc">'.$groupNames[$group].'</dfn>
');

		$currentSubGroup = '';

		foreach ($options as $option => $value)
		{
			if (strstr($option, '/'))
			{
				$option = str_replace('/', '|', $option);
				$array = explode('|', $option);
				$subGroup = reset($array);
				$optionName = end($array);
				$description = ((($subGroup == 'GroupAmount'))?EstatsLocale::translate(($optionName !== 'details')?$titles[$optionName]:'Details'):(isset($optionsNames[$option])?$optionsNames[$option]:''));
			}
			else
			{
				$subGroup = '';
				$optionName = $option;
				$description = (isset($optionsNames[$option])?$optionsNames[$option]:'');
			}

			if ($subGroup != $currentSubGroup)
			{
				EstatsTheme::append('page', ($currentSubGroup?'</div>
</fieldset>
':'').'<fieldset class="expanded" id="g_'.$group.'.'.$subGroup.'">
<legend onclick="changeClassName(\'g_'.$group.'.'.$subGroup.'\')" title="'.$groupNames[$subGroup].'">'.$subGroup.'</legend>
<div>
<dfn class="groupdesc">'.$groupNames[$subGroup].'</dfn>
');

				$currentSubGroup = $subGroup;
			}

			EstatsTheme::append('page', EstatsGUI::optionRowWidget($optionName, $description, $option, EstatsCore::option($option), $value['type'], (($value['type'] == EstatsGUI::FIELD_SELECT)?$optionSelects[$option]:NULL), $value['value']));

			++$resultsAmount;
		}

		if ($currentSubGroup)
		{
			EstatsTheme::append('page', '</div>
</fieldset>
');
		}

		EstatsTheme::append('page', '</div>
</fieldset>
');
	}

	EstatsTheme::append('page', '<div class="buttons">
<input type="submit" onclick="if (!confirm('.EstatsLocale::translate('Do You really want to save?').')) return false" value="'.EstatsLocale::translate('Save').'" name="SaveConfiguration">
<input type="submit" onclick="if (!confirm('.EstatsLocale::translate('Do You really want to restore defaults?').')) return false" value="'.EstatsLocale::translate('Defaults').'" name="Defaults">
<input type="reset" onclick="resetAll()" value="'.EstatsLocale::translate('Reset').'">
</div>
</form>
<script type="text/javascript">
document.getElementById(\'AdvancedSearch\').style.color = \'gray\';

var resultsAmount = {resultsamount};
var changedValueString = \''.EstatsLocale::translate('Field value is other than default').'\';
var searchString = \''.EstatsLocale::translate('Search').'\';
var expanded = true;

for (var i = 0; i < 2; ++i)
{
	var elements = document.getElementById(\'g_\' + (i ? \'Core\' : \'GUI\')).getElementsByTagName(\'fieldset\');

	for (var j = 0; j < elements.length; ++j)
	{
		elements[j].className = \'collapsed\';
	}
}
</script>
</div>
');
	EstatsTheme::add('resultsamount', $resultsAmount);
}
else
{
	if (isset($_POST['SaveConfiguration']) || isset($_POST['Defaults']))
	{
		if (isset($_POST['SaveConfiguration']))
		{
			if (isset($_POST['AccessPassword']) && $_POST['AccessPassword'] !== '')
			{
				$_POST['AccessPassword'] = md5($_POST['AccessPassword']);
			}

			if ($_POST['PathMode'] == 1)
			{
				$_POST['Path/mode'] = 1;
				$_POST['Path/prefix'] = 'index.php/';
				$_POST['Path/suffix'] = '';
				$_POST['Path/separator'] = '?';
			}
			else if ($_POST['PathMode'] == 2)
			{
				$_POST['Path/mode'] = 0;
				$_POST['Path/prefix'] = '';
				$_POST['Path/suffix'] = '/';
				$_POST['Path/separator'] = '&';
			}
			else
			{
				$_POST['Path/mode'] = 0;
				$_POST['Path/prefix'] = 'index.php?path=';
				$_POST['Path/suffix'] = '';
				$_POST['Path/separator'] = '&';
			}
		}

		EstatsGUI::saveConfiguration(array('AccessPassword', 'VisitTime', 'StatsEnabled', 'Maintenance', 'LogEnabled', 'CountPhrases', 'Antipixel', 'DefaultTheme', 'Path/mode', 'Path/prefix', 'Path/suffix', 'Path/separator'), $_POST, isset($_POST['Defaults']));
	}

	if (isset($_POST['ChangePassword']))
	{
		if (md5($_POST['CurrentPassword']) == EstatsCore::option('AdminPass') && $_POST['NewPassword'] == $_POST['RepeatPassword'])
		{
			EstatsCore::logEvent(EstatsCore::EVENT_ADMINISTRATORPASSWORDCHANGED);

			$_SESSION[EstatsCore::session()]['password'] = md5($_POST['NewPassword']);

			if (EstatsCookie::get('password'))
			{
				EstatsCookie::set('password', md5($_SESSION[EstatsCore::session()]['password'].$uniqueID), 1209600);
			}

			EstatsCore::setConfiguration(array('AdminPass' => $_SESSION[EstatsCore::session()]['password']));
			EstatsGUI::notify(EstatsLocale::translate('Administrator password changed successfully.'), 'success');
		}
		else
		{
			EstatsCore::logEvent(EstatsCore::EVENT_FAILEDADMISNISTRATORPASSWORDCHANGE);

			if (md5($_POST['CurrentPassword']) !== EstatsCore::option('AdminPass'))
			{
				EstatsCookie::delete('password');

				unset($_SESSION[EstatsCore::session()]['password']);
				die(header('Location: '.$_SERVER['REQUEST_URI']));
			}
			else
			{
				EstatsGUI::notify(EstatsLocale::translate('Given passwords are not the same!'), 0);
			}
		}
	}

	EstatsTheme::add('page', '<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Administrator password').'{heading-end}
</h3>
');

	$options = array(
	'Current' => EstatsLocale::translate('Current password'),
	'New' => EstatsLocale::translate('New password'),
	'Repeat' => EstatsLocale::translate('Repeat password')
	);

	foreach ($options as $key => $value)
	{
		EstatsTheme::append('page', EstatsGUI::optionRowWidget($value, '', $key.'Password'));
	}

	EstatsTheme::append('page', '<div class="buttons">
<input type="submit" name="ChangePassword" value="'.EstatsLocale::translate('Change password').'">
</div>
</form>
<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
');

	$options = array(
	'AccessPassword' => array(EstatsLocale::translate('Password for viewing statistics (leave empty, if you allow free access)'), '', EstatsGUI::FIELD_VALUE),
	'VisitTime' => array(EstatsLocale::translate('Time after that visit is count again (seconds)'), EstatsCore::option('VisitTime'), EstatsGUI::FIELD_VALUE),
	'StatsEnabled' => array(EstatsLocale::translate('Enable data collecting'), EstatsCore::option('StatsEnabled'), EstatsGUI::FIELD_BOOLEAN),
	'Maintenance' => array(EstatsLocale::translate('Enable maintenance mode'), EstatsCore::option('Maintenance'), EstatsGUI::FIELD_BOOLEAN),
	'LogEnabled' => array(EstatsLocale::translate('Log errors and important information'), EstatsCore::option('LogEnabled'), EstatsGUI::FIELD_BOOLEAN),
	'CountPhrases' => array(EstatsLocale::translate('Count whole phrases instead of keywords'), EstatsCore::option('CountPhrases'), EstatsGUI::FIELD_BOOLEAN)
	);

	foreach ($options as $key => $value)
	{
		EstatsTheme::append('page', EstatsGUI::optionRowWidget($value[0].(($key == 'AccessPassword')?' <strong>['.(EstatsCore::option('AccessPassword')?EstatsLocale::translate('Currently enabled'):EstatsLocale::translate('Currently disabled')).']</strong>':''), '', $key, $value[1], $value[2]));
	}

	$antipixelSelect = $currentDirectory = '';
	$antipixels = glob('share/antipixels/*/*.{png,gif,jpg}', GLOB_BRACE);

	natsort($antipixels);

	for ($i = 0, $c = count($antipixels); $i < $c; ++$i)
	{
		$antipixels[$i] = str_replace('share/antipixels/', '', $antipixels[$i]);
		$directory = dirname($antipixels[$i]);

		if ($directory != $currentDirectory)
		{
			$antipixelSelect.= ($currentDirectory?'</optgroup>
':'').'<optgroup label="'.ucfirst(basename($directory)).'">
';

			$currentDirectory = $directory;
		}

		$antipixelSelect.= '<option value="'.htmlspecialchars($antipixels[$i], ENT_QUOTES, 'UTF-8', FALSE).'"'.((EstatsCore::option('Antipixel') == $antipixels[$i])?' selected="selected"':'').'>'.ucfirst(htmlspecialchars(str_replace('_', ' ', basename($antipixels[$i])))).'</option>
';
	}

	if ($antipixelSelect)
	{
		$antipixelSelect.= '</optgroup>
';
	}

	EstatsTheme::append('page', EstatsGUI::optionRowWidget(EstatsLocale::translate('Statistics antipixel'), '', 'Antipixel', '<img src="{datapath}share/antipixels/'.htmlspecialchars(EstatsCore::option('Antipixel'), ENT_QUOTES, 'UTF-8', FALSE).'" alt="Preview" id="antipixelpreview">
<select name="Antipixel" id="F_Antipixel" onchange="document.getElementById(\'antipixelpreview\').src = \'{datapath}share/antipixels/\' + this.options[selectedIndex].value">
'.$antipixelSelect.'</select>', EstatsGUI::FIELD_CUSTOM).EstatsGUI::optionRowWidget(EstatsLocale::translate('Default theme'), '', 'DefaultTheme', EstatsCore::option('DefaultTheme'), EstatsGUI::FIELD_SELECT, array_keys($themes)).EstatsGUI::optionRowWidget(EstatsLocale::translate('Mode of passing data in the path'), '', 'PathMode', EstatsCore::option('Path/mode'), EstatsGUI::FIELD_SELECT, array('GET', 'PATH_INFO', 'Rewrite')).'<div class="buttons">
<input type="submit" onclick="if (!confirm(document.getElementById(\'F_Maintenance\').checked?\'Do you really want to enable maintenance mode?\nIf you log out before turning it off you will not be able to log in again!\':\''.EstatsLocale::translate('Do you really want to save?').'\')) return false" value="'.EstatsLocale::translate('Save').'" name="SaveConfiguration">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do you really want to restore defaults?').'\')) return false" value="'.EstatsLocale::translate('Defaults').'" name="Defaults">
<input type="reset" value="'.EstatsLocale::translate('Reset').'">
<input type="button" onclick="location.href = \'{path}tools/configuration/advanced{suffix}\'" value="'.EstatsLocale::translate('Advanced').'">
</div>
</form>
');
	}
?>