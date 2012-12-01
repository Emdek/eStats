<?php
/**
 * General infromation GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

if (isset($Path[2]) && in_array($Path[2], $Groups['general']))
{
	EstatsTheme::load('group');
	EstatsTheme::load('chart');
	EstatsTheme::append('title', ' - '.$Titles[$Path[2]]);
	EstatsTheme::add('group', EstatsGroup::create($Path[2], $Path[2], $Titles[$Path[2]], $Date, (isset($Path[4])?(int) $Path[4]:1), TRUE, '{path}general/'.$Path[2].'/{date}{suffix}'));
	EstatsTheme::link('group-page', 'page');
}
else
{
	for ($i = 0, $c = count($Groups['general']); $i < $c; ++$i)
	{
		EstatsTheme::add($Groups['general'][$i], EstatsGroup::create($Groups['general'][$i], $Groups['general'][$i], $Titles[$Groups['general'][$i]], $Date, 1, FALSE, '{path}general/'.$Groups['general'][$i].'/{date}{suffix}'));
	}

	$Summary = EstatsCore::summary();

	foreach ($Summary as $Key => $Value)
	{
		EstatsTheme::add($Key, (is_array($Value)?EstatsGUI::formatNumber($Value['amount']).' ('.($Value['amount']?date('d.m.Y', $Value['time']):'-').')':EstatsGUI::formatNumber($Value)));
	}
}

EstatsTheme::add('lang_fulllist', EstatsLocale::translate('Full list'));
EstatsTheme::add('lang_chart', EstatsLocale::translate('Chart'));
EstatsTheme::add('lang_visits', EstatsLocale::translate('Visits'));
EstatsTheme::add('lang_views', EstatsLocale::translate('Views'));
EstatsTheme::add('lang_unique', EstatsLocale::translate('Unique'));
EstatsTheme::add('lang_returns', EstatsLocale::translate('Returns'));
EstatsTheme::add('lang_excluded', EstatsLocale::translate('Excluded'));
EstatsTheme::add('lang_most', EstatsLocale::translate('Most'));
EstatsTheme::add('lang_lasthour', EstatsLocale::translate('Last hour'));
EstatsTheme::add('lang_last24hours', EstatsLocale::translate('Last twenty - four hours'));
EstatsTheme::add('lang_lastweek', EstatsLocale::translate('Last week'));
EstatsTheme::add('lang_lastmonth', EstatsLocale::translate('Last month'));
EstatsTheme::add('lang_lastyear', EstatsLocale::translate('Last year'));
EstatsTheme::add('lang_online', EstatsLocale::translate('On-line'));
EstatsTheme::add('lang_averageperday', EstatsLocale::translate('Average per day'));
EstatsTheme::add('lang_averageperhour', EstatsLocale::translate('Average per hour'));
?>