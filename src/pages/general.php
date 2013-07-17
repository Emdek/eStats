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

if (isset($path[2]) && in_array($path[2], $groups['general']))
{
	EstatsTheme::load('group');
	EstatsTheme::load('chart');
	EstatsTheme::append('title', ' - '.$titles[$path[2]]);
	EstatsTheme::add('group', EstatsGroup::create($path[2], $path[2], $titles[$path[2]], $date, (isset($path[4])?(int) $path[4]:1), TRUE, '{path}general/'.$path[2].'/{date}{suffix}'));
	EstatsTheme::link('group-page', 'page');
}
else
{
	for ($i = 0, $c = count($groups['general']); $i < $c; ++$i)
	{
		EstatsTheme::add($groups['general'][$i], EstatsGroup::create($groups['general'][$i], $groups['general'][$i], $titles[$groups['general'][$i]], $date, 1, FALSE, '{path}general/'.$groups['general'][$i].'/{date}{suffix}'));
	}

	$summary = EstatsCore::summary();

	foreach ($summary as $key => $value)
	{
		EstatsTheme::add($key, (is_array($value)?EstatsGUI::formatNumber($value['amount']).' ('.($value['amount']?date('d.m.Y', $value['time']):'-').')':EstatsGUI::formatNumber($value)));
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