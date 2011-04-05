<?php
/**
 * Groups class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

class EstatsGroup
{

/**
 * Returns group data
 * @param string Group
 * @param integer Amount
 * @param integer Offset
 * @param integer From
 * @param integer To
 * @return array
 */

	static function selectData($Group, $Amount, $Offset, $From = 0, $To = 0)
	{
		$Data = array();
		$FetchBefore = ($From && $To);
		$WhereCurrent = EstatsCore::timeClause('time', $From, $To);
		$WhereBefore = ($FetchBefore?EstatsCore::timeClause('time', ($From - ($To - $From)), $From):array());
		$FieldSum = array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount'), 'sum');

		if ($WhereBefore)
		{
			$WhereBefore[] = EstatsDriver::OPERATOR_AND;
		}

		if ($Group == 'browser-versions')
		{
			$Table = 'browsers';
		}
		else if ($Group == 'operatingsystems' || $Group == 'operatingsystem-versions')
		{
			$Table = 'oses';
		}
		else if ($Group == 'cities' || $Group == 'countries' || $Group == 'continents' || $Group == 'world' || substr($Group, 0, 7) == 'country' || substr($Group, 0, 6) == 'cities' || substr($Group, 0, 7) == 'regions')
		{
			$Table = 'geoip';
		}
		else
		{
			$Table = $Group;
		}

		if ($Group == 'browser-versions' || $Group == 'operatingsystem-versions')
		{
			$Fields = array($FieldSum, array(EstatsDriver::ELEMENT_CONCATENATION, array('name', array(EstatsDriver::ELEMENT_VALUE, ' '), 'version'), 'name'));

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.version', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.version')), EstatsDriver::OPERATOR_AND, array($Table.'2.name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.name')))))), 'before');
			}

			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('version', 'name'));
		}
		else if ($Group == 'sites')
		{
			$Fields = array($FieldSum, 'name', 'address');

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.address', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.address')))))), 'before');
			}

			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('address', 'name'));

			for ($i = 0, $c = count($Result); $i < $c; ++$i)
			{
				$Data[] = array(
	'amount_current' => $Result[$i]['sum'],
	'amount_before' => ($FetchBefore?$Result[$i]['before']:0),
	'name' => ($Result[$i]['name']?$Result[$i]['name']:$Result[$i]['address']),
	'address' => $Result[$i]['address']
	);
			}
		}
		else if (substr($Group, 0, 6) == 'cities')
		{
			$Fields = array($FieldSum, ((strlen($Group) < 7)?array(EstatsDriver::ELEMENT_CONCATENATION, array('city', array(EstatsDriver::ELEMENT_VALUE, '-'), 'country'), 'city'):'city'), 'latitude', 'longitude');

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.latitude', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.latitude')), EstatsDriver::OPERATOR_AND, array($Table.'2.longitude', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.longitude')))))), 'before');
			}

			if ($WhereCurrent)
			{
				$WhereCurrent[] = EstatsDriver::OPERATOR_AND;
			}

			$WhereCurrent[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($Group[6]) && $Group[6] == '-')
			{
				$WhereCurrent[] = EstatsDriver::OPERATOR_AND;
				$WhereCurrent[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 7)));
			}

			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('city'));

			for ($i = 0, $c = count($Result); $i < $c; ++$i)
			{
				$Data[] = array(
	'amount_current' => $Result[$i]['sum'],
	'amount_before' => ($FetchBefore?$Result[$i]['before']:0),
	'name' => $Result[$i]['city'],
	'latitude' => $Result[$i]['latitude'],
	'longitude' => $Result[$i]['longitude']
	);
			}

			$Group = 'cities';
		}
		else if (substr ($Group, 0, 7) == 'regions')
		{
			$Fields = array($FieldSum, array(EstatsDriver::ELEMENT_CONCATENATION, array('country', array(EstatsDriver::ELEMENT_VALUE, '-'), 'region'), 'name'));

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.country', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.country')), EstatsDriver::OPERATOR_AND, array($Table.'2.region', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.region')))))), 'before');
			}

			if ($WhereCurrent)
			{
				$WhereCurrent[] = EstatsDriver::OPERATOR_AND;
			}

			$WhereCurrent[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 8)));
			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('name'));
		}
		else if ($Group == 'continents')
		{
			$Fields = array($FieldSum, array(EstatsDriver::ELEMENT_FIELD, 'continent', 'name'));

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.continent', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.continent')))))), 'before');
			}

			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('name'));
		}
		else if ($Group == 'countries')
		{
			$Fields = array($FieldSum, 'country', 'continent');

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.country', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.country')))))), 'before');
			}

			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('country', 'continent'));

			for ($i = 0, $c = count($Result); $i < $c; ++$i)
			{
				$Data[] = array(
	'amount_current' => $Result[$i]['sum'],
	'amount_before' => ($FetchBefore?$Result[$i]['before']:0),
	'name' => $Result[$i]['country'],
	'continent' => $Result[$i]['continent']
	);
			}
		}
		else
		{
			$Fields = array($FieldSum, 'name');

			if ($FetchBefore)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($Table, $Table.'2')), array($FieldSum), array_merge($WhereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($Table.'2.name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $Table.'.name')))))), 'before');
			}

			$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $WhereCurrent, $Amount, $Offset, array('sum' => FALSE), array('name'));
		}

		if ($Group != 'countries' && $Group != 'cities' && $Group != 'sites')
		{
			for ($i = 0, $c = count($Result); $i < $c; ++$i)
			{
				$Data[] = array(
	'amount_current' => $Result[$i]['sum'],
	'amount_before' => ($FetchBefore?$Result[$i]['before']:0),
	'name' => $Result[$i]['name']
	);
			}
		}

		return $Data;
	}

/**
 * Returns group data for period
 * @param string Group
 * @param integer From
 * @param integer To
 * @param string Unit
 * @return array
 */

	static function selectDataPeriod($Group, $From = 0, $To = 0, $Unit = 'day')
	{
		$Data = array();
	$Units = array(
	'hour' => '%Y.%m.%d %H',
	'day' => '%Y.%m.%d',
	'month' => '%Y.%m',
	'year' => '%Y'
	);

		$Where = EstatsCore::timeClause('time', $From, $To);

		if ($Group == 'browser-versions')
		{
			$Table = 'browsers';
		}
		else if ($Group == 'operatingsystems' || $Group == 'operatingsystem-versions')
		{
			$Table = 'oses';
		}
		else if ($Group == 'cities' || $Group == 'countries' || $Group == 'continents' || $Group == 'world' || substr($Group, 0, 7) == 'country' || substr($Group, 0, 6) == 'cities' || substr($Group, 0, 7) == 'regions')
		{
			$Table = 'geoip';
		}
		else
		{
			$Table = $Group;
		}

		$Fields = array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $Units[$Unit])), 'unit'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount'), 'sum'));
		$GroupBy = array('unit', 'title');

		if ($Group == 'browser-versions' || $Group == 'operatingsystem-versions')
		{
			$Fields[] = array(EstatsDriver::ELEMENT_CONCATENATION, array('name', array(EstatsDriver::ELEMENT_VALUE, ' '), 'version'), 'title');
		}
		else if ($Group == 'sites')
		{
			$Fields[] = array(EstatsDriver::ELEMENT_CASE, array(array(array(EstatsDriver::ELEMENT_OPERATION, array('name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, ''))), 'address'), array('name')), 'title');
		}
		else if (substr($Group, 0, 6) == 'cities')
		{
			if (strlen($Group) < 7)
			{
				$Fields[] = array(EstatsDriver::ELEMENT_CONCATENATION, array('city', array(EstatsDriver::ELEMENT_VALUE, '-'), 'country'), 'title');
			}
			else
			{
				$Fields[] = array(EstatsDriver::ELEMENT_FIELD, 'city', 'title');
			}

			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($Group[6]) && $Group[6] == '-')
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
				$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 7)));
			}

			$GroupBy = array(array(EstatsDriver::ELEMENT_CONCATENATION, array('longitude', array(EstatsDriver::ELEMENT_VALUE, '-'), 'latitude')));
		}
		else if (substr($Group, 0, 7) == 'regions')
		{
			$Fields[] = array(EstatsDriver::ELEMENT_CONCATENATION, array(substr($Group, 8), array(EstatsDriver::ELEMENT_VALUE, '-'), 'region'), 'title');

			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 8)));
		}
		else if ($Group == 'countries')
		{
			$Fields[] = array(EstatsDriver::ELEMENT_FIELD, 'country', 'title');
		}
		else if ($Group == 'continents')
		{
			$Fields[] = array(EstatsDriver::ELEMENT_FIELD, 'continent', 'title');
		}
		else
		{
			$Fields[] = array(EstatsDriver::ELEMENT_FIELD, 'name', 'title');
		}

		$Result = EstatsCore::driver()->selectData(array($Table), $Fields, $Where, 0, 0, array('sum' => FALSE), $GroupBy);

		for ($i = 0, $c = count($Result); $i < $c; ++$i)
		{
			$Data[$Result[$i]['unit']][$Result[$i]['title']] = $Result[$i]['sum'];
		}

		return $Data;
	}

/**
 * Returns amount of group data
 * @param string Group
 * @param integer Amount
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function selectAmount($Group, $Amount, $From = 0, $To = 0)
	{
		$Where = EstatsCore::timeClause('time', $From, $To);
		$Field = 'name';

		if ($Group == 'browser-versions')
		{
			$Table = 'browsers';
		}
		else if ($Group == 'operatingsystems' || $Group == 'operatingsystem-versions')
		{
			$Table = 'oses';
		}
		else if ($Group == 'cities' || $Group == 'countries' || $Group == 'continents' || $Group == 'world' || substr($Group, 0, 7) == 'country' || substr($Group, 0, 6) == 'cities' || substr($Group, 0, 7) == 'regions')
		{
			$Table = 'geoip';
		}
		else
		{
			$Table = $Group;
		}

		if ($Group == 'browser-versions' || $Group == 'operatingsystem-versions')
		{
			$Field = array(EstatsDriver::ELEMENT_CONCATENATION, array('name', array(EstatsDriver::ELEMENT_VALUE, '-'), 'version'));
		}
		else if ($Group == 'sites')
		{
			$Field = 'address';
		}
		else if (substr($Group, 0, 6) == 'cities')
		{
			$Field = array(EstatsDriver::ELEMENT_CONCATENATION, array('longitude', array(EstatsDriver::ELEMENT_VALUE, '-'), 'latitude'));

			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($Group[6]) && $Group[6] == '-')
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
				$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 7)));
			}
		}
		else if (substr($Group, 0, 7) == 'regions')
		{
			$Field = 'region';

			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 8)));
		}
		else if ($Group == 'countries')
		{
			$Field = 'country';
		}
		else if ($Group == 'continents')
		{
			$Field = 'continent';
		}

		return count(EstatsCore::driver()->selectData(array($Table), array($Field), $Where, 0, 0, NULL, NULL, NULL, TRUE));
	}

/**
 * Returns sum of group data
 * @param string Group
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function selectSum($Group, $From = 0, $To = 0)
	{
		if ($From)
		{
			$Where = array(array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_GREATEROREQUAL, date('Y-m-d H:i:s', $From))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_LESSOREQUAL, date('Y-m-d H:i:s', $To))));
		}
		else
		{
			$Where = array();
		}

		if ($Group == 'browser-versions')
		{
			$Table = 'browsers';
		}
		else if ($Group == 'operatingsystems' || $Group == 'operatingsystem-versions')
		{
			$Table = 'oses';
		}
		else if ($Group == 'cities' || $Group == 'countries' || $Group == 'continents' || $Group == 'world' || substr($Group, 0, 7) == 'country' || substr($Group, 0, 6) == 'cities' || substr($Group, 0, 7) == 'regions')
		{
			$Table = 'geoip';
		}
		else
		{
			$Table = $Group;
		}

		if (substr($Group, 0, 6) == 'cities')
		{
			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($Group[6]) && $Group[6] == '-')
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
				$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 7)));
			}
		}
		else if (substr($Group, 0, 7) == 'regions')
		{
			if ($Where)
			{
				$Where[] = EstatsDriver::OPERATOR_AND;
			}

			$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($Group, 8)));
		}

		return EstatsCore::driver()->selectField($Table, array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount')), $Where);
	}

/**
 * Processes and returns group information
 * @param string ID
 * @param string Group
 * @param string Title
 * @param array Date
 * @param integer Page
 * @param boolean Extended
 * @param string Link
 * @return string
 */

	static function create($ID, $Group, $Title, $Date, $Page = 1, $Extended = FALSE, $Link = '')
	{
		$Range = EstatsGUI::timeRange($Date[0], $Date[1], $Date[2], $Date[3]);
		$ThisYear = date('Y');

		if ($Date[1] == date('n') && $Date[0] == $ThisYear)
		{
			$Suffix = 'current-month';
		}
		else if ($Date[0] == $ThisYear && !$Date[1])
		{
			$Suffix = 'current-year';
		}
		else if ($Date[0] || $Date[1])
		{
			$Suffix = ($Date[0]?$Date[0]:'').($Date[1]?'-'.$Date[1]:'');
		}
		else
		{
			$Suffix = '';
		}

		$Options = array();
		$Information = '';
		$FileName = $Group.'-'.$Page.($Suffix?'-'.$Suffix:'');

		if (EstatsCache::status($FileName, EstatsCore::option('Cache/others')))
		{
			if (ESTATS_USERLEVEL > 1)
			{
				$Information.= (EstatsCore::option('GroupAmount/'.$ID)?'':EstatsGUI::notificationWidget(EstatsLocale::translate('This group is disabled!'), 'warning')).((!in_array($ID, array('browser-versions', 'operatingsystem-versions', 'cities', 'countries', 'regions', 'continents')) && EstatsCore::option('CollectFrequency/'.$ID) == 'disabled')?EstatsGUI::notificationWidget(EstatsLocale::translate('Data collecting for this group was disabled!'), 'warning'):'');
			}

			$Amount = self::selectAmount($Group, EstatsCore::option('GroupAmount/'.$ID), $Range[0], $Range[1]);
			$PagesAmount = ceil($Amount / EstatsCore::option('GroupAmount/'.$ID));

			if ($Page < 1 || $Page > $PagesAmount)
			{
				$Page = 1;
			}

			$Data = array(
	'data' => self::selectData($Group, EstatsCore::option('GroupAmount/'.$ID), (($Page - 1) * EstatsCore::option('GroupAmount/'.$ID)), $Range[0], $Range[1]),
	'amount' => $Amount,
	'sum_current' => self::selectSum($Group, $Range[0], $Range[1]),
	'sum_before' => ((EstatsCore::option('AmountDifferences') && $Date[0])?self::selectSum($Group, ($Range[0] -($Range[1] - $Range[0])), $Range[0]):0),
	);

			rsort($Data['data']);

			EstatsCache::save($FileName, $Data);
		}
		else
		{
			$Data = EstatsCache::read($FileName);

			if (isset($Data['amount']) && $Data['amount'])
			{
				$PagesAmount = ceil($Data['amount'] / EstatsCore::option('GroupAmount/'.$ID));
				$Information.= EstatsGUI::notificationWidget(sprintf(EstatsLocale::translate('Data from <em>cache</em>, refreshed: %s.'), date('d.m.Y H:i:s', EstatsCache::timestamp($FileName))), 'information');
			}
			else
			{
				$PagesAmount = 0;
			}
		}

		if (!isset($Data['amount']) || !$Data['amount'] || !EstatsCore::option('GroupAmount/'.$ID))
		{
			$Information.= EstatsGUI::notificationWidget(EstatsLocale::translate('No data to display!'), 'error');
		}

		if ($ID == 'countries' || $ID == 'regions')
		{
			$GLOBALS['Data'] = &$Data;
		}
		else if ($ID == 'cities')
		{
			$GLOBALS['Cities'] = &$Data;
		}

		EstatsTheme::add('group_chart', ($Extended && isset($Data['amount']) && $Data['amount'] && EstatsGraphics::isAvailable() && $Page == 1));
		EstatsTheme::add('group_difference', (EstatsCore::option('AmountDifferences') && $Date[0]));

		if ($Extended && isset($Data['amount']) && $Data['amount'])
		{
			if (EstatsGraphics::isAvailable() && $Page == 1)
			{
				EstatsTheme::add('chartidpie', $ID.'-pie');

				$_SESSION[EstatsCore::session()]['imagedata'][$ID.'-pie'] = array(
	'type' => 'chart',
	'chart' => 'pie',
	'diagram' => &$ID,
	'cache' => EstatsCore::option('Cache/others'),
	'data' => &$Data,
	);
			}

			$CurrentTime = 0;

			if ($Date[1])
			{
				$Period = 'month';
				$ChartTitle = EstatsLocale::translate('Month');
				$DatePeriod = array(&$Date[0], &$Date[1], 0);
			}
			else if ($Date[0])
			{
				$Period = 'year';
				$ChartTitle = EstatsLocale::translate('Year');
				$DatePeriod = array(&$Date[0], 0, 0);
			}
			else
			{
				$Period = 'years';
				$ChartTitle = EstatsLocale::translate('Years');
				$DatePeriod = array(0, 0, 0);
				$CurrentTime = 1;
			}

			$ChartInformation = EstatsChart::information($Period, $DatePeriod, $CurrentTime);
			$DataPeriod = self::selectDataPeriod($Group, $ChartInformation['range'][0], $ChartInformation['range'][1], $ChartInformation['unit']);
			$Types = array();
			$Max = 0;

			foreach ($DataPeriod as $Unit)
			{
				foreach ($Unit as $Key => $Value)
				{
					if ($Value > $Max)
					{
						$Max = $Value;
					}

					if (isset($Types[$Key]))
					{
						$Types[$Key] += $Value;
					}
					else
					{
						$Types[$Key] = $Value;
					}
				}
			}

			arsort($Types);

			$i = 0;
			$Tmp = array();

			foreach ($Types as $Key => $Value)
			{
				if (++$i >= 10)
				{
					break;
				}

				if ((($Value / $Max) * 100) < 5)
				{
					continue;
				}

				$Tmp[] = $Key;
			}

			$Types = $Tmp;
			$ChartID = $ID.($DatePeriod[0]?'-'.$DatePeriod[0].'_'.$DatePeriod[1].'_'.$DatePeriod[2]:'').'-time';
			$ChartSummary = array_merge(EstatsChart::summary($Period, $DataPeriod, array(), $ChartInformation, $Types, 0), array(
	'amount' => $ChartInformation['amount'],
	'chart' => $Period,
	'step' => $ChartInformation['step'],
	'timestamp' => $ChartInformation['range'][0],
	'format' => $ChartInformation['format'],
	'currenttime' => $CurrentTime,
	'maxall' => $Max,
	'types' => &$Types,
	));
			$_SESSION[EstatsCore::session()]['imagedata'][$ChartID] = array(
	'type' => 'chart',
	'chart' => 'lines',
	'diagram' => &$ID,
	'data' => &$DataPeriod,
	'summary' => $ChartSummary,
	'cache' => EstatsCore::option('Cache/others'),
	'join' => 0
	);

			EstatsTheme::add('chartidtime', $ChartID);
			EstatsTheme::add('lang_sum', EstatsLocale::translate('Sum'));
			EstatsTheme::add('lang_most', EstatsLocale::translate('Most'));
			EstatsTheme::add('lang_average', EstatsLocale::translate('Average'));
			EstatsTheme::add('lang_least', EstatsLocale::translate('Least'));
			EstatsTheme::add('chart', EstatsChart::create($Period, 'lines', $ID, $ChartID, 'location.href = \''.$Link.'\'', $ChartInformation, $DataPeriod, array(), $ChartSummary, $ChartTitle, $CurrentTime, 0));
		}
		else
		{
			EstatsTheme::add('chart', '');
		}

		$Colours = explode('|', EstatsTheme::option('ChartPieColours'));

		for ($i = 0; $i < 2; ++$i)
		{
			$Colours[$i] = EstatsGraphics::colour($Colours[$i]);
		}

		$Amount = -1;
		$Others = $Number = $j = 0;

		for ($i = 0, $c = count($Data['data']); $i < $c; ++$i)
		{
			$Percent = (($Data['data'][$i]['amount_current'] / $Data['sum_current']) * 100);

			if (++$j <= 20 && ($Percent >= 5 || (!$Others && $j == $c)))
			{
				++$Amount;

				$Number += $Data['data'][$i]['amount_current'];
			}
			else
			{
				++$Others;
			}
		}

		if (($Data['sum_current'] - $Number) > 0)
		{
			++$Amount;
		}

		$Number = 0;

		if ($Extended)
		{
			$ColoursStep = array();

			for ($i = 0; $i < 3; ++$i)
			{
				$ColoursStep[$i] = ($Amount?(($Colours[1][$i] - $Colours[0][$i]) / $Amount):0);
			}
		}

		$Contents = '';

		for ($i = 0, $c = count($Data['data']); $i < $c; ++$i)
		{
			$Row = &$Data['data'][$i];
			$Name = trim($Row['name']);
			$Address = '';

			if ($ID == 'sites')
			{
				$Address = htmlspecialchars($Row['address']);
			}
			else if ($ID == 'websearchers')
			{
				$Address = htmlspecialchars($Name);
			}
			else if ($ID == 'referrers' && $Name && $Name != '?')
			{
				$Address = htmlspecialchars($Name);
			}
			else if ($ID == 'cities' && $Name && $Name != '?')
			{
				$Address = EstatsGUI::mapLink($Row['latitude'], $Row['longitude']);
			}
			else if ($ID == 'countries' && $Name && $Name != '?')
			{
				$Address = '{path}geolocation/'.$Name.'/'.implode('-', $Date).'{suffix}';
			}

			$String = EstatsGUI::itemText($Name, $ID);

			if (ESTATS_USERLEVEL > 1 && ($ID == 'referrers' || $ID == 'keywords') && $Name != '?')
			{
				if ($ID == 'referrers')
				{
					$Referrer = parse_url($Name);
				}

				$AdminOptions = '
<a href="{selfpath}{separator}'.(($ID == 'referrers')?'referrer='.urlencode($Referrer['host']):'keyword='.urlencode($Name)).'" class="red" tabindex="'.EstatsGUI::tabindex().'" title="'.(($ID == 'referrers')?EstatsLocale::translate('Block counting of this referrer'):EstatsLocale::translate('Block counting of this keyword / phrase')).'" onclick="if (!confirm(\''.(($ID == 'referrers')?EstatsLocale::translate('Do you really want to exclude this referrer?'):EstatsLocale::translate('Do you really want to exclude this keyword / phrase?')).'\')) return false">
<strong>&#187;</strong>
</a>';
			}
			else
			{
				$AdminOptions = '';
			}

			$Colour = '#';

			for ($j = 0; $j < 3; ++$j)
			{
				$Colour.= dechex($Colours[0][$j]);
			}

			if ($i < $Amount && $Extended)
			{
				for ($j = 0; $j < 3; ++$j)
				{
					$Colours[0][$j] += $ColoursStep[$j];
				}
			}

			$Colour = strtoupper($Colour);
			$Difference = EstatsGUI::formatDifference($Row['amount_current'], $Row['amount_before']);
			$Icon = EstatsGUI::iconPath($Name, $ID);
			$Contents.= EstatsTheme::parse(EstatsTheme::get('group-row'), array(
	'title' => str_replace('{', '&#123;', htmlspecialchars($String)),
	'number' => (++$Number + (($Page - 1) * EstatsCore::option('GroupAmount/'.$ID))),
	'icon' => ($Icon?EstatsGUI::iconTag($Icon, $String).'
':''),
	'value' => ($Address?'<a href="'.htmlspecialchars($Address).'" tabindex="'.EstatsGUI::tabindex().'" title="'.htmlspecialchars($String).'" rel="nofollow">
':'').str_replace('{', '&#123;', EstatsGUI::cutString($String, EstatsTheme::option('Group'.($Extended?'Single':'').'RowValueLength'))).($Address?'
</a>':'').$AdminOptions,
	'amount' => EstatsGUI::formatNumber($Row['amount_current']),
	'percent' => round((($Row['amount_current'] / $Data['sum_current']) * 100), 2).'%',
	'bar' => ceil(($Row['amount_current'] / $Data['sum_current']) * 100),
	'colour' => $Colour,
	'class' => (($Difference == 0)?'remain':(($Difference > 0)?'increase':'decrease')),
	'difference' => (($Difference > 0)?'+':'').$Difference.'%',
	));
		}

		if ($Data['sum_current'] && EstatsCore::option('GroupAmount/'.$ID))
		{
			$Difference = EstatsGUI::formatDifference($Data['sum_current'], $Data['sum_before']);
			$Summary = EstatsTheme::parse(EstatsTheme::get('group-amount'), array(
	'amount' => EstatsGUI::formatNumber($Data['sum_current']),
	'class' => (($Difference == 0)?'remain':(($Difference > 0)?'increase':'decrease')),
	'difference' => (($Difference > 0)?'+':'').$Difference.'%',
	));
		}
		else
		{
			$Summary = EstatsTheme::get('group-none');
		}

		return EstatsTheme::parse(EstatsTheme::get('group'), array(
	'title' => ((!$Extended && EstatsCore::option('GroupAmount/'.$ID) && $Data['amount'] > EstatsCore::option('GroupAmount/'.$ID))?sprintf(EstatsLocale::translate('%s (%d of %d)'), $Title, (int) ((EstatsCore::option('GroupAmount/'.$ID) > $Data['amount'])?$Data['amount']:EstatsCore::option('GroupAmount/'.$ID)), (int) $Data['amount']):$Title),
	'link' => ($Link?str_replace('{date}', '{period}', $Link):''),
	'links' => (($PagesAmount > 1)?EstatsGUI::linksWidget($Page, $PagesAmount, str_replace('{date}', '{period}/{page}', $Link)):''),
	'tabindex' => EstatsGUI::tabindex(),
	'information' => ($Information?str_replace('{information}', $Information, EstatsTheme::get('group-information')):''),
	'rows' => $Contents,
	'summary' => $Summary,
	'id' => $ID,
	'lang_sum' => EstatsLocale::translate('Sum'),
	'lang_none' => EstatsLocale::translate('None'),
	));
	}
}
?>