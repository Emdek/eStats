<?php
/**
 * Chart class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 1.1.01
 */

class EstatsChart
{

/**
 * Generates information for chart
 * @param string Period
 * @param array Date
 * @param boolean CurrentTime
 * @return array
 */

	static function information($period, $date, $currentTime)
	{
		$data = array();
		$data['currenttime'] = $currentTime;
		$data['step'] = 0;

		switch ($period)
		{
			case '24hours':
				if ($currentTime)
				{
					$data['range'] = array((time() - 86400), 0);
				}
				else
				{
					$data['range'] = EstatsGUI::timeRange($date[0], $date[1], $date[2]);
				}

				$data['unit'] = 'hour';
				$data['format'] = 'Y.m.d H';
				$data['amount'] = 24;
				$data['step'] = 3600;
			break;
			case 'month':
				if ($currentTime)
				{
					$data['range'] = array(strtotime('last month'), 0);
					$data['amount'] = date('t', ((date('t') == date('j'))?$_SERVER['REQUEST_TIME']:strtotime('last month')));
				}
				else
				{
					$data['range'] = EstatsGUI::timeRange($date[0], $date[1]);
					$data['amount'] = date('t', ((date('t', $data['range'][1]) == date('j', $data['range'][1]))?$data['range'][1]:$data['range'][0]));
				}

				$data['unit'] = 'day';
				$data['format'] = 'Y.m.d';
				$data['step'] = 86400;
			break;
			case 'year':
				if ($currentTime)
				{
					$data['range'] = array(strtotime('last year'), 0);
				}
				else
				{
					$data['range'] = EstatsGUI::timeRange($date[0]);
				}

				$data['unit'] = 'month';
				$data['format'] = 'Y.m';
				$data['amount'] = 12;
			break;
			case 'years':
				$yearsRange = (date('Y') - date('Y', EstatsCore::option('CollectedFrom')));
				$lastYears = $yearsRange;

				if ($lastYears > 15)
				{
					$lastYears = 15;
				}
				else if ($lastYears < 5)
				{
					$lastYears = 5;
				}

				if ($currentTime)
				{
					$data['range'] = array(strtotime('-'.$lastYears.' years'), 0);
				}
				else
				{
					$data['range'] = array(strtotime(($date[0] - $lastYears + 1).'-01-01'), strtotime(($date[0] + 1).'-01-01'));
				}

				$data['unit'] = 'year';
				$data['format'] = 'Y';
				$data['amount'] = $lastYears;
			break;
			case 'hours':
				if ($currentTime)
				{
					$data['range'] = array(0, 0);
				}
				else
				{
					$data['range'] = EstatsGUI::timeRange($date[0], $date[1], $date[2]);
				}

				$data['unit'] = 'dayhour';
				$data['format'] = '';
				$data['amount'] = 24;
				$data['step'] = 1;
			break;
			case 'weekdays':
				if ($currentTime)
				{
					$data['range'] = array(0, 0);
				}
				else
				{
					$data['range'] = EstatsGUI::timeRange($date[0], $date[1], $date[2]);
				}

				$data['unit'] = 'weekday';
				$data['format'] = 'w';
				$data['amount'] = 7;
				$data['step'] = 1;
			break;
		}

		return $data;
	}

/**
 * Generates summary for chart
 * @param string Period
 * @param array Data
 * @param array DataBefore
 * @param array Information
 * @param array Types
 * @param boolean CurrentTime
 * @return array
 */

	static function summary($period, $data, $dataBefore, $information, $types, $timeDifference)
	{
		ksort($data);

		$summary = array();
		$typeAmounts = count($types);

		for ($i = 0; $i < $typeAmounts; ++$i)
		{
			$summary['maximum'][$types[$i]] = $summary['minimum'][$types[$i]] = $summary['sum'][$types[$i]] = $summary['maximum_before'][$types[$i]] = $summary['minimum_before'][$types[$i]] = $summary['sum_before'][$types[$i]] = 0;
		}

		$information['timestamp'] = $information['range'][0];
		$timeUnit = array(0, (in_array($period, array('hours', 'weekdays'))?-1:$information['timestamp']));

		for ($i = 0; $i < $information['amount']; ++$i)
		{
			$timeUnit = EstatsGUI::timeUnit($period, $timeUnit[1], $information['step'], $information['format'], $information['currenttime']);

			for ($j = 0; $j < $typeAmounts; ++$j)
			{
				if (!isset($data[$timeUnit[0]][$types[$j]]))
				{
					continue;
				}

				if ($data[$timeUnit[0]][$types[$j]] > $summary['maximum'][$types[$j]])
				{
					$summary['maximum'][$types[$j]] = $data[$timeUnit[0]][$types[$j]];
				}

				if (($data[$timeUnit[0]][$types[$j]] < $summary['minimum'][$types[$j]]) || !$summary['minimum'][$types[$j]])
				{
					$summary['minimum'][$types[$j]] = $data[$timeUnit[0]][$types[$j]];
				}

				$summary['sum'][$types[$j]] += $data[$timeUnit[0]][$types[$j]];
			}
		}

		if ($timeDifference)
		{
			foreach ($dataBefore as $unit => $array)
			{
				for ($i = 0; $i < $typeAmounts; ++$i)
				{
					if (!isset($array[$types[$i]]))
					{
						continue;
					}

					if ($array[$types[$i]] > $summary['maximum_before'][$types[$i]])
					{
						$summary['maximum_before'][$types[$i]] = $array[$types[$i]];
					}

					if (($array[$types[$i]] < $summary['minimum_before'][$types[$i]]) || !$summary['minimum_before'][$types[$i]])
					{
						$summary['minimum_before'][$types[$i]] = $array[$types[$i]];
					}

					$summary['sum_before'][$types[$i]] += $array[$types[$i]];
				}
			}
		}

		for ($i = 0; $i < $typeAmounts; ++$i)
		{
			$summary['average'][$types[$i]] = ($summary['sum'][$types[$i]] / $information['amount']);

			if ($timeDifference)
			{
				$summary['average_before'][$types[$i]] = ($summary['sum_before'][$types[$i]] / $information['amount']);
			}
		}

		krsort($summary['sum']);

		$summary['maxall'] = max($summary['maximum']);

		if (count($data) != $information['amount'])
		{
			for ($i = 0; $i < $typeAmounts; ++$i)
			{
				$summary['minimum'][$types[$i]] = 0;
			}
		}

		if ($timeDifference && count($dataBefore) != $information['amount'])
		{
			for ($i = 0; $i < $typeAmounts; ++$i)
			{
				$summary['minimum_before'][$types[$i]] = 0;
			}
		}

		return $summary;
	}

/**
 * Generates chart
 * @param string Period
 * @param string Type
 * @param string Category
 * @param string ID
 * @param string Action
 * @param array Information
 * @param array Data
 * @param array DataBefore
 * @param array Summary
 * @param string Title
 * @param boolean Icons
 * @param boolean TimeDifference
 * @param array UserVisits
 * @param integer CacheTime
 * @return string
 */

	static function create($period, $type, $category, $iD, $action, $information, $data, $dataBefore, $summary, $title, $currentTime, $timeDifference, $userVisits = NULL, $cacheTime = 0)
	{
		$levelTypes = array('maximum', 'average', 'minimum');
		$levelNames = array(EstatsLocale::translate('maximum'), EstatsLocale::translate('average'), EstatsLocale::translate('minimum'));
		$typeAmounts = count($summary['types']);
		$maxValues = $minValues = array();
		$popularity = in_array($period, array('hours', 'weekdays'));
		$timestamp = $information['range'][0];

		if ($timeDifference)
		{
			$timestampBefore = ($information['range'][0] - ($information['range'][1] - $information['range'][0]));
		}

		$chartArea = $descriptions = $chart = '';
		$chartFooter = '<tr>
<th colspan="{colspan}">
&nbsp;
</th>
</tr>
';
		switch ($period)
		{
			case '24hours':
				if (!$currentTime || date('G') == 23)
				{
					break;
				}

				$yesterday = strtotime('yesterday');
				$chartFooter = '<tr class="footer">
<th colspan="'.(23 - date('G')).'" title="'.ucfirst(strftime('%A', $yesterday)).'">
'.(in_array(date('w', $yesterday), array(0, 6))?'<em>'.strtoupper(strftime('%a', $yesterday)).'</em>':strtoupper(strftime('%a', $yesterday))).'
</th>
<th colspan="'.(date('G') + 1).'" title="'.ucfirst(strftime('%A')).'">
'.(in_array(date('w'), array(0, 6))?'<em>'.strtoupper(strftime('%a')).'</em>':strtoupper(strftime('%a'))).'
</th>
<th>
&nbsp;
</th>
</tr>
';
			break;
			case 'month':
				if (!$currentTime || date('t') == date('j'))
				{
					break;
				}

				$month = ((date('n') - 1)?(date('n') - 1):12);
				$chartFooter = '<tr class="footer">
<th colspan="'.(date('t', strtotime('last month')) - date('j')).'" title="'.ucfirst(strftime('%B', mktime(0, 0, 0, $month))).'">
'.strtoupper(strftime('%b', mktime(0, 0, 0, $month))).'
</th>
<th colspan="'. date('j').'" title="'.ucfirst(strftime('%B')).'">
'.strtoupper(strftime('%b')).'
</th>
<th>
&nbsp;
</th>
</tr>
';
			break;
			case 'year':
				$timestamp = strtotime(date('Y-m-01', $timestamp));

				if ($timeDifference)
				{
					$timestampBefore = strtotime(date('Y-m-01', $timestampBefore));
				}

				if (!$currentTime || date('n') == 12)
				{
					break;
				}

				$chartFooter = '<tr class="footer">
<th colspan="'.(12 - date('n')).'" title="'.(date('Y') - 1).'">
'.(date('Y') - 1).'
</th>
<th colspan="'.date('n').'" title="'.date('Y').'">
'.date('Y').'
</th>
<th>
&nbsp;
</th>
</tr>
';
			break;
			case 'years':
				$timestamp = strtotime(date('Y-01-01', $timestamp));

				if ($timeDifference)
				{
					$timestampBefore = strtotime(date('Y-01-01', $timestampBefore));
				}
			break;
			case 'hours':
			case 'weekdays':
				$timestamp = -1;
			break;
		}

		$colours = explode('|', EstatsTheme::option('ChartTimeColours'));
		$barWidth = round(700 / $information['amount']);
		$timeUnit = array(0, $timestamp);
		$unitID = $timeUnit[0];
		$timestamp = $timeUnit[1];

		if ($timeDifference)
		{
			$timeUnitBefore = array(0, $timestampBefore);
		}

		for ($i = 0; $i < $information['amount']; ++$i)
		{
			$columnTitle = $description = $bars = '';
			$nextTimeStamp = $yourVisits = 0;

			if ($currentTime || $popularity)
			{
				$timeUnit = EstatsGUI::timeUnit($period, $timeUnit[1], $information['step'], $information['format'], $information['currenttime']);
				$unitID = $timeUnit[0];
				$timestamp = $timeUnit[1];
			}

			switch ($period)
			{
				case '24hours':
					$description = date('H', $timestamp);
					$toolTipDate = date('Y.m.d H:00');
				break;
				case 'month':
					$weekDay = date('w', $timestamp);
					$columnTitle = strftime('%A', $timestamp);
					$description = date('d', $timestamp);

					if ($weekDay == 0 || $weekDay == 6)
					{
						$description = '<em>'.$description.'</em>';
					}

					$toolTipDate = $columnTitle.', '.date('Y.m.d', $timestamp);
					$columnTitle = ucfirst($columnTitle);
				break;
				case 'year':
					$dateID = date('n', $timestamp);
					$description = strtoupper(strftime('%b', mktime(0, 0, 0, $dateID)));
					$columnTitle = strftime('%B', mktime(0, 0, 0, $dateID));
					$toolTipDate = $columnTitle.' '.date('Y', $timestamp);
					$columnTitle = ucfirst($columnTitle);
				break;
				case 'years':
					$toolTipDate = $description = $unitID;
				break;
				case 'hours':
					$toolTipDate = $description = (($unitID === 0)?'0':'').$unitID;
				break;
				case 'weekdays':
					$timestamp = (94694400 + (($unitID - 1) * 86400));
					$toolTipDate = strftime('%A', $timestamp);
					$columnTitle = ucfirst($toolTipDate);
					$description = strtoupper(strftime('%a', $timestamp));

					if ($unitID == 0 || $unitID == 6)
					{
						$description = '<em>'.$description.'</em>';
					}
				break;
			}

			if ($action)
			{
				switch ($period)
				{
					case 'month':
						$date = date('Y-n-j-0', $timestamp);
					break;
					case 'year':
						$date = date('Y-n-0-0', $timestamp);
					break;
					case 'years':
						$date = date('Y-0-0-0', $timestamp);
					break;
				}
			}

			if (!$currentTime && !$popularity)
			{
				$timeUnit = EstatsGUI::timeUnit($period, $timeUnit[1], $information['step'], $information['format'], $information['currenttime']);
				$unitID = $timeUnit[0];
				$timestamp = $timeUnit[1];
			}

			if ($timeDifference)
			{
				if ($popularity)
				{
					$timeUnitBefore = &$timeUnit;
					$unitIDBefore = &$unitID;
					$timestampBefore = &$timestamp;
				}
				else
				{
					$timeUnitBefore = EstatsGUI::timeUnit($period, $timeUnitBefore[1], $information['step'], $information['format'], 0);
					$unitIDBefore = $timeUnitBefore[0];
					$timestampBefore = $timeUnitBefore[1];
				}
			}

			$descriptions.= '<th'.($columnTitle?' title="'.$columnTitle.'"':'').'>'.$description.'</th>
';
			$size = $toolTip = array();

			for ($j = 0; $j < $typeAmounts; ++$j)
			{
				if (isset($data[$unitID][$summary['types'][$j]]) && $data[$unitID][$summary['types'][$j]] == $summary['maximum'][$summary['types'][$j]])
				{
					$maxValues[$summary['types'][$j]][] = $i;
				}

				if (!isset($data[$unitID][$summary['types'][$j]]) || $data[$unitID][$summary['types'][$j]] == $summary['minimum'][$summary['types'][$j]])
				{
					$minValues[$summary['types'][$j]][] = $i;
				}

				$size[$summary['types'][$j]] = ((isset($data[$unitID][$summary['types'][$j]]) && $data[$unitID][$summary['types'][$j]])?(($data[$unitID][$summary['types'][$j]] / $summary['maxall']) * 150):0);
				$maximum = (isset($data[$unitID][$summary['types'][$j]]) && $data[$unitID][$summary['types'][$j]] == $summary['maximum'][$summary['types'][$j]] && $summary['maximum'][$summary['types'][$j]]);

				if ($type == 'html')
				{
					$height = $size[$summary['types'][$j]];

					if (!$height)
					{
						$bars.= '<div class="empty"></div>
	';
					}
					else
					{
						$bars.= EstatsTheme::parse(EstatsTheme::get('chart-bar'), array(
	'height' => round($height),
	'margin' => round(150 - $size[$summary['types'][$j]]),
	'colour' => '#'.$colours[$j],
	'id' => 'bar_'.$iD.'_'.$i.'_'.$j,
	'class' => ($maximum?'maximum':(($data[$unitID][$summary['types'][$j]] == $summary['minimum'][$summary['types'][$j]])?'minimum':'')),
	'title' => EstatsGUI::itemText($summary['types'][$j], $category).': '.$data[$unitID][$summary['types'][$j]],
	'simplebar' => (EstatsTheme::option('ChartSimple')?str_repeat(' <br>
', (int) (($height / 150) * 10)):'')
	));
					}
				}

				if (!EstatsTheme::option('ChartSimple'))
				{
					$number = (isset($data[$unitID][$summary['types'][$j]])?$data[$unitID][$summary['types'][$j]]:0);

					if (!$number)
					{
						continue;
					}

					$sum = $summary['sum'][$summary['types'][$j]];

					if ($timeDifference)
					{
						$difference = EstatsGUI::formatDifference((isset($data[$unitID][$summary['types'][$j]])?$data[$unitID][$summary['types'][$j]]:0), (isset($dataBefore[$unitIDBefore][$summary['types'][$j]])?$dataBefore[$unitIDBefore][$summary['types'][$j]]:0));
					}

					$toolTip[$number.'.'.$j] = EstatsGUI::itemText($summary['types'][$j], $category).': '.($maximum?'<strong>':'').$number.($maximum?'</strong>':'').' ('.($sum?round((($number / $sum) * 100), 1):0).'%)'.($timeDifference?' <em class="'.(($difference == 0)?'remain':(($difference > 0)?'increase':'decrease')).'">'.(($difference > 0)?'+':'').$difference.'%</em>':'');
				}
			}

			if (!array_sum($size))
			{
				$toolTip = '';
			}
			else
			{
				krsort($toolTip);

				$toolTip = implode('<br>
', $toolTip);

				if ($userVisits)
				{
					foreach ($userVisits as $visit => $visitTime)
					{
						if (!isset($visitTime['first']))
						{
							continue;
						}

						switch ($period)
						{
							case 'hours':
								if (date('G', $visitTime['first']) == $unitID)
								{
									++$yourVisits;
								}
							break;
							case 'weekdays':
								if (date('w', $visitTime['first']) == $unitID)
								{
									++$yourVisits;
								}
							break;
							default:
								if (!$nextTimeStamp)
								{
									if ($information['step'])
									{
										$nextTimestamp = ($timestamp + $information['step']);
									}
									else if ($period == 'year')
									{
										$nextTimeStamp = ($timestamp +(date('t', $timestamp) * 86400));
									}
									else if ($period == 'years')
									{
										$nextTimeStamp = ($timestamp +((date('L', $timestamp) + 365) * 86400));
									}
								}

								if ($visitTime['first'] >= $timestamp && $visitTime['first'] < $nextTimeStamp)
								{
									++$yourVisits;
								}
							break;
						}
					}
				}
			}

			$chart.= EstatsTheme::parse(EstatsTheme::get('chart-bars-container'), array(
	'class' => 'bars_'.$typeAmounts,
	'width' => $barWidth,
	'id' => 'bars_'.$iD.'_'.$i,
	'action' => ($action?str_replace('{date}', $date, $action):''),
	'bars' => (($type != 'html')?'<div class="empty"></div>
':$bars),
	'tooltip' => ($toolTip?'<span class="tooltip">
<strong>'.ucfirst($toolTipDate).':</strong><br>
'.$toolTip.($yourVisits?'<br>
'.EstatsLocale::translate('Your visits').': '.EstatsGUI::formatNumber($yourVisits).' ('.((isset($summary['sum']['unique']) && $summary['sum']['unique'])?round((($yourVisits / $summary['sum']['unique']) * 100), 1):0).'%)<br>
':'').'</span>
':' ')
	));
		}

		$levels = $scale = '';

		if ($summary['maxall'])
		{
			if (!EstatsTheme::option('ChartSimple') && $typeAmounts <= 3)
			{
				for ($i = 0; $i < $typeAmounts; ++$i)
				{
					for ($j = 0; $j < 3; ++$j)
					{
						if (!$summary[$levelTypes[$j]][$summary['types'][$i]])
						{
							continue;
						}

						$levels.= '<hr id="level_'.$iD.'_'.$levelTypes[$j].'_'.$i.'" class="'.$levelTypes[$j].'" style="margin-top:-'.(int)((($summary[$levelTypes[$j]][$summary['types'][$i]] / $summary['maxall']) * 150) + 2).'px;border-color:#'.$colours[$i].';" title="'.EstatsLocale::translate(ucfirst($summary['types'][$i])).' - '.$levelNames[$j].': '.round($summary[$levelTypes[$j]][$summary['types'][$i]], 2).'">
	';
					}
				}
			}

			for ($i = 10; $i > 0; $i--)
			{
				$scale.= EstatsGUI::formatNumber(($summary['maxall'] * $i) / 10).'
';
			}

			$scale.= '<em>0</em>';
		}
		else
		{
			$scale = str_repeat('
', 12);
		}

		$chartArea.= '<tr>
'.$chart.'<td class="scale" style="">
<pre>'.$scale.'</pre>
</td>
</tr>
<tr>
<td colspan="'.$information['amount'].'" class="levels">
'.$levels.'</td>
</tr>
<tr>
'.$descriptions.'<th>'.(($summary['maxall'] && !EstatsTheme::option('ChartSimple') && $typeAmounts <= 3)?'<input type="checkbox" id="levels_switch_'.$period.'" onclick="levelsShowHide(\''.$period.'\')"'.((!isset($_COOKIE['estats_time_levels_chart_'.$period]) || $_COOKIE['estats_time_levels_chart_'.$period] != 'true')?' checked="checked"':'').' title="'.EstatsLocale::translate('Show / hide levels of maximum, average and minimum').'">':'&nbsp;').'</th>
</tr>
';
		$summaryTable = '';

		for ($i = 0; $i < $typeAmounts; ++$i)
		{
			$keys = array('sum', 'maximum', 'average', 'minimum');
			$text = EstatsGUI::itemText($summary['types'][$i], $category);
			$icon = EstatsGUI::iconPath($summary['types'][$i], $category);
			$themeArray = array(
	'text' => EstatsGUI::cutString($text, EstatsTheme::option('ChartRowValueLength'), TRUE),
	'number' => $i,
	'colour' => '#'.$colours[$i],
	'icon' =>($icon?'
'.EstatsGUI::iconTag($icon, $text).'
':''),
	);
			for ($j = 0; $j < 4; ++$j)
			{
				$themeArray[$keys[$j]] = EstatsGUI::formatNumber($summary[$keys[$j]][$summary['types'][$i]]);

				if ($timeDifference)
				{
					$difference = EstatsGUI::formatDifference($summary[$keys[$j]][$summary['types'][$i]], $summary[$keys[$j].'_before'][$summary['types'][$i]]);
					$themeArray[$keys[$j].'_difference'] = (($difference > 0)?'+':'').$difference.'%';
					$themeArray[$keys[$j].'_class'] = (($difference == 0)?'remain':(($difference > 0)?'increase':'decrease'));
				}
			}

			$summaryTable.= EstatsTheme::parse(EstatsTheme::get('chart-summary-row'), $themeArray, array('time_difference' => $timeDifference));
		}

	return EstatsTheme::parse(EstatsTheme::get('chart'), array(
	'chart' => &$chartArea,
	'footer' => &$chartFooter,
	'summary' => EstatsTheme::parse(EstatsTheme::get('chart-summary'), array(
		'rows' => &$summaryTable,
		'id' => &$period,
		)),
	'id' => $period,
	'class' => (in_array($period, array('24hours', 'month', 'hours'))?' narrow':'').(($type != 'html')?'':' plain').($action?' actions':''),
	'style' => (($type != 'html')?' style="background:url({path}image{suffix}{separator}id='.$iD.') no-repeat left top;"':''),
	'title' => $title,
	'colspan' => ($information['amount'] + 1),
	'cacheinformation' => ($cacheTime?EstatsGUI::notificationWidget(sprintf(EstatsLocale::translate('Data from <em>cache</em>, refreshed: %s.'), date('d.m.Y H:i:s', $cacheTime)), 'information'):''),
	'switch' => (EstatsTheme::option('ChartSimple')?'':'<script type="text/javascript">
levelsShowHide(\''.$period.'\');
</script>
'),
	'lang_summary' => EstatsLocale::translate('Summary'),
	));
	}
}
?>