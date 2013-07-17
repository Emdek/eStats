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

if (empty($path[2]) || !isset($feeds[$path[2]]))
{
	$path[2] = 'daily';
}

$timeFormat = 'Y.m.d';
$timeUnitFormat = '%Y.%m.%d';
$timeUnitStep = 86400;

switch ($path[2])
{
	case 'daily':
		$title = EstatsLocale::translate('Daily visits summary for %A, %e %B %Y.');
		$period = array(mktime(23, 59, 59));
		$entriesAmount = 30;
		$step = 86400;
		$timeFormat = 'Y.m.d H';
		$timeUnitFormat = '%Y.%m.%d %H';
		$timeUnitStep = 3600;
		$stepsAmount = 24;
		$groups = array();
	break;
	case 'weekly':
		$title = EstatsLocale::translate('Weekly visits summary for %W week of %Y.');
		$period = array(mktime(23, 59, 59) - (date('w') * 86400));
		$entriesAmount = 25;
		$step = 604800;
		$stepsAmount = 7;
		$groups = array(
	'sites' => 20,
	'keywords' => 15,
	'operatingsystem-versions' => 15,
	'browsers' => 15
	);
	break;
	case 'monthly':
		$title = EstatsLocale::translate('Monthly visits summary for %B %Y.');
		$period = array(mktime(23, 59, 59, (date('n') + 1), 0));
		$entriesAmount = 12;
		$stepsAmount = date('t', $period[1]);
		$groups = array(
	'sites' => 30,
	'keywords' => 20,
	'operatingsystem-versions' => 20,
	'browsers' => 20
	);
	break;
}

$modified = FALSE;
$fileName = 'feed-'.$path[2];
$startTime = EstatsCore::option('CollectedFrom');
$lastUpdate = (strtotime(EstatsCore::driver()->selectField('visitors', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_MAX, 'lastvisit')))) - 60);
$cacheUpdate = EstatsCache::timestamp();
$cacheData = EstatsCache::read($fileName);
$data = array();
$entries = array();
$fields = array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $timeUnitFormat, 'unit')), 'unit'));
$timeTypes = array('unique', 'views', 'returns');
$timeSummary = array(
	'sum' => EstatsLocale::translate('Sum'),
	'max' => EstatsLocale::translate('Most'),
	'average' => EstatsLocale::translate('Average'),
	'min' => EstatsLocale::translate('Least')
);

for ($i = 0; $i < 3; ++$i)
{
	$fields[] = array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, $timeTypes[$i]), $timeTypes[$i]);
}

for ($i = 0; $i < $entriesAmount; ++$i)
{
	$period = array(($period[0] - (($path[2] == 'monthly')?(date('t', $period[0]) * 86400):$step)), $period[0]);

	if ($period[1] < $startTime)
	{
		break;
	}

	if (isset($cacheData[$period[1]]) && $period[1] <= $cacheUpdate && $lastUpdate <= $cacheUpdate)
	{
		$data[$period[1]] = &$cacheData[$period[1]];
	}
	else
	{
		$modified = TRUE;
		$data[$period[1]] = array(
	'summary' => EstatsCore::summary($period[0], $period[1]),
	'time' => array('data' => array(), 'summary' => array()),
	'groups' => array(),
	);
		$result = EstatsCore::driver()->selectData(array('time'), $fields, EstatsCore::timeClause('time', $period[0], $period[1]), 0, 0, array('unit' => TRUE), array('unit'));
		$resultAmount = count($result);
		$timeData = &$data[$period[1]]['time'];
		$minimum = (($resultAmount == $stepsAmount)?0:-1);

		for ($j = 0; $j < 3; ++$j)
		{
			$timeData['sum'][$timeTypes[$j]] = $timeData['max'][$timeTypes[$j]] = 0;
			$timeData['min'][$timeTypes[$j]] = $minimum;
		}

		for ($j = 0; $j < $resultAmount; ++$j)
		{
			$timeUnit = &$result[$j]['unit'];
			$timeData['data'][$timeUnit] = array(
	'views' => ($result[$j]['views'] + $result[$j]['unique'] + $result[$j]['returns']),
	'unique' => ($result[$j]['unique'] + $result[$j]['returns']),
	'returns' => (int) $result[$j]['returns']
	);

			for ($k = 0; $k < 3; ++$k)
			{
				$timeData['sum'][$timeTypes[$k]] += $timeData['data'][$timeUnit][$timeTypes[$k]];

				if ($timeData['max'][$timeTypes[$k]] < $timeData['data'][$timeUnit][$timeTypes[$k]])
				{
					$timeData['max'][$timeTypes[$k]] = $timeData['data'][$timeUnit][$timeTypes[$k]];
				}

				if ($timeData['min'][$timeTypes[$k]] < 0 || $timeData['min'][$timeTypes[$k]] > $timeData['data'][$timeUnit][$timeTypes[$k]])
				{
					$timeData['min'][$timeTypes[$k]] = $timeData['data'][$timeUnit][$timeTypes[$k]];
				}
			}
		}

		for ($j = 0; $j < 3; ++$j)
		{
			$timeData['average'][$timeTypes[$j]] = round(($timeData['sum'][$timeTypes[$j]] / $stepsAmount), 2);
		}
	}

	$summary = sprintf(($timeData['sum']['views']?EstatsLocale::translate('Between %s and %s there were %d unique visits (%d views), of which %d were returns.'):EstatsLocale::translate('Between %s and %s there were no visits.')), date('Y-m-d H:i:s', (($startTime > $period[0])?$startTime:($period[0] + 1))), date('Y-m-d H:i:s', $period[1]), $timeData['sum']['unique'], $timeData['sum']['views'], $timeData['sum']['returns']);
	$content = '<h2>'.EstatsLocale::translate('Summary').'</h2>
'.$summary.'
';

	if ($timeData['data'])
	{
		$content.= '<h2>'.EstatsLocale::translate('Visits').'</h2>
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

		$timestamp = $period[0];

		for ($j = 0; $j < $stepsAmount; ++$j)
		{
			$timestamp += $timeUnitStep;
			$timeUnit = date($timeFormat, $timestamp);
			$content.= '<tr>
<th>
'.$timeUnit.'
</th>
';

			for ($k = 0; $k < 3; ++$k)
			{
				$content.= '<td>
'.(isset($timeData['data'][$timeUnit][$timeTypes[$k]])?$timeData['data'][$timeUnit][$timeTypes[$k]]:0).'
</td>
';
			}

			$content.= '</tr>
';
		}

		$content.= '<tr>
';

		foreach ($timeSummary as $key => $label)
		{
			$content.= '<th>
'.$label.':
</th>
';

			for ($k = 0; $k < 3; ++$k)
			{
				$content.= '<td>
'.$timeData[$key][$timeTypes[$k]].'
</td>
';
			}
		}

		$content.= '</tr>
</table>
';
	}

	foreach ($groups as $group => $amount)
	{
		if (!isset($data[$period[1]]['groups'][$group]))
		{
			$data[$period[1]]['groups'][$group] = array(
	'data' => EstatsGroup::selectData($group, $amount, 0, $period[0], $period[1]),
	'amount' => EstatsGroup::selectAmount($group, $amount, $period[0], $period[1]),
	'sum' => EstatsGroup::selectSum($group, $period[0], $period[1]),
	);
		}

		$groupData = &$data[$period[1]]['groups'][$group];

		if (!$groupData['amount'])
		{
			continue;
		}

		$address = ($group == 'sites');
		$content.= '<h2>'.(($groupData['amount'] && $groupData['amount'] !== (($groups[$group] > $groupData['amount'])?$groupData['amount']:$groups[$group]))?sprintf(EstatsLocale::translate('%s (%d of %d)'), $titles[$group], count($groupData['data']), $groupData['amount']):$titles[$group]).'</h2>
<ol>
';

		for ($j = 0, $l = count($groupData['data']); $j < $l; ++$j)
		{
			$content.= '<li>
'.($address?'<a href="'.htmlspecialchars($groupData['data'][$j]['address']).'">':'').htmlspecialchars(EstatsGUI::itemText(trim($groupData['data'][$j]['name']), $group)).($address?'</a>':'').' - <em>'.$groupData['data'][$j]['amount_current'].' ('.round((($groupData['data'][$j]['amount_current'] / $groupData['sum']) * 100), 2).'%)</em>
</li>
';
		}

		$content.= '</ol>
<strong>'.EstatsLocale::translate('Sum').': <em>'.$groupData['sum'].'</em></strong>
';
	}

	$entries[$period[1]] = '<entry>
<title type="text">
'.strftime($title, $period[1]).'
</title>
<summary type="text">
'.$summary.'
</summary>
<content type="xhtml">
<div xmlns="http://www.w3c.org/1999/xhtml">
'.$content.'</div>
</content>
<id>http://{servername}{path}feed/'.$path[2].'/'.($period[1] + 1).'{suffix}</id>
<updated>'.date(DATE_ATOM, (($period[1] > $_SERVER['REQUEST_TIME'])?$_SERVER['REQUEST_TIME']:$period[1])).'</updated>
<author>
<name>eStats</name>
</author>
</entry>
';
}

if ($modified)
{
	EstatsCache::save($fileName, $data);
}

krsort($entries);
header('Content-type: application/atom+xml; charset=utf-8');
die(EstatsTheme::parse('<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{language}">
<title type="text">
eStats :: '.EstatsLocale::translate('Feed channel for {servername}').'
</title>
<subtitle type="text">
'.EstatsLocale::translate('Short summary of collected data.').'
</subtitle>
<id>http://{servername}{path}feed/'.$path[2].'{suffix}</id>
<icon>http://{servername}{datapath}share/icons/miscellaneous/estats.png</icon>
<generator uri="http://estats.emdek.pl/">eStats</generator>
<updated>'.date('Y-m-d\TH:i:s\Z', $lastUpdate).'</updated>
<link rel="alternate" type="text/html" href="http://{servername}{path}" />
<link rel="self" type="application/atom+xml" href="http://{servername}{path}feed{suffix}" />
'.implode('', $entries).'</feed>'));
?>