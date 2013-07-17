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

$databaseTables = array_keys(EstatsCore::loadData('share/data/database.ini'));

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
	$backupID = EstatsBackups::create(ESTATS_VERSIONSTRING, 'data');

	if ($backupID)
	{
		EstatsCore::logEvent(EstatsCore::EVENT_BACKUPCREATED, 'ID: '.$backupID);
		EstatsGUI::notify(EstatsLocale::translate('Backup created successfully.'), 'success');
		EstatsCore::setConfiguration(array('LastBackup' => $_SERVER['REQUEST_TIME']), 0);
	}
	else
	{
		EstatsCore::logEvent(EstatsCore::EVENT_FAILEDBACKUPCREATION, 'ID: '.$backupID);
		EstatsGUI::notify(EstatsLocale::translate('An error occured during backup create attempt!'), 'error');
	}
}

if (isset($_POST['ResetData']))
{
	for ($i = 0, $c = count($databaseTables); $i < $c; ++$i)
	{
		if (!in_array($databaseTables[$i], array('configuration', 'logs')))
		{
			EstatsCore::driver()->deleteData($databaseTables[$i]);
		}
	}

	EstatsCore::logEvent(EstatsCore::EVENT_DATADELETED);
	EstatsGUI::notify(EstatsLocale::translate('Data deleted successfully.'), 'success');
	EstatsCore::setConfiguration(array('CollectedFrom' => $_SERVER['REQUEST_TIME']), 0);
}

$databaseSize = 0;

for ($i = 0, $c = count($databaseTables); $i < $c; ++$i)
{
	$databaseSize += EstatsCore::driver()->tableSize($databaseTables[$i]);
}

$resetOptions = array(
	'Data' => $databaseSize,
	'Backups' => EstatsBackups::size(),
	'Cache' => EstatsCache::size()
	);
$optionNames = array(
	'Data' => 'Delete all statistics data',
	'Backups' => 'Delete backups',
	'Cache' => 'Reset cache'
	);
EstatsTheme::add('page', '<form action="{selfpath}" method="post">
');

foreach ($resetOptions as $key => $value)
{
	EstatsTheme::append('page', EstatsGUI::optionRowWidget(EstatsLocale::translate($optionNames[$key]).' (<strong>'.EstatsGUI::formatSize($value).'</strong>)', '', 'Reset'.$key, FALSE, EstatsGUI::FIELD_BOOLEAN));
}

EstatsTheme::append('page', EstatsGUI::optionRowWidget(EstatsLocale::translate('Create backup'), '', 'CreateBackup', 1, EstatsGUI::FIELD_BOOLEAN).'<div class="buttons">
<input type="submit" value="'.EstatsLocale::translate('Execute').'" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to delete data?').'\')) return false">
<input type="reset" value="'.EstatsLocale::translate('Reset').'">
</div>
</form>
');
?>