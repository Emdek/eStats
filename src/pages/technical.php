<?php
/**
 * Technical information GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

if (isset ($path[2]) && in_array ($path[2], $groups['technical']))
{
	EstatsTheme::load('group');
	EstatsTheme::load('chart');
	EstatsTheme::append('title', ' - '.$titles[$path[2]]);
	EstatsTheme::add('group', EstatsGroup::create($path[2], $path[2], $titles[$path[2]], $date, (isset($path[4])?(int) $path[4]:1), TRUE, '{path}technical/'.$path[2].'/{date}{suffix}'));
	EstatsTheme::link('group-page', 'page');
}
else
{
	for ($i = 0, $c = count($groups['technical']); $i < $c; ++$i)
	{
		EstatsTheme::add($groups['technical'][$i], EstatsGroup::create($groups['technical'][$i], $groups['technical'][$i], $titles[$groups['technical'][$i]], $date, 1, FALSE, '{path}technical/'.$groups['technical'][$i].'/{date}{suffix}'));
	}
}

EstatsTheme::add('lang_fulllist', EstatsLocale::translate('Full list'));
EstatsTheme::add('lang_chart', EstatsLocale::translate('Chart'));
?>