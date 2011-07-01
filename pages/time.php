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

$Graphics = (class_exists('EstatsGraphics') && EstatsGraphics::isAvailable() && !EstatsTheme::option('ChartSimple'));
$CurrentTime = (!$Date[0] || ((int) $Date[0] == date('Y') && (int) $Date[1] == date('n') && (int) $Date[2] == date('j')));

if ($CurrentTime)
{
	$Date = array(0, 0, 0, 0);
}

$Compare = (isset($Path[$Var + 1]) && $Path[$Var + 1] == 'compare');

if ($Compare)
{
	$ViewTypes = array_slice($ViewTypes, 0, 1);

	if ($CurrentTime)
	{
		$Date = explode('-', date('Y-n-j-G'));
	}

	$CurrentTime = 0;
}

for ($i = 0; $i < 3; ++$i)
{
	EstatsTheme::append('selectview', '<option value="'.$AvailableViewTypes[$i].'"'.(in_array ($AvailableViewTypes[$i], $ViewTypes)?' selected="selected"':'').'>'.EstatsLocale::translate(ucfirst($AvailableViewTypes[$i])).'</option>
');
}

EstatsTheme::add('selectviewindex', EstatsGUI::tabindex());
EstatsTheme::add('checkboxcompareindex', EstatsGUI::tabindex());
EstatsTheme::add('checkboxcomparechecked', ($Compare?' checked="checked"':''));

if (!$CurrentTime)
{
	EstatsTheme::append('title', ' - ');

	if ($Date[2])
	{
		EstatsTheme::append('title', $Date[2]);
	}

	if ($Date[1])
	{
		EstatsTheme::append('title', strftime('%B', mktime(0, 0, 0, $Date[1])));
	}

	EstatsTheme::append('title', $Date[0]);
}

$ChartsAmount = 0;
$CompareTypes = array('current', 'previous');

for ($i = 0, $c = count($Groups['time']); $i < $c; ++$i)
{
	$TimeDifference = (EstatsCore::option('AmountDifferences') && !$Compare && $Groups['time'][$i] != 'years' && (!$CurrentTime || !in_array($Groups['time'][$i], array ('hours', 'weekdays'))));
	$ChartArea = $ChartFooter = '';

	EstatsTheme::add($Groups['time'][$i], '');

	if ((EstatsCore::option('CollectFrequency|time') != 'hourly' && in_array($Groups['time'][$i], array ('24hours', 'hours'))) || ($Var == 4 && $Groups['time'][$i] !== $Path[2]))
	{
		continue;
	}

	$Popularity = in_array($Groups['time'][$i], array('hours', 'weekdays'));

	switch ($Groups['time'][$i])
	{
		case '24hours':
			if (!$CurrentTime && !$Date[2])
			{
				continue 2;
			}
		break;
		case 'month':
			if (!$CurrentTime && !$Date[1])
			{
				continue 2;
			}
		break;
		case 'years':
			if (!$CurrentTime || $Compare)
			{
				continue 2;
			}
		break;
		case 'hours':
		case 'weekdays':
			if ($Date[2] || ($CurrentTime && $Compare))
			{
				continue 2;
			}
		break;
	}

	++$ChartsAmount;

	$ChartInformation = EstatsChart::information($Groups['time'][$i], $Date, $CurrentTime);
	$FileName = $Groups['time'][$i].'-'.implode('+', $ViewTypes).'-'.implode('_', $Date).($Compare?'-compare':'');
	$Data = array(array(), array());

	if (EstatsCache::status($FileName, EstatsCore::option('Cache|time')))
	{
		$Ranges = array(array($ChartInformation['range'][0], $ChartInformation['range'][1]));

		if ($TimeDifference || $Compare)
		{
			$Ranges[] = array(($ChartInformation['range'][0] - ($ChartInformation['range'][1] - $ChartInformation['range'][0])), $ChartInformation['range'][0]);
		}

		for ($j = 0, $l = count($Ranges); $j < $l; ++$j)
		{
			$Units = array(
	'hour' => '%Y.%m.%d %H',
	'dayhour' => '%H',
	'day' => '%Y.%m.%d',
	'weekday' => '%w',
	'month' => '%Y.%m',
	'year' => '%Y'
	);
			$Fields = array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $Units[$ChartInformation['unit']])), 'unit'));
			$Bits = 0;
			$Bit = 1;

			for ($k = 0; $k < 3; ++$k)
			{
				$Add = in_array($AvailableViewTypes[$k], $ViewTypes);

				if ($Add)
				{
					$Bits += $Bit;
				}

				$Bit *= 2;

				if ($Fields || $Add)
				{
					$Fields[] = array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, $AvailableViewTypes[$k]), $AvailableViewTypes[$k]);
				}
			}

			$Result = EstatsCore::driver()->selectData(array('time'), $Fields, EstatsCore::timeClause('time', $Ranges[$j][0], $Ranges[$j][1]), 0, 0, array('unit' => TRUE), array('unit'));

			for ($k = 0, $a = count($Result); $k < $a; ++$k)
			{
				if ($Bits & 1)
				{
					$Data[$j][$Result[$k]['unit']]['views'] = ($Result[$k]['views'] + $Result[$k]['unique'] + $Result[$k]['returns']);
				}

				if ($Bits & 2)
				{
					$Data[$j][$Result[$k]['unit']]['unique'] = ($Result[$k]['unique'] + $Result[$k]['returns']);
				}

				if ($Bits & 4)
				{
					$Data[$j][$Result[$k]['unit']]['returns'] = (int) $Result[$k]['returns'];
				}
			}
		}

		if ($Compare)
		{
			$Timestamp = ($Popularity?-1:$ChartInformation['range'][0]);
			$TimestampBefore = ($Popularity?-1:($ChartInformation['range'][0] - ($ChartInformation['range'][1] - $ChartInformation['range'][0])));
			$TimeUnit = array(0, $Timestamp);
			$TimeUnitBefore = array(0, $TimestampBefore);

			if ($Groups['time'][$i] == 'year')
			{
				$Timestamp = strtotime(date('Y-m-01', $Timestamp));
			}

			$TmpData = array();

			for ($j = 0; $j < $ChartInformation['amount']; ++$j)
			{
				$TimeUnit = EstatsGUI::timeUnit($Groups['time'][$i], $TimeUnit[1], $ChartInformation['step'], $ChartInformation['format'], $ChartInformation['currenttime']);
				$UnitID = $TimeUnit[0];
				$Timestamp = $TimeUnit[1];

				if ($Popularity)
				{
					$TimeUnitBefore = &$TimeUnit;
					$UnitIDBefore = &$UnitID;
					$TimestampBefore = &$Timestamp;
				}
				else
				{
					$TimeUnitBefore = EstatsGUI::timeUnit($Groups['time'][$i], $TimeUnitBefore[1], $ChartInformation['step'], $ChartInformation['format'], 0);
					$UnitIDBefore = $TimeUnitBefore[0];
					$TimestampBefore = $TimeUnitBefore[1];
				}

			$TmpData[$UnitID] = array(
	$CompareTypes[0] => (isset($Data[0][$UnitID][$ViewTypes[0]])?$Data[0][$UnitID][$ViewTypes[0]]:0),
	$CompareTypes[1] => (isset($Data[1][$UnitIDBefore][$ViewTypes[0]])?$Data[1][$UnitIDBefore][$ViewTypes[0]]:0),
	);
			}

			$Data[0] = $TmpData;
		}

		$Summary = EstatsChart::summary($Groups['time'][$i], $Data[0], $Data[1], $ChartInformation, ($Compare?$CompareTypes:$ViewTypes), $TimeDifference);
		EstatsCache::save($FileName, array(
	'data' => &$Data[0],
	'data_before' => &$Data[1],
	'summary' => $Summary,
	));
		$CacheTime = 0;
	}
	else
	{
		$Data = EstatsCache::read($FileName);
		$Summary = $Data['summary'];
		$Data[0] = $Data['data'];
		$CacheTime = EstatsCache::timestamp($FileName);
	}

	$Summary['types'] = ($Compare?$CompareTypes:$ViewTypes);
	$ChartID = $Groups['time'][$i].($CurrentTime?'':'-'.$Date[0].'_'.$Date[1].'_'.$Date[2]).($Compare?'-compare]':'');

	if ($Graphics)
	{
		$GraphicsSummary = $Summary;
		$GraphicsSummary['amount'] = $ChartInformation['amount'];
		$GraphicsSummary['chart'] = $Groups['time'][$i];
		$GraphicsSummary['step'] = $ChartInformation['step'];
		$GraphicsSummary['timestamp'] = $ChartInformation['range'][0];
		$GraphicsSummary['format'] = $ChartInformation['format'];
		$GraphicsSummary['currenttime'] = $CurrentTime;
		$_SESSION[EstatsCore::session()]['imagedata'][$ChartID] = array(
	'type' => 'chart',
	'chart' => EstatsCore::option('ChartsType'),
	'diagram' => $Groups['time'][$i],
	'data' => $Data[0],
	'summary' => $GraphicsSummary,
	'mode' => implode ('+', $ViewTypes),
	'cache' => EstatsCore::option('Cache|time'),
	'join' => $Popularity
	);
	}

	EstatsTheme::add($Groups['time'][$i], EstatsChart::create($Groups['time'][$i], ($Graphics?EstatsCore::option('ChartsType'):'html'), 'time', $ChartID, ((!$Popularity && $Groups['time'][$i] != '24hours')?'location.href = \'{path}time/'.implode('+', $Summary['types']).'/{date}{suffix}\'':''), $ChartInformation, $Data[0], $Data[1], $Summary, '<a href="{path}time/'.$Groups['time'][$i].'/'.implode('+', $Summary['types']).($CurrentTime?'':'/{period}').($Compare?'/compare':'').'{suffix}" tabindex="'.EstatsGUI::tabindex().'">
'.$Titles[$Groups['time'][$i]].'
</a>', $CurrentTime, $TimeDifference, EstatsCookie::get('visits'), $CacheTime));
}

if (!$ChartsAmount)
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