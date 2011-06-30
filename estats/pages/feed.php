<?php
/**
 * Feed for eStats
 * @author Emdek <http://emdek.pl>
 * @version 5.0.00
 */

if (!defined('eStats') || EstatsCore::option('AccessPassword'))
{
	die();
}

if (empty($Path[2]) || !isset($Feeds[$Path[2]]))
{
	$Path[2] = 'daily';
}

$VisitsAvailable = (EstatsCore::option('CollectFrequency/time') == 'hourly' || EstatsCore::option('CollectFrequency/time') == 'daily');
$TimeFormat = 'Y.m.d';
$TimeUnitFormat = '%Y.%m.%d';
$TimeUnitStep = 86400;

switch ($Path[2])
{
	case 'daily':
		$Title = EstatsLocale::translate('Daily visits summary for %A, %e %B %Y.');
		$Period = array(mktime(23, 59, 59));
		$EntriesAmount = 30;
		$Step = 86400;
		$VisitsAvailable = (EstatsCore::option('CollectFrequency/time') == 'hourly');
		$TimeFormat = 'Y.m.d H';
		$TimeUnitFormat = '%Y.%m.%d %H';
		$TimeUnitStep = 3600;
		$StepsAmount = 24;
		$Groups = array();
	break;
	case 'weekly':
		$Title = EstatsLocale::translate('Weekly visits summary for %W week of %Y.');
		$Period = array(mktime(23, 59, 59) - (date('w') * 86400));
		$EntriesAmount = 25;
		$Step = 604800;
		$StepsAmount = 7;
		$Groups = array(
	'sites' => 20,
	'keywords' => 15,
	'operatingsystem-versions' => 15,
	'browsers' => 15
	);
	break;
	case 'monthly':
		$Title = EstatsLocale::translate('Monthly visits summary for %B %Y.');
		$Period = array(mktime(23, 59, 59, (date('n') + 1), 0));
		$EntriesAmount = 12;
		$StepsAmount = date('t', $Period[1]);
		$Groups = array(
	'sites' => 30,
	'keywords' => 20,
	'operatingsystem-versions' => 20,
	'browsers' => 20
	);
	break;
}

$Modified = FALSE;
$FileName = 'feed-'.$Path[2];
$StartTime = EstatsCore::option('CollectedFrom');
$LastUpdate = (strtotime(EstatsCore::driver()->selectField('visitors', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MAX, 'lastvisit')))) - 60);
$CacheUpdate = EstatsCache::timestamp();
$CacheData = EstatsCache::read($FileName);
$Data = array();
$Entries = array();
$Fields = array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $TimeUnitFormat, 'unit')), 'unit'));
$TimeTypes = array('unique', 'views', 'returns');
$TimeSummary = array(
	'sum' => EstatsLocale::translate('Sum'),
	'max' => EstatsLocale::translate('Most'),
	'average' => EstatsLocale::translate('Average'),
	'min' => EstatsLocale::translate('Least')
);

for ($i = 0; $i < 3; ++$i)
{
	$Fields[] = array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, $TimeTypes[$i]), $TimeTypes[$i]);
}

for ($i = 0; $i < $EntriesAmount; ++$i)
{
	$Period = array(($Period[0] - (($Path[2] == 'monthly')?(date('t', $Period[0]) * 86400):$Step)), $Period[0]);

	if ($Period[1] < $StartTime)
	{
		break;
	}

	if (isset($CacheData[$Period[1]]) && $Period[1] <= $CacheUpdate && $LastUpdate <= $CacheUpdate)
	{
		$Data[$Period[1]] = &$CacheData[$Period[1]];
	}
	else
	{
		$Modified = TRUE;
		$Data[$Period[1]] = array(
	'summary' => EstatsCore::summary($Period[0], $Period[1]),
	'time' => array('data' => array(), 'summary' => array()),
	'groups' => array(),
	);
		$Result = EstatsCore::driver()->selectData(array('time'), $Fields, EstatsCore::timeClause('time', $Period[0], $Period[1]), 0, 0, array('unit' => TRUE), array('unit'));
		$ResultAmount = count($Result);
		$TimeData = &$Data[$Period[1]]['time'];
		$Minimum = (($ResultAmount == $StepsAmount)?0:-1);

		for ($j = 0; $j < 3; ++$j)
		{
			$TimeData['sum'][$TimeTypes[$j]] = $TimeData['max'][$TimeTypes[$j]] = 0;
			$TimeData['min'][$TimeTypes[$j]] = $Minimum;
		}

		for ($j = 0; $j < $ResultAmount; ++$j)
		{
			$TimeUnit = &$Result[$j]['unit'];
			$TimeData['data'][$TimeUnit] = array(
	'views' => ($Result[$j]['views'] + $Result[$j]['unique'] + $Result[$j]['returns']),
	'unique' => ($Result[$j]['unique'] + $Result[$j]['returns']),
	'returns' => (int) $Result[$j]['returns']
	);

			for ($k = 0; $k < 3; ++$k)
			{
				$TimeData['sum'][$TimeTypes[$k]] += $TimeData['data'][$TimeUnit][$TimeTypes[$k]];

				if ($TimeData['max'][$TimeTypes[$k]] < $TimeData['data'][$TimeUnit][$TimeTypes[$k]])
				{
					$TimeData['max'][$TimeTypes[$k]] = $TimeData['data'][$TimeUnit][$TimeTypes[$k]];
				}

				if ($TimeData['min'][$TimeTypes[$k]] < 0 || $TimeData['min'][$TimeTypes[$k]] > $TimeData['data'][$TimeUnit][$TimeTypes[$k]])
				{
					$TimeData['min'][$TimeTypes[$k]] = $TimeData['data'][$TimeUnit][$TimeTypes[$k]];
				}
			}
		}

		for ($j = 0; $j < 3; ++$j)
		{
			$TimeData['average'][$TimeTypes[$j]] = round(($TimeData['sum'][$TimeTypes[$j]] / $StepsAmount), 2);
		}
	}

	$Summary = sprintf(($TimeData['sum']['views']?EstatsLocale::translate('Between %s and %s there were %d unique visits (%d views), of which %d were returns.'):EstatsLocale::translate('Between %s and %s there were no visits.')), date('Y-m-d H:i:s', (($StartTime > $Period[0])?$StartTime:($Period[0] + 1))), date('Y-m-d H:i:s', $Period[1]), $TimeData['sum']['unique'], $TimeData['sum']['views'], $TimeData['sum']['returns']);
	$Content = '<h2>'.EstatsLocale::translate('Summary').'</h2>
'.$Summary.'
';

	if ($VisitsAvailable && $TimeData['data'])
	{
		$Content.= '<h2>'.EstatsLocale::translate('Visits').'</h2>
<table cellpadding="2px" cellspacing="0" border="1px" width="100%">
<tr>
<th>
'.EstatsLocale::translate('Date').'
</th>
<th>
'.EstatsLocale::translate('Unique').'
</th>
<th>
'.EstatsLocale::translate('Views').'
</th>
<th>
'.EstatsLocale::translate('Returns').'
</th>
</tr>
';

		$Timestamp = $Period[0];

		for ($j = 0; $j < $StepsAmount; ++$j)
		{
			$Timestamp += $TimeUnitStep;
			$TimeUnit = date($TimeFormat, $Timestamp);
			$Content.= '<tr>
<th>
'.$TimeUnit.'
</th>
';

			for ($k = 0; $k < 3; ++$k)
			{
				$Content.= '<td>
'.(isset($TimeData['data'][$TimeUnit][$TimeTypes[$k]])?$TimeData['data'][$TimeUnit][$TimeTypes[$k]]:0).'
</td>
';
			}

			$Content.= '</tr>
';
		}

		$Content.= '<tr>
';

		foreach ($TimeSummary as $Key => $Label)
		{
			$Content.= '<th>
'.$Label.':
</th>
';

			for ($k = 0; $k < 3; ++$k)
			{
				$Content.= '<td>
'.$TimeData[$Key][$TimeTypes[$k]].'
</td>
';
			}
		}

		$Content.= '</tr>
</table>
';
	}

	foreach ($Groups as $Group => $Amount)
	{
		if (!isset($Data[$Period[1]]['groups'][$Group]))
		{
			$Data[$Period[1]]['groups'][$Group] = array(
	'data' => EstatsGroup::selectData($Group, $Amount, 0, $Period[0], $Period[1]),
	'amount' => EstatsGroup::selectAmount($Group, $Amount, $Period[0], $Period[1]),
	'sum' => EstatsGroup::selectSum($Group, $Period[0], $Period[1]),
	);
		}

		$GroupData = &$Data[$Period[1]]['groups'][$Group];

		if (!$GroupData['amount'])
		{
			continue;
		}

		$Address = ($Group == 'sites');
		$Content.= '<h2>'.(($GroupData['amount'] && $GroupData['amount'] !== (($Groups[$Group] > $GroupData['amount'])?$GroupData['amount']:$Groups[$Group]))?sprintf(EstatsLocale::translate('%s (%d of %d)'), $Titles[$Group], count($GroupData['data']), $GroupData['amount']):$Titles[$Group]).'</h2>
<ol>
';

		for ($j = 0, $l = count($GroupData['data']); $j < $l; ++$j)
		{
			$Content.= '<li>
'.($Address?'<a href="'.htmlspecialchars($GroupData['data'][$j]['address']).'">':'').htmlspecialchars(EstatsGUI::itemText(trim($GroupData['data'][$j]['name']), $Group)).($Address?'</a>':'').' - <em>'.$GroupData['data'][$j]['amount_current'].' ('.round((($GroupData['data'][$j]['amount_current'] / $GroupData['sum']) * 100), 2).'%)</em>
</li>
';
		}

		$Content.= '</ol>
<strong>'.EstatsLocale::translate('Sum').': <em>'.$GroupData['sum'].'</em></strong>
';
	}

	$Entries[$Period[1]] = '<entry>
<title type="text">
'.strftime($Title, $Period[1]).'
</title>
<summary type="text">
'.$Summary.'
</summary>
<content type="xhtml">
<div xmlns="http://www.w3c.org/1999/xhtml">
'.$Content.'</div>
</content>
<id>http://{servername}{path}feed/'.$Path[2].'/'.($Period[1] + 1).'{suffix}</id>
<updated>'.date(DATE_ATOM, (($Period[1] > $_SERVER['REQUEST_TIME'])?$_SERVER['REQUEST_TIME']:$Period[1])).'</updated>
<author>
<name>eStats</name>
</author>
</entry>
';
}

if ($Modified)
{
	EstatsCache::save($FileName, $Data);
}

krsort($Entries);
header('Content-type: application/atom+xml; charset=utf-8');
die(EstatsTheme::parse('<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{language}">
<title type="text">
eStats :: '.EstatsLocale::translate('Feed channel for {servername}').'
</title>
<subtitle type="text">
'.EstatsLocale::translate('Short summary of collected data.').'
</subtitle>
<id>http://{servername}{path}feed/'.$Path[2].'{suffix}</id>
<icon>http://{servername}{datapath}share/icons/miscellaneous/estats.png</icon>
<generator uri="http://estats.emdek.cba.pl/">eStats</generator>
<updated>'.date('Y-m-d\TH:i:s\Z', $LastUpdate).'</updated>
<link rel="alternate" type="text/html" href="http://{servername}{path}" />
<link rel="self" type="application/atom+xml" href="http://{servername}{path}feed{suffix}" />
'.implode('', $Entries).'</feed>'));
?>