<?php
/**
 * Time information GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

EstatsTheme::load('chart');

$graphics = (class_exists('EstatsGraphics') && EstatsGraphics::isAvailable() && !EstatsTheme::option('ChartSimple'));
$currentTime = (!$date[0] || ((int) $date[0] == date('Y') && (int) $date[1] == date('n') && (int) $date[2] == date('j')));

if ($currentTime)
{
	$date = array(0, 0, 0, 0);
}

$compare = (isset($path[$var + 1]) && $path[$var + 1] == 'compare');

if ($compare)
{
	$viewTypes = array_slice($viewTypes, 0, 1);

	if ($currentTime)
	{
		$date = explode('-', date('Y-n-j-G'));
	}

	$currentTime = 0;
}

for ($i = 0; $i < 3; ++$i)
{
	EstatsTheme::append('selectview', '<option value="'.$availableViewTypes[$i].'"'.(in_array ($availableViewTypes[$i], $viewTypes)?' selected="selected"':'').'>'.EstatsLocale::translate(ucfirst($availableViewTypes[$i])).'</option>
');
}

EstatsTheme::add('checkboxcomparechecked', ($compare?' checked="checked"':''));

if (!$currentTime)
{
	EstatsTheme::append('title', ' - ');

	if ($date[2])
	{
		EstatsTheme::append('title', $date[2]);
	}

	if ($date[1])
	{
		EstatsTheme::append('title', strftime('%B', mktime(0, 0, 0, $date[1])));
	}

	EstatsTheme::append('title', $date[0]);
}

$chartsAmount = 0;
$compareTypes = array('current', 'previous');

for ($i = 0, $c = count($groups['time']); $i < $c; ++$i)
{
	$timeDifference = (EstatsCore::option('AmountDifferences') && !$compare && $groups['time'][$i] != 'years' && (!$currentTime || !in_array($groups['time'][$i], array ('hours', 'weekdays'))));
	$chartArea = $chartFooter = '';

	EstatsTheme::add($groups['time'][$i], '');

	if ($var == 4 && $groups['time'][$i] !== $path[2])
	{
		continue;
	}

	$popularity = in_array($groups['time'][$i], array('hours', 'weekdays'));

	switch ($groups['time'][$i])
	{
		case '24hours':
			if (!$currentTime && !$date[2])
			{
				continue 2;
			}
		break;
		case 'month':
			if (!$currentTime && !$date[1])
			{
				continue 2;
			}
		break;
		case 'years':
			if (!$currentTime || $compare)
			{
				continue 2;
			}
		break;
		case 'hours':
		case 'weekdays':
			if ($date[2] || ($currentTime && $compare))
			{
				continue 2;
			}
		break;
	}

	++$chartsAmount;

	$chartInformation = EstatsChart::information($groups['time'][$i], $date, $currentTime);
	$fileName = $groups['time'][$i].'-'.implode('+', $viewTypes).'-'.implode('_', $date).($compare?'-compare':'');
	$data = array(array(), array());

	if (EstatsCache::status($fileName, EstatsCore::option('Cache/time')))
	{
		$ranges = array(array($chartInformation['range'][0], $chartInformation['range'][1]));

		if ($timeDifference || $compare)
		{
			$ranges[] = array(($chartInformation['range'][0] - ($chartInformation['range'][1] - $chartInformation['range'][0])), $chartInformation['range'][0]);
		}

		for ($j = 0, $l = count($ranges); $j < $l; ++$j)
		{
			$units = array(
	'hour' => '%Y.%m.%d %H',
	'dayhour' => '%H',
	'day' => '%Y.%m.%d',
	'weekday' => '%w',
	'month' => '%Y.%m',
	'year' => '%Y'
	);
			$fields = array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $units[$chartInformation['unit']])), 'unit'));
			$bits = 0;
			$bit = 1;

			for ($k = 0; $k < 3; ++$k)
			{
				$add = in_array($availableViewTypes[$k], $viewTypes);

				if ($add)
				{
					$bits += $bit;
				}

				$bit *= 2;

				if ($fields || $add)
				{
					$fields[] = array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, $availableViewTypes[$k]), $availableViewTypes[$k]);
				}
			}

			$result = EstatsCore::driver()->selectData(array('time'), $fields, EstatsCore::timeClause('time', $ranges[$j][0], $ranges[$j][1]), 0, 0, array('unit' => TRUE), array('unit'));

			for ($k = 0, $a = count($result); $k < $a; ++$k)
			{
				if ($bits & 1)
				{
					$data[$j][$result[$k]['unit']]['views'] = ($result[$k]['views'] + $result[$k]['unique'] + $result[$k]['returns']);
				}

				if ($bits & 2)
				{
					$data[$j][$result[$k]['unit']]['unique'] = ($result[$k]['unique'] + $result[$k]['returns']);
				}

				if ($bits & 4)
				{
					$data[$j][$result[$k]['unit']]['returns'] = (int) $result[$k]['returns'];
				}
			}
		}

		if ($compare)
		{
			$timestamp = ($popularity?-1:$chartInformation['range'][0]);
			$timestampBefore = ($popularity?-1:($chartInformation['range'][0] - ($chartInformation['range'][1] - $chartInformation['range'][0])));
			$timeUnit = array(0, $timestamp);
			$timeUnitBefore = array(0, $timestampBefore);

			if ($groups['time'][$i] == 'year')
			{
				$timestamp = strtotime(date('Y-m-01', $timestamp));
			}

			$tmpData = array();

			for ($j = 0; $j < $chartInformation['amount']; ++$j)
			{
				$timeUnit = EstatsGUI::timeUnit($groups['time'][$i], $timeUnit[1], $chartInformation['step'], $chartInformation['format'], $chartInformation['currenttime']);
				$unitID = $timeUnit[0];
				$timestamp = $timeUnit[1];

				if ($popularity)
				{
					$timeUnitBefore = &$timeUnit;
					$unitIDBefore = &$unitID;
					$timestampBefore = &$timestamp;
				}
				else
				{
					$timeUnitBefore = EstatsGUI::timeUnit($groups['time'][$i], $timeUnitBefore[1], $chartInformation['step'], $chartInformation['format'], 0);
					$unitIDBefore = $timeUnitBefore[0];
					$timestampBefore = $timeUnitBefore[1];
				}

			$tmpData[$unitID] = array(
	$compareTypes[0] => (isset($data[0][$unitID][$viewTypes[0]])?$data[0][$unitID][$viewTypes[0]]:0),
	$compareTypes[1] => (isset($data[1][$unitIDBefore][$viewTypes[0]])?$data[1][$unitIDBefore][$viewTypes[0]]:0),
	);
			}

			$data[0] = $tmpData;
		}

		$summary = EstatsChart::summary($groups['time'][$i], $data[0], $data[1], $chartInformation, ($compare?$compareTypes:$viewTypes), $timeDifference);
		EstatsCache::save($fileName, array(
	'data' => &$data[0],
	'data_before' => &$data[1],
	'summary' => $summary,
	));
		$cacheTime = 0;
	}
	else
	{
		$data = EstatsCache::read($fileName);
		$summary = $data['summary'];
		$data[0] = $data['data'];
		$cacheTime = EstatsCache::timestamp($fileName);
	}

	$summary['types'] = ($compare?$compareTypes:$viewTypes);
	$chartID = $groups['time'][$i].($currentTime?'':'-'.$date[0].'_'.$date[1].'_'.$date[2]).($compare?'-compare]':'');

	if ($graphics)
	{
		$graphicsSummary = $summary;
		$graphicsSummary['amount'] = $chartInformation['amount'];
		$graphicsSummary['chart'] = $groups['time'][$i];
		$graphicsSummary['step'] = $chartInformation['step'];
		$graphicsSummary['timestamp'] = $chartInformation['range'][0];
		$graphicsSummary['format'] = $chartInformation['format'];
		$graphicsSummary['currenttime'] = $currentTime;
		$_SESSION[EstatsCore::session()]['imagedata'][$chartID] = array(
	'type' => 'chart',
	'chart' => EstatsCore::option('ChartsType'),
	'diagram' => $groups['time'][$i],
	'data' => $data[0],
	'summary' => $graphicsSummary,
	'mode' => implode ('+', $viewTypes),
	'cache' => EstatsCore::option('Cache/time'),
	'join' => $popularity
	);
	}

	EstatsTheme::add($groups['time'][$i], EstatsChart::create($groups['time'][$i], ($graphics?EstatsCore::option('ChartsType'):'html'), 'time', $chartID, ((!$popularity && $groups['time'][$i] != '24hours')?'location.href = \'{path}time/'.implode('+', $summary['types']).'/{date}{suffix}\'':''), $chartInformation, $data[0], $data[1], $summary, '<a href="{path}time/'.$groups['time'][$i].'/'.implode('+', $summary['types']).($currentTime?'':'/{period}').($compare?'/compare':'').'{suffix}">
'.$titles[$groups['time'][$i]].'
</a>', $currentTime, $timeDifference, EstatsCookie::get('visits'), $cacheTime));
}

if (!$chartsAmount)
{
	EstatsGUI::notify(EstatsLocale::translate('No data to display!'), 'error');

	EstatsTheme::add('title', EstatsLocale::translate('Time statistics'));
}

EstatsTheme::add('lang_chartsview', EstatsLocale::translate('View of visits charts'));
EstatsTheme::add('lang_compareprevious', EstatsLocale::translate('Compare with previous period'));
EstatsTheme::add('lang_sum', EstatsLocale::translate('Sum'));
EstatsTheme::add('lang_most', EstatsLocale::translate('Most'));
EstatsTheme::add('lang_average', EstatsLocale::translate('Average'));
EstatsTheme::add('lang_least', EstatsLocale::translate('Least'));
?>