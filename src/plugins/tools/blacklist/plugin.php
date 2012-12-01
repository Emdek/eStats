<?php
/**
 * Blacklist management GUI for eStats
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
		EstatsGUI::saveConfiguration(array('IgnoredIPs', 'BlockedIPs', 'Keywords', 'Referrers', 'BlacklistMonitor'), $_POST, isset($_POST['Defaults']));

		if (isset($_POST['IgnoreCookie']))
		{
			EstatsCookie::set('ignore', 1);
		}
		else
		{
			EstatsCookie::delete('ignore');
		}
	}
}

if (!isset($Path[3]))
{
	$Path[3] = 0;
}

EstatsTheme::add('page', '<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Disallow statistics viewing for selected IP addresses').' <a href="#desc" tabindex="'.EstatsGUI::tabindex().'"><sup>*</sup></a>', '', 'BlockedIPs', EstatsCore::option('BlockedIPs'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignored IPs').' <a href="#desc" tabindex="'.EstatsGUI::tabindex().'"><sup>*</sup></a>', '', 'IgnoredIPs', EstatsCore::option('IgnoredIPs'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignored keywords'), '', 'Keywords', EstatsCore::option('Keywords'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignored referrers'), '', 'Referrers', EstatsCore::option('Referrers'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Save information about ignored and blocked visits'), '', 'BlacklistMonitor', EstatsCore::option('BlacklistMonitor'), EstatsGUI::FIELD_BOOLEAN).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignore visits from this browser (using <em>cookies</em>)'), '', 'IgnoreCookie', EstatsCookie::exists('ignore'), EstatsGUI::FIELD_BOOLEAN).'<p>
<small id="desc"><sup>*</sup> '.EstatsLocale::translate('Use * for replace end part of address.').'</small>
</p>
<div class="buttons">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to save?').'\')) return false" value="'.EstatsLocale::translate('Save').'" name="SaveConfiguration" tabindex="'.EstatsGUI::tabindex().'" />
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to restore defaults?').'\')) return false" value="'.EstatsLocale::translate('Defaults').'" name="Defaults" tabindex="'.EstatsGUI::tabindex().'" />
<input type="reset" value="'.EstatsLocale::translate('Reset').'" tabindex="'.EstatsGUI::tabindex().'" />
</div>

<h3>
{heading-start}'.EstatsLocale::translate('Ignored and blocked visits').'{heading-end}
</h3>
{table-start}<table cellpadding="0" cellspacing="0">
<tr>
<th>
'.EstatsLocale::translate('IP').'
</th>
<th>
'.EstatsLocale::translate('First visit').'
</th>
<th>
'.EstatsLocale::translate('Last visit').'
</th>
<th>
'.EstatsLocale::translate('Amount of visits').'
</th>
<th colspan="2">
'.EstatsLocale::translate('User Agent').'
</th>
<th>
'.EstatsLocale::translate('Type').'
</th>
</tr>
');

$EntriesAmount = EstatsCore::driver()->selectAmount('ignored');
$IgnoredAmount = 30;

if (!$Path[3])
{
	$Path[3] = ceil($EntriesAmount / $IgnoredAmount);
}

$From = ($IgnoredAmount * ($Path[3] - 1));

if ($From > $EntriesAmount)
{
	$From = 0;
	$Path[3] = 1;
}

$Entries = EstatsCore::driver()->selectData(array('ignored'), NULL, NULL, $IgnoredAmount, $From, array('lastview' => FALSE));

for ($i = 0, $c = count($Entries); $i < $c; ++$i)
{
	$Robot = EstatsCore::detectRobot($Entries[$i]['useragent']);

	if (!$Robot)
	{
		$Browser = implode(' ', EstatsCore::detectBrowser($Entries[$i]['useragent']));
		$OS = implode(' ', EstatsCore::detectOperatingSystem($Entries[$i]['useragent']));
	}

	EstatsTheme::append('page', '<tr>
<td>
<p>
'.(($Entries[$i]['ip'] == '127.0.0.1')?$Entries[$i]['ip']:EstatsGUI::whoisLink($Entries[$i]['ip'], $Entries[$i]['ip'])).'
'.EstatsGUI::ignoreIPLink(($Entries[$i]['type']?$BlockedIPs:EstatsCore::option('IgnoredIPs')), $Entries[$i]['ip'], !$Entries[$i]['type']).'
</p>
</td>
<td>
<p>
'.date('d.m.Y H:i:s', (is_numeric($Entries[$i]['firstvisit'])?$Entries[$i]['firstvisit']:strtotime($Entries[$i]['firstvisit']))).'
</p>
</td>
<td>
<p>
'.date('d.m.Y H:i:s', (is_numeric($Entries[$i]['lastview'])?$Entries[$i]['lastview']:strtotime($Entries[$i]['lastview']))).'
</p>
</td>
<td>
<p>
<span title="'.EstatsLocale::translate('Unique').'">'.$Entries[$i]['unique'].'</span>
/
<span title="'.EstatsLocale::translate('Views').'">'.($Entries[$i]['unique'] + $Entries[$i]['views']).'</span>
</p>
</td>
<td>
<p>
'.EstatsGUI::cutString($Entries[$i]['useragent'], 40, 1).'
</p>
</td>
<td>
<p>
'.($Robot?EstatsGUI::iconTag(EstatsGUI::iconPath($Robot, 'robots'), EstatsLocale::translate('Network bot').': '.EstatsGUI::itemText($Robot, 'robots')).'
':EstatsGUI::iconTag(EstatsGUI::iconPath($Browser, 'browser-versions'), EstatsLocale::translate('Browser').': '.EstatsGUI::itemText($Browser, 'browser-versions')).'
'.EstatsGUI::iconTag(EstatsGUI::iconPath($OS, 'operatingsystem-versions'), EstatsLocale::translate('Operating system').': '.EstatsGUI::itemText($OS, 'operatingsystem-versions')).'
').'</p>
</td>
<td>
<p>
'.($Entries[$i]['type']?EstatsLocale::translate('Blocked'):EstatsLocale::translate('Ignored')).'
</p>
</td>
</tr>
');
}

EstatsTheme::append('page', ($c?'':'<tr>
<td colspan="7">
<strong>'.EstatsLocale::translate('None').'</strong>
</td>
</tr>
').'</table>
{table-end}</form>
'.EstatsGUI::linksWIdget($Path[3], ceil($EntriesAmount / $IgnoredAmount), '{path}tools/blacklist/{page}{suffix}'));
?>