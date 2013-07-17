<?php
/**
 * Backups management GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

if (isset($_POST['SaveConfiguration']) || isset($_POST['Defaults']))
{
	EstatsGUI::saveConfiguration(array('Backups/profile', 'Backups/creationinterval', 'Backups/usertables', 'Backups/replacedata'), $_POST, isset($_POST['Defaults']));
	EstatsGUI::notify(EstatsLocale::translate('Configuration saved successfully.'), 'success');
}

if (isset($_POST['DownloadBackup']))
{
	$backupInfo = explode('.', $_POST['BackupID']);
	$data = file_get_contents($dataDir.'backups/'.$_POST['BackupID'].'.bak');

	switch (strtolower($_POST['Compress']))
	{
		case 'gzip':
			header('Content-Encoding: gzip');
			$size = strlen($data);
			$data = gzcompress($data, 9);
			$data = "\x1f\x8b\x08\x00\x00\x00\x00\x00".substr($data, 0, $size);
			$extension = '';
		break;
		case 'bzip':
			$data = bzcompress($data);
			$extension = '.bz2';
		break;
		case 'zip':
			$tmpFile = $dataDir.'/tmp/export.zip';
			$zIP = new ZipArchive;
			$zIP->open($tmpFile, ZipArchive::CREATE);
			$zIP->addFromString($fileName, $data);
			$zIP->close();
			$data = file_get_contents($tmpFile);
			$extension = '.zip';

			unlink($tmpFile);
	}

	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename=eStats_'.date('Y-m-d', (int) $backupInfo[0]).'_'.date('Y-m-d').'.'.$backupInfo[1].'.bak'.$extension);
	die(trim($data));
}

if (isset($_POST['DeleteBackup']))
{
	$status = EstatsBackups::delete($_POST['BackupID']);


	if ($status)
	{
		EstatsCore::logEvent(EstatsCore::EVENT_BACKUPDELETED, 'ID: '.$_POST['BackupID']);
		EstatsGUI::notify(EstatsLocale::translate('Backup deleted successfully.'), 'success');
	}
	else
	{
		EstatsCore::logEvent(EstatsCore::EVENT_FAILEDBACKUPDELETION, 'ID: '.$_POST['BackupID']);
		EstatsGUI::notify(EstatsLocale::translate('An error occured during backup delete attempt!'), 'error');
	}
}

if (isset($_POST['CreateBackup']))
{
	$backupID = EstatsBackups::create(ESTATS_VERSIONSTRING, (($_POST['Backups/profile'] == 'user')?'manual':$_POST['Backups/profile']), (isset($_POST['Backups/usertables'])?$_POST['Backups/usertables']:array()), isset($_POST['Backups/replacedata']));

	if ($backupID)
	{
		EstatsCore::logEvent(EstatsCore::EVENT_BACKUPCREATED, 'ID: '.$backupID);
		EstatsGUI::notify(EstatsLocale::translate('Backup created successfully.'), 'success');
		EstatsCore::setConfiguration(array('LastBackup' => $_SERVER['REQUEST_TIME']), 0);

		clearstatcache();
	}
	else
	{
		EstatsCore::logEvent(EstatsCore::EVENT_FAILEDBACKUPCREATION, 'ID: '.$backupID);
		EstatsGUI::notify(EstatsLocale::translate('An error occured during backup create attempt!'), 'error');
	}
}

if (isset($_FILES['UploadBackup']) && is_uploaded_file($_FILES['UploadBackup']['tmp_name']))
{
	$_POST['BackupID'] = 'Upload-'.$_SERVER['REQUEST_TIME'].'.user';
	$_POST['RestoreBackup'] = TRUE;

	move_uploaded_file($_FILES['UploadBackup']['tmp_name'], $dataDir.'backups/'.$_POST['BackupID'].'.bak');
}

if (isset($_POST['RestoreBackup']))
{
	if (EstatsBackups::restore($_POST['BackupID']))
	{
		EstatsCore::logEvent(EstatsCore::EVENT_DATARESTORED, 'ID: '.$_POST['BackupID']);
		EstatsGUI::notify(EstatsLocale::translate('Backup restored successfully.'), 'success');
	}
	else
	{
		EstatsGUI::notify(EstatsLocale::translate('An error occured during backup restore attempt!'), 'error');
	}
}

$profiles = array();
$selectBackups = '';
$backupTypes = array('full', 'data', 'user');
$backupTypesNames = array(EstatsLocale::translate('Full'), EstatsLocale::translate('Only collected data'), EstatsLocale::translate('User definied'));

for ($i = 0; $i < 3; ++$i)
{
	$profiles[] = array($backupTypes[$i], $backupTypesNames[$i]);
	$availableBackups = EstatsBackups::available($backupTypes[$i]);

	 if ($availableBackups)
	{
		$c = count($availableBackups);
		$availableBackups = array_reverse($availableBackups);
		$selectBackups.= '<optgroup label="'.EstatsLocale::translate($backupTypesNames[$i]).'">
';
	}
	else
	{
		$c = 0;
	}

	for ($j = 0; $j < $c; ++$j)
	{
		$backupTime = explode('-', basename($availableBackups[$j]));
		$selectBackups.= '<option value="'.basename($availableBackups[$j], '.bak').'">'.(is_numeric($backupTime[0])?date('d.m.Y H:i:s', (int) $backupTime[0]):$backupTime[0]).' - '.date('d.m.Y H:i:s', (int) $backupTime[1]).' ('.EstatsGUI::formatSize(filesize($availableBackups[$j])).')</option>
';
	}

	if ($c)
	{
		$selectBackups.= '</optgroup>
';
	}
}

EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('Backups management').'{heading-end}
</h3>
<form action="{selfpath}" method="post" enctype="multipart/form-data">
'.($selectBackups?EstatsGUI::optionRowWidget(EstatsLocale::translate('Backup copy'), '', '', '<select name="BackupID">
'.$selectBackups.'</select><br>
<label>
'.EstatsLocale::translate('Compression').':
<select name="Compress" title="'.EstatsLocale::translate('Type of compression of file for download').'">
<option value="">'.EstatsLocale::translate('None').'</option>
<option selected="selected">gzip</option>
'.(extension_loaded('bz2')?'<option>bzip</option>
':'').(class_exists('ZipArchive')?'<option>ZIP</option>
':'').'</select>
</label>
<input type="submit" name="DownloadBackup" value="'.EstatsLocale::translate('Download').'">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to restore data?').'\')) return false" name="RestoreBackup" value="'.EstatsLocale::translate('Restore').'">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to delete data?').'\')) return false" name="DeleteBackup" value="'.EstatsLocale::translate('Delete').'">', EstatsGUI::FIELD_CUSTOM):'').EstatsGUI::optionRowWidget(EstatsLocale::translate('Restore backup saved on hard disc'), '', '', '<input type="file" name="UploadBackup">
<input type="submit" value="'.EstatsLocale::translate('Send').'">', EstatsGUI::FIELD_CUSTOM).'</form>
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
<form action="{selfpath}" method="post">
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Backup creation profile'), '', 'Backups/profile', EstatsCore::option('Backups/profile'), EstatsGUI::FIELD_SELECT, $profiles).EstatsGUI::optionRowWidget(EstatsLocale::translate('Create backups after specified time (s)'), '', 'Backups/creationinterval', EstatsCore::option('Backups/creationinterval')).EstatsGUI::optionRowWidget(EstatsLocale::translate('Tables to archivize'), '', 'Backups/usertables[]', EstatsCore::option('Backups/usertables'), EstatsGUI::FIELD_SELECT, array_keys(EstatsCore::loadData('share/data/database.ini'))).EstatsGUI::optionRowWidget(EstatsLocale::translate('Replace existing data'), '', 'Backups/replacedata', EstatsCore::option('Backups/replacedata'), EstatsGUI::FIELD_BOOLEAN).'<div class="buttons">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do you really want to save?').'\')) return false" value="'.EstatsLocale::translate('Save').'" name="SaveConfiguration">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do you really want to restore defaults?').'\')) return false" value="'.EstatsLocale::translate('Defaults').'" name="Defaults">
<input type="reset" value="'.EstatsLocale::translate('Reset').'">
<input type="submit" name="CreateBackup" value="'.EstatsLocale::translate('Create backup').'">
</div>
</form>
');
?>