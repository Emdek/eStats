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

	static function information($Period, $Date, $CurrentTime)
	{
		$Data = array();
		$Data['currenttime'] = $CurrentTime;
		$Data['step'] = 0;

		switch ($Period)
		{
			case '24hours':
				if ($CurrentTime)
				{
					$Data['range'] = array((time() - 86400), 0);
				}
				else
				{
					$Data['range'] = EstatsGUI::timeRange($Date[0], $Date[1], $Date[2]);
				}

				$Data['unit'] = 'hour';
				$Data['format'] = 'Y.m.d H';
				$Data['amount'] = 24;
				$Data['step'] = 3600;
			break;
			case 'month':
				if ($CurrentTime)
				{
					$Data['range'] = array(strtotime('last month'), 0);
					$Data['amount'] = date('t', ((date('t') == date('j'))?$_SERVER['REQUEST_TIME']:strtotime('last month')));
				}
				else
				{
					$Data['range'] = EstatsGUI::timeRange($Date[0], $Date[1]);
					$Data['amount'] = date('t', ((date('t', $Data['range'][1]) == date('j', $Data['range'][1]))?$Data['range'][1]:$Data['range'][0]));
				}

				$Data['unit'] = 'day';
				$Data['format'] = 'Y.m.d';
				$Data['step'] = 86400;
			break;
			case 'year':
				if ($CurrentTime)
				{
					$Data['range'] = array(strtotime('last year'), 0);
				}
				else
				{
					$Data['range'] = EstatsGUI::timeRange($Date[0]);
				}

				$Data['unit'] = 'month';
				$Data['format'] = 'Y.m';
				$Data['amount'] = 12;
			break;
			case 'years':
				$YearsRange = (date('Y') - date('Y', EstatsCore::option('CollectedFrom')));
				$LastYears = $YearsRange;

				if ($LastYears > 15)
				{
					$LastYears = 15;
				}
				else if ($LastYears < 5)
				{
					$LastYears = 5;
				}

				if ($CurrentTime)
				{
					$Data['range'] = array(strtotime('-'.$LastYears.' years'), 0);
				}
				else
				{
					$Data['range'] = array(strtotime(($Date[0] - $LastYears + 1).'-01-01'), strtotime(($Date[0] + 1).'-01-01'));
				}

				$Data['unit'] = 'year';
				$Data['format'] = 'Y';
				$Data['amount'] = $LastYears;
			break;
			case 'hours':
				if ($CurrentTime)
				{
					$Data['range'] = array(0, 0);
				}
				else
				{
					$Data['range'] = EstatsGUI::timeRange($Date[0], $Date[1], $Date[2]);
				}

				$Data['unit'] = 'dayhour';
				$Data['format'] = '';
				$Data['amount'] = 24;
				$Data['step'] = 1;
			break;
			case 'weekdays':
				if ($CurrentTime)
				{
					$Data['range'] = array(0, 0);
				}
				else
				{
					$Data['range'] = EstatsGUI::timeRange($Date[0], $Date[1], $Date[2]);
				}

				$Data['unit'] = 'weekday';
				$Data['format'] = 'w';
				$Data['amount'] = 7;
				$Data['step'] = 1;
			break;
		}

		return $Data;
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

	static function summary($Period, $Data, $DataBefore, $Information, $Types, $TimeDifference)
	{
		ksort($Data);

		$Summary = array();
		$TypeAmounts = count($Types);

		for ($i = 0; $i < $TypeAmounts; ++$i)
		{
			$Summary['maximum'][$Types[$i]] = $Summary['minimum'][$Types[$i]] = $Summary['sum'][$Types[$i]] = $Summary['maximum_before'][$Types[$i]] = $Summary['minimum_before'][$Types[$i]] = $Summary['sum_before'][$Types[$i]] = 0;
		}

		$Information['timestamp'] = $Information['range'][0];
		$TimeUnit = array(0, (in_array($Period, array('hours', 'weekdays'))?-1:$Information['timestamp']));

		for ($i = 0; $i < $Information['amount']; ++$i)
		{
			$TimeUnit = EstatsGUI::timeUnit($Period, $TimeUnit[1], $Information['step'], $Information['format'], $Information['currenttime']);

			for ($j = 0; $j < $TypeAmounts; ++$j)
			{
				if (!isset($Data[$TimeUnit[0]][$Types[$j]]))
				{
					continue;
				}

				if ($Data[$TimeUnit[0]][$Types[$j]] > $Summary['maximum'][$Types[$j]])
				{
					$Summary['maximum'][$Types[$j]] = $Data[$TimeUnit[0]][$Types[$j]];
				}

				if (($Data[$TimeUnit[0]][$Types[$j]] < $Summary['minimum'][$Types[$j]]) || !$Summary['minimum'][$Types[$j]])
				{
					$Summary['minimum'][$Types[$j]] = $Data[$TimeUnit[0]][$Types[$j]];
				}

				$Summary['sum'][$Types[$j]] += $Data[$TimeUnit[0]][$Types[$j]];
			}
		}

		if ($TimeDifference)
		{
			foreach ($DataBefore as $Unit => $Array)
			{
				for ($i = 0; $i < $TypeAmounts; ++$i)
				{
					if (!isset($Array[$Types[$i]]))
					{
						continue;
					}

					if ($Array[$Types[$i]] > $Summary['maximum_before'][$Types[$i]])
					{
						$Summary['maximum_before'][$Types[$i]] = $Array[$Types[$i]];
					}

					if (($Array[$Types[$i]] < $Summary['minimum_before'][$Types[$i]]) || !$Summary['minimum_before'][$Types[$i]])
					{
						$Summary['minimum_before'][$Types[$i]] = $Array[$Types[$i]];
					}

					$Summary['sum_before'][$Types[$i]] += $Array[$Types[$i]];
				}
			}
		}

		for ($i = 0; $i < $TypeAmounts; ++$i)
		{
			$Summary['average'][$Types[$i]] = ($Summary['sum'][$Types[$i]] / $Information['amount']);

			if ($TimeDifference)
			{
				$Summary['average_before'][$Types[$i]] = ($Summary['sum_before'][$Types[$i]] / $Information['amount']);
			}
		}

		krsort($Summary['sum']);

		$Summary['maxall'] = max($Summary['maximum']);

		if (count($Data) != $Information['amount'])
		{
			for ($i = 0; $i < $TypeAmounts; ++$i)
			{
				$Summary['minimum'][$Types[$i]] = 0;
			}
		}

		if ($TimeDifference && count($DataBefore) != $Information['amount'])
		{
			for ($i = 0; $i < $TypeAmounts; ++$i)
			{
				$Summary['minimum_before'][$Types[$i]] = 0;
			}
		}

		return $Summary;
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

	static function create($Period, $Type, $Category, $ID, $Action, $Information, $Data, $DataBefore, $Summary, $Title, $CurrentTime, $TimeDifference, $UserVisits = NULL, $CacheTime = 0)
	{
		$LevelTypes = array('maximum', 'average', 'minimum');
		$LevelNames = array(EstatsLocale::translate('maximum'), EstatsLocale::translate('average'), EstatsLocale::translate('minimum'));
		$TypeAmounts = count($Summary['types']);
		$MaxValues = $MinValues = array();
		$Popularity = in_array($Period, array('hours', 'weekdays'));
		$Timestamp = $Information['range'][0];

		if ($TimeDifference)
		{
			$TimestampBefore = ($Information['range'][0] - ($Information['range'][1] - $Information['range'][0]));
		}

		$ChartArea = $Descriptions = $Chart = '';
		$ChartFooter = '<tr>
<th colspan="{colspan}">
&nbsp;
</th>
</tr>
';
		switch ($Period)
		{
			case '24hours':
				if (!$CurrentTime || date('G') == 23)
				{
					break;
				}

				$Yesterday = strtotime('yesterday');
				$ChartFooter = '<tr class="footer">
<th colspan="'.(23 - date('G')).'" title="'.ucfirst(strftime('%A', $Yesterday)).'">
'.(in_array(date('w', $Yesterday), array(0, 6))?'<em>'.strtoupper(strftime('%a', $Yesterday)).'</em>':strtoupper(strftime('%a', $Yesterday))).'
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
				if (!$CurrentTime || date('t') == date('j'))
				{
					break;
				}

				$Month = ((date('n') - 1)?(date('n') - 1):12);
				$ChartFooter = '<tr class="footer">
<th colspan="'.(date('t', strtotime('last month')) - date('j')).'" title="'.ucfirst(strftime('%B', mktime(0, 0, 0, $Month))).'">
'.strtoupper(strftime('%b', mktime(0, 0, 0, $Month))).'
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
				$Timestamp = strtotime(date('Y-m-01', $Timestamp));

				if ($TimeDifference)
				{
					$TimestampBefore = strtotime(date('Y-m-01', $TimestampBefore));
				}

				if (!$CurrentTime || date('n') == 12)
				{
					break;
				}

				$ChartFooter = '<tr class="footer">
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
				$Timestamp = strtotime(date('Y-01-01', $Timestamp));

				if ($TimeDifference)
				{
					$TimestampBefore = strtotime(date('Y-01-01', $TimestampBefore));
				}
			break;
			case 'hours':
			case 'weekdays':
				$Timestamp = -1;
			break;
		}

		$Colours = explode('|', EstatsTheme::option('ChartTimeColours'));
		$BarWidth = round(700 / $Information['amount']);
		$TimeUnit = array(0, $Timestamp);
		$UnitID = $TimeUnit[0];
		$Timestamp = $TimeUnit[1];

		if ($TimeDifference)
		{
			$TimeUnitBefore = array(0, $TimestampBefore);
		}

		for ($i = 0; $i < $Information['amount']; ++$i)
		{
			$ColumnTitle = $Description = $Bars = '';
			$NextTimeStamp = $YourVisits = 0;

			if ($CurrentTime || $Popularity)
			{
				$TimeUnit = EstatsGUI::timeUnit($Period, $TimeUnit[1], $Information['step'], $Information['format'], $Information['currenttime']);
				$UnitID = $TimeUnit[0];
				$Timestamp = $TimeUnit[1];
			}

			switch ($Period)
			{
				case '24hours':
					$Description = date('H', $Timestamp);
					$ToolTipDate = date('Y.m.d H:00');
				break;
				case 'month':
					$WeekDay = date('w', $Timestamp);
					$ColumnTitle = strftime('%A', $Timestamp);
					$Description = date('d', $Timestamp);

					if ($WeekDay == 0 || $WeekDay == 6)
					{
						$Description = '<em>'.$Description.'</em>';
					}

					$ToolTipDate = $ColumnTitle.', '.date('Y.m.d', $Timestamp);
					$ColumnTitle = ucfirst($ColumnTitle);
				break;
				case 'year':
					$DateID = date('n', $Timestamp);
					$Description = strtoupper(strftime('%b', mktime(0, 0, 0, $DateID)));
					$ColumnTitle = strftime('%B', mktime(0, 0, 0, $DateID));
					$ToolTipDate = $ColumnTitle.' '.date('Y', $Timestamp);
					$ColumnTitle = ucfirst($ColumnTitle);
				break;
				case 'years':
					$ToolTipDate = $Description = $UnitID;
				break;
				case 'hours':
					$ToolTipDate = $Description = (($UnitID === 0)?'0':'').$UnitID;
				break;
				case 'weekdays':
					$Timestamp = (94694400 + (($UnitID - 1) * 86400));
					$ToolTipDate = strftime('%A', $Timestamp);
					$ColumnTitle = ucfirst($ToolTipDate);
					$Description = strtoupper(strftime('%a', $Timestamp));

					if ($UnitID == 0 || $UnitID == 6)
					{
						$Description = '<em>'.$Description.'</em>';
					}
				break;
			}

			if ($Action)
			{
				switch ($Period)
				{
					case 'month':
						$Date = date('Y-n-j-0', $Timestamp);
					break;
					case 'year':
						$Date = date('Y-n-0-0', $Timestamp);
					break;
					case 'years':
						$Date = date('Y-0-0-0', $Timestamp);
					break;
				}
			}

			if (!$CurrentTime && !$Popularity)
			{
				$TimeUnit = EstatsGUI::timeUnit($Period, $TimeUnit[1], $Information['step'], $Information['format'], $Information['currenttime']);
				$UnitID = $TimeUnit[0];
				$Timestamp = $TimeUnit[1];
			}

			if ($TimeDifference)
			{
				if ($Popularity)
				{
					$TimeUnitBefore = &$TimeUnit;
					$UnitIDBefore = &$UnitID;
					$TimestampBefore = &$Timestamp;
				}
				else
				{
					$TimeUnitBefore = EstatsGUI::timeUnit($Period, $TimeUnitBefore[1], $Information['step'], $Information['format'], 0);
					$UnitIDBefore = $TimeUnitBefore[0];
					$TimestampBefore = $TimeUnitBefore[1];
				}
			}

			$Descriptions.= '<th'.($ColumnTitle?' title="'.$ColumnTitle.'"':'').'>'.$Description.'</th>
';
			$Size = $ToolTip = array();

			for ($j = 0; $j < $TypeAmounts; ++$j)
			{
				if (isset($Data[$UnitID][$Summary['types'][$j]]) && $Data[$UnitID][$Summary['types'][$j]] == $Summary['maximum'][$Summary['types'][$j]])
				{
					$MaxValues[$Summary['types'][$j]][] = $i;
				}

				if (!isset($Data[$UnitID][$Summary['types'][$j]]) || $Data[$UnitID][$Summary['types'][$j]] == $Summary['minimum'][$Summary['types'][$j]])
				{
					$MinValues[$Summary['types'][$j]][] = $i;
				}

				$Size[$Summary['types'][$j]] = ((isset($Data[$UnitID][$Summary['types'][$j]]) && $Data[$UnitID][$Summary['types'][$j]])?(($Data[$UnitID][$Summary['types'][$j]] / $Summary['maxall']) * 150):0);
				$Maximum = (isset($Data[$UnitID][$Summary['types'][$j]]) && $Data[$UnitID][$Summary['types'][$j]] == $Summary['maximum'][$Summary['types'][$j]] && $Summary['maximum'][$Summary['types'][$j]]);

				if ($Type == 'html')
				{
					$Height = $Size[$Summary['types'][$j]];

					if (!$Height)
					{
						$Bars.= '<div class="empty"></div>
	';
					}
					else
					{
						$Bars.= EstatsTheme::parse(EstatsTheme::get('chart-bar'), array(
	'height' => round($Height),
	'margin' => round(150 - $Size[$Summary['types'][$j]]),
	'colour' => '#'.$Colours[$j],
	'id' => 'bar_'.$ID.'_'.$i.'_'.$j,
	'class' => ($Maximum?'maximum':(($Data[$UnitID][$Summary['types'][$j]] == $Summary['minimum'][$Summary['types'][$j]])?'minimum':'')),
	'title' => EstatsGUI::itemText($Summary['types'][$j], $Category).': '.$Data[$UnitID][$Summary['types'][$j]],
	'simplebar' => (EstatsTheme::option('ChartSimple')?str_repeat(' <br />
', (int) (($Height / 150) * 10)):'')
	));
					}
				}

				if (!EstatsTheme::option('ChartSimple'))
				{
					$Number = (isset($Data[$UnitID][$Summary['types'][$j]])?$Data[$UnitID][$Summary['types'][$j]]:0);

					if (!$Number)
					{
						continue;
					}

					$Sum = $Summary['sum'][$Summary['types'][$j]];

					if ($TimeDifference)
					{
						$Difference = EstatsGUI::formatDifference((isset($Data[$UnitID][$Summary['types'][$j]])?$Data[$UnitID][$Summary['types'][$j]]:0), (isset($DataBefore[$UnitIDBefore][$Summary['types'][$j]])?$DataBefore[$UnitIDBefore][$Summary['types'][$j]]:0));
					}

					$ToolTip[$Number.'.'.$j] = EstatsGUI::itemText($Summary['types'][$j], $Category).': '.($Maximum?'<strong>':'').$Number.($Maximum?'</strong>':'').' ('.($Sum?round((($Number / $Sum) * 100), 1):0).'%)'.($TimeDifference?' <em class="'.(($Difference == 0)?'remain':(($Difference > 0)?'increase':'decrease')).'">'.(($Difference > 0)?'+':'').$Difference.'%</em>':'');
				}
			}

			if (!array_sum($Size))
			{
				$ToolTip = '';
			}
			else
			{
				krsort($ToolTip);

				$ToolTip = implode('<br />
', $ToolTip);

				if ($UserVisits)
				{
					foreach ($UserVisits as $Visit => $VisitTime)
					{
						if (!isset($VisitTime['first']))
						{
							continue;
						}

						switch ($Period)
						{
							case 'hours':
								if (date('G', $VisitTime['first']) == $UnitID)
								{
									++$YourVisits;
								}
							break;
							case 'weekdays':
								if (date('w', $VisitTime['first']) == $UnitID)
								{
									++$YourVisits;
								}
							break;
							default:
								if (!$NextTimeStamp)
								{
									if ($Information['step'])
									{
										$NextTimestamp = ($Timestamp + $Information['step']);
									}
									else if ($Period == 'year')
									{
										$NextTimeStamp = ($Timestamp +(date('t', $Timestamp) * 86400));
									}
									else if ($Period == 'years')
									{
										$NextTimeStamp = ($Timestamp +((date('L', $Timestamp) + 365) * 86400));
									}
								}

								if ($VisitTime['first'] >= $Timestamp && $VisitTime['first'] < $NextTimeStamp)
								{
									++$YourVisits;
								}
							break;
						}
					}
				}
			}

			$Chart.= EstatsTheme::parse(EstatsTheme::get('chart-bars-container'), array(
	'class' => 'bars_'.$TypeAmounts,
	'width' => $BarWidth,
	'id' => 'bars_'.$ID.'_'.$i,
	'action' => ($Action?str_replace('{date}', $Date, $Action):''),
	'bars' => (($Type != 'html')?'<div class="empty"></div>
':$Bars),
	'tooltip' => ($ToolTip?'<span class="tooltip">
<strong>'.ucfirst($ToolTipDate).':</strong><br />
'.$ToolTip.($YourVisits?'<br />
'.EstatsLocale::translate('Your visits').': '.EstatsGUI::formatNumber($YourVisits).' ('.((isset($Summary['sum']['unique']) && $Summary['sum']['unique'])?round((($YourVisits / $Summary['sum']['unique']) * 100), 1):0).'%)<br />
':'').'</span>
':' ')
	));
		}

		$Levels = $Scale = '';

		if ($Summary['maxall'])
		{
			if (!EstatsTheme::option('ChartSimple') && $TypeAmounts <= 3)
			{
				for ($i = 0; $i < $TypeAmounts; ++$i)
				{
					for ($j = 0; $j < 3; ++$j)
					{
						if (!$Summary[$LevelTypes[$j]][$Summary['types'][$i]])
						{
							continue;
						}

						$Levels.= '<hr id="level_'.$ID.'_'.$LevelTypes[$j].'_'.$i.'" class="'.$LevelTypes[$j].'" style="margin-top:-'.(int)((($Summary[$LevelTypes[$j]][$Summary['types'][$i]] / $Summary['maxall']) * 150) + 2).'px;border-color:#'.$Colours[$i].';" title="'.EstatsLocale::translate(ucfirst($Summary['types'][$i])).' - '.$LevelNames[$j].': '.round($Summary[$LevelTypes[$j]][$Summary['types'][$i]], 2).'" />
	';
					}
				}
			}

			for ($i = 10; $i > 0; $i--)
			{
				$Scale.= EstatsGUI::formatNumber(($Summary['maxall'] * $i) / 10).'
';
			}

			$Scale.= '<em>0</em>';
		}
		else
		{
			$Scale = str_repeat('
', 12);
		}

		$ChartArea.= '<tr>
'.$Chart.'<td class="scale" style="">
<pre>'.$Scale.'</pre>
</td>
</tr>
<tr>
<td colspan="'.$Information['amount'].'" class="levels">
'.$Levels.'</td>
</tr>
<tr>
'.$Descriptions.'<th>'.(($Summary['maxall'] && !EstatsTheme::option('ChartSimple') && $TypeAmounts <= 3)?'<input type="checkbox" id="levels_switch_'.$Period.'" onclick="levelsShowHide(\''.$Period.'\')"'.((!isset($_COOKIE['estats_time_levels_chart_'.$Period]) || $_COOKIE['estats_time_levels_chart_'.$Period] != 'true')?' checked="checked"':'').' title="'.EstatsLocale::translate('Show / hide levels of maximum, average and minimum').'" />':'&nbsp;').'</th>
</tr>
';
		$SummaryTable = '';

		for ($i = 0; $i < $TypeAmounts; ++$i)
		{
			$Keys = array('sum', 'maximum', 'average', 'minimum');
			$Text = EstatsGUI::itemText($Summary['types'][$i], $Category);
			$Icon = EstatsGUI::iconPath($Summary['types'][$i], $Category);
			$ThemeArray = array(
	'text' => EstatsGUI::cutString($Text, EstatsTheme::option('ChartRowValueLength'), TRUE),
	'number' => $i,
	'colour' => '#'.$Colours[$i],
	'icon' =>($Icon?'
'.EstatsGUI::iconTag($Icon, $Text).'
':''),
	);
			for ($j = 0; $j < 4; ++$j)
			{
				$ThemeArray[$Keys[$j]] = EstatsGUI::formatNumber($Summary[$Keys[$j]][$Summary['types'][$i]]);

				if ($TimeDifference)
				{
					$Difference = EstatsGUI::formatDifference($Summary[$Keys[$j]][$Summary['types'][$i]], $Summary[$Keys[$j].'_before'][$Summary['types'][$i]]);
					$ThemeArray[$Keys[$j].'_difference'] = (($Difference > 0)?'+':'').$Difference.'%';
					$ThemeArray[$Keys[$j].'_class'] = (($Difference == 0)?'remain':(($Difference > 0)?'increase':'decrease'));
				}
			}

			$SummaryTable.= EstatsTheme::parse(EstatsTheme::get('chart-summary-row'), $ThemeArray, array('time_difference' => $TimeDifference));
		}

	return EstatsTheme::parse(EstatsTheme::get('chart'), array(
	'chart' => &$ChartArea,
	'footer' => &$ChartFooter,
	'summary' => EstatsTheme::parse(EstatsTheme::get('chart-summary'), array(
		'rows' => &$SummaryTable,
		'id' => &$Period,
		)),
	'id' => $Period,
	'class' => (in_array($Period, array('24hours', 'month', 'hours'))?' narrow':'').(($Type != 'html')?'':' plain').($Action?' actions':''),
	'style' => (($Type != 'html')?' style="background:url({path}image{suffix}{separator}id='.$ID.') no-repeat left top;"':''),
	'title' => $Title,
	'colspan' => ($Information['amount'] + 1),
	'cacheinformation' => ($CacheTime?EstatsGUI::notificationWidget(sprintf(EstatsLocale::translate('Data from <em>cache</em>, refreshed: %s.'), date('d.m.Y H:i:s', $CacheTime)), 'information'):''),
	'switch' => (EstatsTheme::option('ChartSimple')?'':'<script type="text/javascript">
levelsShowHide(\''.$Period.'\');
</script>
'),
	'lang_summary' => EstatsLocale::translate('Summary'),
	));
	}
}
?>