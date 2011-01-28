<?php
/**
 * Feed for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats') || EstatsCore::option('Pass') || !include ('./lib/group.class.php'))
{
	die();
}

$Amounts = array(
	'daily' => 20,
	'weekly' => 10,
	'monthly' => 5
	);
$Diagrams = array(
	'daily' => array('24hours', 'hour'),
	'weekly' => array('week', 'day'),
	'monthly' => array('month', 'day'),
	);
$Groups = array(
	'daily' => array(),
	'weekly' => array(
		'sites' => 20,
		'keywords' => 15,
		'operatingsystem-versions' => 15,
		'browsers' => 15
		),
	'monthly' => array(
		'sites' => 30,
		'keywords' => 20,
		'operatingsystem-versions' => 20,
		'browsers' => 20
		),
	);
$TimeTypes = array('unique', 'returns', 'views');
$Types = array();

if (isset($Path[3]) && $Path[3])
{
	$TmpTypes = explode('+', $Path[3]);

	for ($i = 0, $c = count($TmpTypes); $i < $c; ++$i)
	{
		if (isset($Amounts[$TmpTypes[$i]]) && $Amounts[$TmpTypes[$i]] && !in_array($TmpTypes[$i], $Types))
		{
			$Types[] = $TmpTypes[$i];
		}
	}
}

if (!$Types)
{
	$Types = array('daily', 'weekly', 'monthly');
}

$Feeds = array(
	'daily' => mktime(0, 0, 0, date('n'), (date('j') - 1), date('Y')),
	'weekly' => (mktime(0, 0, 0, date('n'), date('j'), date('Y')) - (date('w') * 86400)),
	'monthly' => strtotime(date('Y-m-t 00:00', strtotime('last month')))
	);
$Updated = 0;
$FeedArray = array();

foreach ($Feeds as $Key => $Value)
{
	if (!$Amounts[$Key] || !in_array($Key, $Types))
	{
		continue;
	}

	$Modified = 0;
	$FileName = 'feed-'.$Key;
	$Data = EstatsCache::read($FileName);
	$NewData = array();
	$TimeStamp = $Feeds[$Key];

	for ($i = 0; $i < $Amounts[$Key]; ++$i)
	{
		if ($TimeStamp > $Updated)
		{
			$Updated = $TimeStamp;
		}

		switch ($Key)
		{
			case 'daily':
				$Step = 86400;
			break;
			case 'weekly':
				$Step = 604800;
			break;
			case 'monthly':
				$Step = (date('t', $TimeStamp) * 86400);
		}

		if (!isset($NewData[$TimeStamp]))
		{
			$Modified = 1;
			$NewData[$TimeStamp]['summary'] = EstatsCore::summary(($TimeStamp - $Step), $TimeStamp);

			if ($Groups[$Key])
			{
				$NewData[$TimeStamp]['tables'] = array();

				foreach ($Groups[$Key] as $Table => $Amount)
				{
					$NewData[$TimeStamp]['tables'][$Table] = array(
	'data' => EstatsGroup::selectData($Table, $Amount, 0, ($TimeStamp - $Step), $TimeStamp),
	'amount' => EstatsGroup::selectAmount($Table, $Amount, ($TimeStamp - $Step), $TimeStamp),
	'sum_current' => EstatsGroup::selectSum($Table, ($TimeStamp - $Step), $TimeStamp),
	'sum_before' => (EstatsCore::option('AmountDifferences')?EstatsGroup::selectSum($Table, ($TimeStamp - ($Step * 2)), ($TimeStamp - $Step)):0),
	);
				}
			}

			$NewData[$TimeStamp]['time'] = EstatsGroup::selectDataPeriod($Diagrams[$Key][1], ($TimeStamp - $Step), $TimeStamp, array('unique', 'views', 'returns'));

		}
		else
		{
			$NewData[$TimeStamp] = &$Data[$TimeStamp];
		}

		$Date = date('Y-m-d\TH:i:s\Z', $TimeStamp);
		$Title = sprintf(EstatsLocale::translate(ucfirst($Key).' visits summary for %s.'), date('Y-m-d', $TimeStamp));
		$Summary = sprintf(EstatsLocale::translate($NewData[$TimeStamp]['summary']['views']?'Between %s and %s there were %d unique visits (%d views), which %d were returns.':'Between %s and %s there were no visits.'), date('Y-m-d H:00', ($TimeStamp - $Step)), date('Y-m-d H:i', $TimeStamp), $NewData[$TimeStamp]['summary']['unique'], $NewData[$TimeStamp]['summary']['views'], $NewData[$TimeStamp]['summary']['returns']);
		$Content = '<h1>
'.EstatsLocale::translate('Summary').'
</h1>
'.$Summary.'
';
		if ($Groups[$Key])
		{
			foreach ($NewData[$TimeStamp]['tables'] as $Table => $GroupData)
			{
				if (!$GroupData['amount'])
				{
					continue;
				}

				$Content.= '<h2>
'.EstatsLocale::translate($Titles[$Table]).(($GroupData['amount'] && $GroupData['amount'] != (($Groups[$Key][$Table] > $GroupData['amount'])?$GroupData['amount']:$Groups[$Key][$Table]))?' ('.count($GroupData['data']).' '.EstatsLocale::translate('of').' '.$GroupData['amount'].')':'').'
</h2>
<ol>
';
				for ($j = 0, $l = count($GroupData['data']); $j < $l; ++$j)
				{
					$Name = trim($GroupData['data'][$j]['name']);
					$Address = '';

					if ($Table == 'sites')
					{
						$Address = &$GroupData['data'][$j]['address'];
					}
					else if ($Table == 'websearchers')
					{
						$Address = &$Name;
					}
					else if ($Table == 'referrers' && $Name && $Name != '?')
					{
						$Address = &$Name;
					}
					else if ($Table== 'cities' && $Name && $Name != '?')
					{
						$Address = EstatsGUI::mapLink($GroupData['data'][$j]['latitude'], $GroupData['data'][$j]['longitude']);
					}

					$String = EstatsGUI::itemText($Name, $Table);
					$Content.= '<li>
'.($Address?'<a href="'.htmlspecialchars($Address).'" title="'.htmlspecialchars($String).'">
':'').htmlspecialchars($String).($Address?'
</a>':'').' - <em>'.$GroupData['data'][$j]['amount_current'].' ('.round((($GroupData['data'][$j]['amount_current'] / $GroupData['sum_current']) * 100), 2).'%)</em>
</li>
';
				}

				if (!$GroupData['amount'])
				{
					$Content.= '<li>
<strong>'.EstatsLocale::translate('None').'</strong>
</li>
';
				}

				$Content.= '</ol>
<strong>'.EstatsLocale::translate('Sum').': <em>'.$GroupData['sum_current'].'</em></strong>
';
			}
		}

		if ((EstatsCore::option('CollectFrequency|time') == 'hourly' || $Key != '24hours') && $NewData[$TimeStamp]['time'])
		{
			$Content.= '<h2>
'.EstatsLocale::translate('Time').' ('.EstatsLocale::translate($Titles[$Diagrams[$Key][0]]).')
</h2>
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

			switch ($Key)
			{
				case 'daily':
					$Amount = 24;
					$TimeStep = 3600;
					$DateString = 'Y.m.d H';
				break;
				case 'weekly':
					$Amount = 7;
					$TimeStep = 86400;
					$DateString = 'Y.m.d';
				break;
				case 'monthly':
					$Amount = date('t', $TimeStamp);
					$TimeStep = 86400;
					$DateString = 'Y.m.d';
			}

			$DiagramTimeStamp = ($TimeStamp - $Step);

			for ($j = 0, $l = count($TimeTypes); $j < $l; ++$j)
			{
				$TimeSummary['sum'][$TimeTypes[$j]] = $TimeSummary['max'][$TimeTypes[$j]] = $TimeSummary['min'][$TimeTypes[$j]] = 0;
			}

			for ($j = 0; $j < $Amount; ++$j)
			{
				$DiagramTimeStamp += $TimeStep;
				$UnitID = date($DateString, $DiagramTimeStamp);
				$Content.= '<tr>
<td>
<em>'.$UnitID.'</em>
</td>
';
				for ($k = 0; $k < 3; ++$k)
				{
					if (!isset($NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]]))
					{
						$NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]] = 0;
					}

					$TimeSummary['sum'][$TimeTypes[$k]] += $NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]];

					if ($TimeSummary['max'][$TimeTypes[$k]] < $NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]])
					{
						$TimeSummary['max'][$TimeTypes[$k]] = $NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]];
					}

					if ($TimeSummary['min'][$TimeTypes[$k]] > $NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]])
					{
						$TimeSummary['min'][$TimeTypes[$k]] = $NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]];
					}

					$Content.= '<td>
'.$NewData[$TimeStamp]['time'][$UnitID][$TimeTypes[$k]].'
</td>
';
				}

				$Content.= '</tr>
';
			}

			$Content.= '<tr>
<th>
'.EstatsLocale::translate('Sum').':
</th>
';
			for ($k = 0, $l = count($TimeTypes); $k < $l; ++$k)
			{
				$Content.= '<td>
'.$TimeSummary['sum'][$TimeTypes[$k]].'
</td>
';
			}

			$Content.= '</tr>
<tr>
<th>
'.EstatsLocale::translate('Most').':
</th>
';
			for ($k = 0, $l = count($TimeTypes); $k < $l; ++$k)
			{
				$Content.= '<td>
'.$TimeSummary['max'][$TimeTypes[$k]].'
</td>
';
			}

			$Content.= '</tr>
<tr>
<th>
'.EstatsLocale::translate('Average').':
</th>
';
			for ($k = 0, $l = count($TimeTypes); $k < $l; ++$k)
			{
				$Content.= '<td>
'.round(($TimeSummary['sum'][$TimeTypes[$k]] / $Amount), 2).'
</td>
';
			}

			$Content.= '</tr>
<tr>
<th>
'.EstatsLocale::translate('Least').':
</th>
';
			for ($k = 0, $l = count($TimeTypes); $k < $l; ++$k)
			{
				$Content.= '<td>
'.$TimeSummary['min'][$TimeTypes[$k]].'
</td>
';
			}

			$Content.= '</tr>
</table>
';
		}

		$FeedArray[$TimeStamp.'-'.$Key] = '<entry>
<title type="text">
'.$Title.'
</title>
<summary type="text">
'.$Summary.'
</summary>
<content type="xhtml">
<div xmlns="http://www.w3c.org/1999/xhtml">
'.$Content.'</div>
</content>
<id>http://{servername}{path}feed/'.$Key.'/'.$TimeStamp.'{suffix}</id>
<updated>'.$Date.'</updated>
<author>
<name>eStats</name>
</author>
</entry>
';
		$TimeStamp -= $Step;
	}

	if ($Modified)
	{
		EstatsCache::save($FileName, $NewData);
	}
}

krsort($FeedArray);
header('Content-type: application/atom+xml; charset=utf-8');
die(EstatsTheme::parse('<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{lang}">
<title type="text">
eStats :: '.EstatsLocale::translate('Feed channel for {servername}').'
</title>
<subtitle type="text">
'.EstatsLocale::translate('Short summary of collected data from 24 hours, week or month.').'
</subtitle>
<id>http://{servername}{path}feed/'.implode('+', $Types).'</id>
<icon>http://{servername}{datapath}share/icons/miscellaneous/estats.png</icon>
<generator uri="http://estats.emdek.cba.pl/">eStats</generator>
<updated>'.date('Y-m-d\TH:i:s\Z', $Updated).'</updated>
<link rel="alternate" type="text/html" href="http://{servername}{path}" />
<link rel="self" type="application/atom+xml" href="http://{servername}{path}feed" />
'.implode('', $FeedArray).'</feed>'));
?>