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
	if (defined('ESTATS_DEMO'))
	{
		EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
	}
	else
	{
		EstatsGUI::saveConfiguration(array('Backups|profile', 'Backups|creationinterval', 'Backups|usertables', 'Backups|replacedata'), $_POST, isset($_POST['Defaults']));
		EstatsGUI::notify(EstatsLocale::translate('Configuration saved successfully.'), 'success');
	}
}

if (isset($_POST['DownloadBackup']))
{
	$BackupInfo = explode('.', $_POST['BackupID']);
	$Data = file_get_contents($DataDir.'backups/'.$_POST['BackupID'].'.bak');

	switch (strtolower($_POST['Compress']))
	{
		case 'gzip':
			header('Content-Encoding: gzip');
			$Size = strlen($Data);
			$Data = gzcompress($Data, 9);
			$Data = "\x1f\x8b\x08\x00\x00\x00\x00\x00".substr($Data, 0, $Size);
			$Extension = '';
		break;
		case 'bzip':
			$Data = bzcompress($Data);
			$Extension = '.bz2';
		break;
		case 'zip':
			$TmpFile = $DataDir.'/tmp/export.zip';
			$ZIP = new ZipArchive;
			$ZIP->open($TmpFile, ZipArchive::CREATE);
			$ZIP->addFromString($FileName, $Data);
			$ZIP->close();
			$Data = file_get_contents($TmpFile);
			$Extension = '.zip';

			unlink($TmpFile);
	}

	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename=eStats_'.date('Y-m-d', (int) $BackupInfo[0]).'_'.date('Y-m-d').'.'.$BackupInfo[1].'.bak'.$Extension);
	die(trim($Data));
}

if (isset($_POST['DeleteBackup']))
{
	if (defined('ESTATS_DEMO'))
	{
		EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
	}
	else
	{
		$Status = EstatsBackups::delete($_POST['BackupID']);

		if ($Status)
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
}

if (isset($_POST['CreateBackup']))
{
	if (defined('ESTATS_DEMO'))
	{
		EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
	}
	else
	{
		$BackupID = EstatsBackups::create(ESTATS_VERSIONSTRING, (($_POST['Backups|profile'] == 'user')?'manual':$_POST['Backups|profile']), (isset($_POST['Backups|usertables'])?$_POST['Backups|usertables']:array()), isset($_POST['Backups|replacedata']));

		if ($BackupID)
		{
			EstatsCore::logEvent(EstatsCore::EVENT_BACKUPCREATED, 'ID: '.$BackupID);
			EstatsGUI::notify(EstatsLocale::translate('Backup created successfully.'), 'success');
			EstatsCore::setConfiguration(array('LastBackup' => $_SERVER['REQUEST_TIME']), 0);

			clearstatcache();
		}
		else
		{
			EstatsCore::logEvent(EstatsCore::EVENT_FAILEDBACKUPCREATION, 'ID: '.$BackupID);
			EstatsGUI::notify(EstatsLocale::translate('An error occured during backup create attempt!'), 'error');
		}
	}
}

if (isset($_FILES['UploadBackup']) && is_uploaded_file($_FILES['UploadBackup']['tmp_name']))
{
	if (defined('ESTATS_DEMO'))
	{
		EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
	}
	else
	{
		$_POST['BackupID'] = 'Upload-'.$_SERVER['REQUEST_TIME'].'.user';
		$_POST['RestoreBackup'] = TRUE;

		move_uploaded_file($_FILES['UploadBackup']['tmp_name'], $DataDir.'backups/'.$_POST['BackupID'].'.estats.bak');
	}
}

if (isset($_POST['RestoreBackup']))
{
	if (defined('ESTATS_DEMO'))
	{
		EstatsGUI::notify(EstatsLocale::translate('This functionality is disabled in demo mode!'), 'warning');
	}
	else
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
}

$Profiles = array();
$SelectBackups = '';
$BackupTypes = array('full', 'data', 'user');
$BackupTypesNames = array(EstatsLocale::translate('Full'), EstatsLocale::translate('Only collected data'), EstatsLocale::translate('User definied'));

for ($i = 0; $i < 3; ++$i)
{
	$Profiles[] = array($BackupTypes[$i], $BackupTypesNames[$i]);
	$AvailableBackups = EstatsBackups::available($BackupTypes[$i]);

	if ($AvailableBackups)
	{
		$c = count($AvailableBackups);
		$AvailableBackups = array_reverse($AvailableBackups);
		$SelectBackups.= '<optgroup label="'.EstatsLocale::translate($BackupTypesNames[$i]).'">
';
	}
	else
	{
		$c = 0;
	}

	for ($j = 0; $j < $c; ++$j)
	{
		$BackupTime = explode('-', basename($AvailableBackups[$j]));
		$SelectBackups.= '<option value="'.basename($AvailableBackups[$j], '.bak').'">'.(is_numeric($BackupTime[0])?date('d.m.Y H:i:s', (int) $BackupTime[0]):$BackupTime[0]).' - '.date('d.m.Y H:i:s', (int) $BackupTime[1]).' ('.EstatsGUI::formatSize(filesize($AvailableBackups[$j])).')</option>
';
	}

	if ($c)
	{
		$SelectBackups.= '</optgroup>
';
	}
}

EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('Backups management').'{heading-end}
</h3>
<form action="{selfpath}" method="post" enctype="multipart/form-data">
'.($SelectBackups?EstatsGUI::optionRowWidget(EstatsLocale::translate('Backup copy'), '', '', '<select name="BackupID" tabindex="'.EstatsGUI::tabindex().'">
'.$SelectBackups.'</select><br />
<label>
'.EstatsLocale::translate('Compression').':
<select name="Compress" title="'.EstatsLocale::translate('Type of compression of file for download').'">
<option value="">'.EstatsLocale::translate('None').'</option>
<option selected="selected">gzip</option>
'.(extension_loaded('bz2')?'<option>bzip</option>
':'').(class_exists('ZipArchive')?'<option>ZIP</option>
':'').'</select>
</label>
<input type="submit" name="DownloadBackup" value="'.EstatsLocale::translate('Download').'" tabindex="'.EstatsGUI::tabindex().'" />
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to restore data?').'\')) return false" name="RestoreBackup" value="'.EstatsLocale::translate('Restore').'" tabindex="'.EstatsGUI::tabindex().'" />
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to delete data?').'\')) return false" name="DeleteBackup" value="'.EstatsLocale::translate('Delete').'" tabindex="'.EstatsGUI::tabindex().'" />', EstatsGUI::FIELD_CUSTOM):'').EstatsGUI::optionRowWidget(EstatsLocale::translate('Restore backup saved on hard disc'), '', '', '<input type="file" name="UploadBackup" tabindex="'.EstatsGUI::tabindex().'" />
<input type="submit" value="'.EstatsLocale::translate('Send').'" tabindex="'.EstatsGUI::tabindex().'" />', EstatsGUI::FIELD_CUSTOM).'</form>
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
<form action="{selfpath}" method="post">
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Backup creation profile'), '', 'Backups|profile', EstatsCore::option('Backups|profile'), EstatsGUI::FIELD_SELECT, $Profiles).EstatsGUI::optionRowWidget(EstatsLocale::translate('Create backups after specified time (s)'), '', 'Backups|creationinterval', EstatsCore::option('Backups|creationinterval')).EstatsGUI::optionRowWidget(EstatsLocale::translate('Tables to archivize'), '', 'Backups|usertables[]', EstatsCore::option('Backups|usertables'), EstatsGUI::FIELD_SELECT, array_keys(EstatsCore::loadData('share/data/database.ini'))).EstatsGUI::optionRowWidget(EstatsLocale::translate('Replace existing data'), '', 'Backups|replacedata', EstatsCore::option('Backups|replacedata'), EstatsGUI::FIELD_BOOLEAN).'<div class="buttons">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to save?').'\')) return false" value="'.EstatsLocale::translate('Save').'" name="SaveConfiguration" tabindex="'.EstatsGUI::tabindex().'" />
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to restore defaults?').'\')) return false" value="'.EstatsLocale::translate('Defaults').'" name="Defaults" tabindex="'.EstatsGUI::tabindex().'" />
<input type="reset" value="'.EstatsLocale::translate('Reset').'" tabindex="'.EstatsGUI::tabindex().'" /><br />
<input type="submit" name="CreateBackup" value="'.EstatsLocale::translate('Create backup').'" tabindex="'.EstatsGUI::tabindex().'" />
</div>
</form>
');
?>