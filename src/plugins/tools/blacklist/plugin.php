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

if (!isset($path[3]))
{
	$path[3] = 0;
}

EstatsTheme::add('page', '<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Settings').'{heading-end}
</h3>
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Disallow statistics viewing for selected IP addresses').' <a href="#desc"><sup>*</sup></a>', '', 'BlockedIPs', EstatsCore::option('BlockedIPs'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignored IPs').' <a href="#desc"><sup>*</sup></a>', '', 'IgnoredIPs', EstatsCore::option('IgnoredIPs'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignored keywords'), '', 'Keywords', EstatsCore::option('Keywords'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignored referrers'), '', 'Referrers', EstatsCore::option('Referrers'), EstatsGUI::FIELD_ARRAY).EstatsGUI::optionRowWidget(EstatsLocale::translate('Save information about ignored and blocked visits'), '', 'BlacklistMonitor', EstatsCore::option('BlacklistMonitor'), EstatsGUI::FIELD_BOOLEAN).EstatsGUI::optionRowWidget(EstatsLocale::translate('Ignore visits from this browser (using <em>cookies</em>)'), '', 'IgnoreCookie', EstatsCookie::exists('ignore'), EstatsGUI::FIELD_BOOLEAN).'<p>
<small id="desc"><sup>*</sup> '.EstatsLocale::translate('Use * for replace end part of address.').'</small>
</p>
<div class="buttons">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do you really want to save?').'\')) return false" value="'.EstatsLocale::translate('Save').'" name="SaveConfiguration">
<input type="submit" onclick="if (!confirm(\''.EstatsLocale::translate('Do you really want to restore defaults?').'\')) return false" value="'.EstatsLocale::translate('Defaults').'" name="Defaults">
<input type="reset" value="'.EstatsLocale::translate('Reset').'">
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

$entriesAmount = EstatsCore::driver()->selectAmount('ignored');
$ignoredAmount = 30;

if (!$path[3])
{
	$path[3] = ceil($entriesAmount / $ignoredAmount);
}

$from = ($ignoredAmount * ($path[3] - 1));

if ($from > $entriesAmount)
{
	$from = 0;
	$path[3] = 1;
}

$entries = EstatsCore::driver()->selectData(array('ignored'), NULL, NULL, $ignoredAmount, $from, array('lastview' => FALSE));

for ($i = 0, $c = count($entries); $i < $c; ++$i)
{
	$robot = EstatsCore::detectRobot($entries[$i]['useragent']);

	if (!$robot)
	{
		$browser = implode(' ', EstatsCore::detectBrowser($entries[$i]['useragent']));
		$oS = implode(' ', EstatsCore::detectOperatingSystem($entries[$i]['useragent']));
	}

	EstatsTheme::append('page', '<tr>
<td>
<p>
'.(($entries[$i]['ip'] == '127.0.0.1')?$entries[$i]['ip']:EstatsGUI::whoisLink($entries[$i]['ip'], $entries[$i]['ip'])).'
'.EstatsGUI::ignoreIPLink(($entries[$i]['type']?$blockedIPs:EstatsCore::option('IgnoredIPs')), $entries[$i]['ip'], !$entries[$i]['type']).'
</p>
</td>
<td>
<p>
'.date('d.m.Y H:i:s', (is_numeric($entries[$i]['firstvisit'])?$entries[$i]['firstvisit']:strtotime($entries[$i]['firstvisit']))).'
</p>
</td>
<td>
<p>
'.date('d.m.Y H:i:s', (is_numeric($entries[$i]['lastview'])?$entries[$i]['lastview']:strtotime($entries[$i]['lastview']))).'
</p>
</td>
<td>
<p>
<span title="'.EstatsLocale::translate('Unique').'">'.$entries[$i]['unique'].'</span>
/
<span title="'.EstatsLocale::translate('Views').'">'.($entries[$i]['unique'] + $entries[$i]['views']).'</span>
</p>
</td>
<td>
<p>
'.EstatsGUI::cutString($entries[$i]['useragent'], 40, 1).'
</p>
</td>
<td>
<p>
'.($robot?EstatsGUI::iconTag(EstatsGUI::iconPath($robot, 'robots'), EstatsLocale::translate('Network bot').': '.EstatsGUI::itemText($robot, 'robots')).'
':EstatsGUI::iconTag(EstatsGUI::iconPath($browser, 'browser-versions'), EstatsLocale::translate('Browser').': '.EstatsGUI::itemText($browser, 'browser-versions')).'
'.EstatsGUI::iconTag(EstatsGUI::iconPath($oS, 'operatingsystem-versions'), EstatsLocale::translate('Operating system').': '.EstatsGUI::itemText($oS, 'operatingsystem-versions')).'
').'</p>
</td>
<td>
<p>
'.($entries[$i]['type']?EstatsLocale::translate('Blocked'):EstatsLocale::translate('Ignored')).'
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
'.EstatsGUI::linksWIdget($path[3], ceil($entriesAmount / $ignoredAmount), '{path}tools/blacklist/{page}{suffix}'));
?>