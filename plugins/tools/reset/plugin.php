<?php
/**
 * Resetting GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

$DatabaseTables = array_keys(EstatsCore::loadData('share/data/database.ini'));

if (isset($_POST['ResetBackups']))
{
	EstatsBackups::delete();
	EstatsCore::logEvent(EstatsCore::EVENT_BACKUPSDELETED);
	EstatsGUI::notify(EstatsLocale::translate('Backups deleted successfully.'), 'success');
}

if (isset($_POST['ResetCache']))
{
	EstatsCache::delete();
}

if (isset($_POST['CreateBackup']))
{
	$BackupID = EstatsBackups::create('data');

	if ($BackupID)
	{
		EstatsCore::logEvent(EstatsCore::EVENT_BACKUPCREATED, 'ID: '.$BackupID);
		EstatsGUI::notify(EstatsLocale::translate('Backup created successfully.'), 'success');
		EstatsCore::setConfiguration(array('LastBackup' => $_SERVER['REQUEST_TIME']), 0);
	}
	else
	{
		EstatsCore::logEvent(EstatsCore::EVENT_FAILEDBACKUPCREATION, 'ID: '.$BackupID);
		EstatsGUI::notify(EstatsLocale::translate('An error occured during backup create attempt!'), 'error');
	}
}

if (isset($_POST['ResetData']))
{
	for ($i = 0, $c = count($DatabaseTables); $i < $c; ++$i)
	{
		if (!in_array($DatabaseTables[$i], array('configuration', 'logs')))
		{
			EstatsCore::driver()->deleteData($DatabaseTables[$i]);
		}
	}

	EstatsCore::logEvent(EstatsCore::EVENT_DATADELETED);
	EstatsGUI::notify(EstatsLocale::translate('Data deleted successfully.'), 'success');
	EstatsCore::setConfiguration(array('CollectedFrom' => $_SERVER['REQUEST_TIME']), 0);
}

if (isset($_POST['ResetTables']) && !array_diff($_POST['Tables'], $DatabaseTables) && !in_array('configuration', $_POST['Tables']) && !in_array('logs', $_POST['Tables']))
{
	for ($i = 0, $c = count($_POST['Tables']); $i < $c; ++$i)
	{
		if (!in_array($DatabaseTables[$i], array('configuration', 'logs')) && in_array($_POST['Tables'][$i], $DatabaseTables))
		{
			EstatsCore::driver()->deleteData($_POST['Tables'][$i]);
		}
	}

	EstatsCore::logEvent(EstatsCore::EVENT_TABLESEMPTIED, implode(', ', $_POST['Tables']));
	EstatsGUI::notify(EstatsLocale::translate('Selected tables emptied successfully.'), 'success');
}

$DatabaseSize = 0;

for ($i = 0, $c = count($DatabaseTables); $i < $c; ++$i)
{
	$DatabaseSize += EstatsCore::driver()->tableSize($DatabaseTables[$i]);
}

$ResetOptions = array(
	'Data' => $DatabaseSize,
	'Backups' => EstatsBackups::size(),
	'Cache' => EstatsCache::size()
	);
$OptionNames = array(
	'Data' => 'Delete all statistics data',
	'Backups' => 'Delete backups',
	'Cache' => 'Reset cache'
	);
EstatsTheme::add('page', '<form action="{selfpath}" method="post">
');

foreach ($ResetOptions as $Key => $Value)
{
	EstatsTheme::append('page', EstatsGUI::optionRowWidget(EstatsLocale::translate($OptionNames[$Key]).' (<strong>'.EstatsGUI::formatSize($Value).'</strong>)', '', 'Reset'.$Key, FALSE, EstatsGUI::FIELD_BOOLEAN));
}

for ($i = 0, $c = count($DatabaseTables); $i < $c; ++$i)
{
	if (in_array($DatabaseTables[$i], array('configuration', 'logs')))
	{
		unset($DatabaseTables[$i]);
	}
}

EstatsTheme::append('page', EstatsGUI::optionRowWidget(EstatsLocale::translate('Reset selected tables'), '', 'Tables', array(), EstatsGUI::FIELD_SELECT, $DatabaseTables).EstatsGUI::optionRowWidget(EstatsLocale::translate('Create backup'), '', 'CreateBackup', 1, EstatsGUI::FIELD_BOOLEAN).'<div class="buttons">
<input type="submit" value="'.EstatsLocale::translate('Execute').'" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to delete data?').'\')) return false" tabindex="'.EstatsGUI::tabindex().'" />
<input type="reset" value="'.EstatsLocale::translate('Reset').'" tabindex="'.EstatsGUI::tabindex().'" />
</div>
</form>
');
?>