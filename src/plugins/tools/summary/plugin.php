<?php
/**
 * Main administration GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

if (isset($path[3]) && $path[3] == 'phpinfo')
{
	die(phpinfo());
}

$cacheSize = EstatsCache::size();
$backupsSize = EstatsBackups::size();
$pHPExtensions = get_loaded_extensions();
$systemLoad = (function_exists('sys_getloadavg')?sys_getloadavg():array());

natcasesort($pHPExtensions);

if (function_exists('apache_get_modules'))
{
	$apacheModules = apache_get_modules();

	natcasesort($apacheModules);
}
else
{
	$apacheModules = 0;
}

$databaseSize = 0;
$databaseTables = array_keys(EstatsCore::loadData('share/data/database.ini'));

for ($i = 0, $c = count($databaseTables); $i < $c; ++$i)
{
	$databaseSize += EstatsCore::driver()->tableSize($databaseTables[$i]);
}

if (isset($_GET['checkversion']) && !isset($_SESSION[EstatsCore::session()]['CheckVersionError']) && !(isset($_SESSION[EstatsCore::session()]['NewerVersion']) && $_SESSION[EstatsCore::session()]['NewerVersion'] != ESTATS_VERSIONSTRING))
{
	EstatsGUI::notify(EstatsLocale::translate('You are using newest available version.'), 'information');
}

EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('Summary').'{heading-end}
</h3>
<p>
'.EstatsLocale::translate('<em>eStats</em> version').':
<em><a href="http://estats.emdek.pl/index.php?path='.$path[0].'/changelog/#event_'.ESTATS_VERSIONSTRING.'">'.ESTATS_VERSIONSTRING.' - '.ESTATS_VERSIONSTATUS.'</a> ('.date('d.m.Y H:i:s', ESTATS_VERSIONTIME).')</em>'.((isset($_SESSION[EstatsCore::session()]['NewerVersion']) && $_SESSION[EstatsCore::session()]['NewerVersion'] != ESTATS_VERSIONSTRING)?'(<strong>'.EstatsLocale::translate('New version is available!').' - <a href="http://estats.emdek.pl/index.php/'.$path[0].'/changelog/#'.$_SESSION[EstatsCore::session()]['NewerVersion'].'">'.$_SESSION[EstatsCore::session()]['NewerVersion'].'</a></strong>)':'').' - <a href="{selfpath}{separator}checkversion">'.EstatsLocale::translate('Check for upgrade').'</a>;
</p>
<p>
'.EstatsLocale::translate('Database module').':
<em><a href="'.htmlspecialchars(EstatsCore::driver()->option('URL')).'" title="'.EstatsLocale::translate('Author').': '.EstatsCore::driver()->option('Author').'">'.EstatsCore::driver()->option('Name').' v'.EstatsCore::driver()->option('Version').' - '.EstatsCore::driver()->option('Status').'</a> ('.date('d.m.Y H:i:s', EstatsCore::driver()->option('Time')).')</em>;
</p>
<p>
'.EstatsLocale::translate('Database').':
<em>'.EstatsCore::driver()->option('Database').((EstatsCore::driver()->option('DatabaseVersion') != '' && EstatsCore::driver()->option('DatabaseVersion') != '?')?' '.htmlspecialchars(EstatsCore::driver()->option('DatabaseVersion')):'').'</em>;
</p>
<p>
'.EstatsLocale::translate('PHP version').':
<em>'.htmlspecialchars(PHP_VERSION).(function_exists('phpinfo')?' (<a href="{path}tools/summary/phpinfo{suffix}">phpinfo</a>)':'').'</em>;
</p>
<p>
'.EstatsLocale::translate('PHP loaded extensions').':
<em>'.implode(', ', $pHPExtensions).'</em>;
</p>
<p>
'.EstatsLocale::translate('PHP safe mode').':
<em>'.((ini_get('safe_mode') != '')?ini_get('safe_mode'):EstatsLocale::translate('N/A')).'</em>;
</p>
<p>
'.EstatsLocale::translate('Server software').':
<em>'.($_SERVER['SERVER_SOFTWARE']?htmlspecialchars($_SERVER['SERVER_SOFTWARE']):EstatsLocale::translate('N/A')).'</em>;
</p>
'.($apacheModules?'<p>
'.EstatsLocale::translate('Apache modules').':
<em>'.implode(', ', $apacheModules).'</em>;
</p>
':'').'<p>
'.EstatsLocale::translate('Operating system').':
<em>'.PHP_OS.'</em>;
</p>
<p>
'.EstatsLocale::translate('Server load').':
<em>'.($systemLoad?implode(', ', $systemLoad):EstatsLocale::translate('N/A')).'</em>;
</p>
<p>
'.EstatsLocale::translate('Data collected from').':
<em>'.date('d.m.Y H:i:s', EstatsCore::option('CollectedFrom')).'</em>;
</p>
<p>
'.EstatsLocale::translate('Data size').':
<em>'.EstatsGUI::formatSize($databaseSize + $cacheSize + $backupsSize).' (<em title="'.EstatsLocale::translate('Data').'">'.EstatsGUI::formatSize($databaseSize).'</em> / <em title="'.EstatsLocale::translate('Cache').'">'.EstatsGUI::formatSize($cacheSize).'</em> / <em title="'.EstatsLocale::translate('Backups').'">'.EstatsGUI::formatSize($backupsSize).'</em>)</em>;
</p>
<p>
'.EstatsLocale::translate('Date of last backup creation').':
<em>'.((EstatsBackups::amount() && EstatsCore::option('LastBackup'))?date('d.m.Y H:i:s', EstatsCore::option('LastBackup')):' - ').'</em>;
</p>
<p>
'.EstatsLocale::translate('Amount of available backups').':
<em>'.EstatsBackups::amount().'</em>;
</p>
<h3>
{heading-start}'.EstatsLocale::translate('Important links').'{heading-end}
</h3>
<p>
<a href="http://estats.emdek.pl/index.php?path=pl/docs">'.EstatsLocale::translate('Documentation').'</a>
</p>
<p>
<a href="http://estats.emdek.pl/forum/">'.EstatsLocale::translate('Project\'s forum').'</a>
</p>
');
?>