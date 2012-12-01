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

if (isset ($Path[2]) && in_array ($Path[2], $Groups['technical']))
{
	EstatsTheme::load('group');
	EstatsTheme::load('chart');
	EstatsTheme::append('title', ' - '.$Titles[$Path[2]]);
	EstatsTheme::add('group', EstatsGroup::create($Path[2], $Path[2], $Titles[$Path[2]], $Date, (isset($Path[4])?(int) $Path[4]:1), TRUE, '{path}technical/'.$Path[2].'/{date}{suffix}'));
	EstatsTheme::link('group-page', 'page');
}
else
{
	for ($i = 0, $c = count($Groups['technical']); $i < $c; ++$i)
	{
		EstatsTheme::add($Groups['technical'][$i], EstatsGroup::create($Groups['technical'][$i], $Groups['technical'][$i], $Titles[$Groups['technical'][$i]], $Date, 1, FALSE, '{path}technical/'.$Groups['technical'][$i].'/{date}{suffix}'));
	}
}

EstatsTheme::add('lang_fulllist', EstatsLocale::translate('Full list'));
EstatsTheme::add('lang_chart', EstatsLocale::translate('Chart'));
?>