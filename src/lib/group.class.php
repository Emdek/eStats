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

	static function selectData($group, $amount, $offset, $from = 0, $to = 0)
	{
		$data = array();
		$fetchBefore = ($from && $to);
		$whereCurrent = EstatsCore::timeClause('time', $from, $to);
		$whereBefore = ($fetchBefore?EstatsCore::timeClause('time', ($from - ($to - $from)), $from):array());
		$fieldSum = array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount'), 'sum');

		if ($whereBefore)
		{
			$whereBefore[] = EstatsDriver::OPERATOR_AND;
		}

		if ($group == 'browser-versions')
		{
			$table = 'browsers';
		}
		else if ($group == 'operatingsystems' || $group == 'operatingsystem-versions')
		{
			$table = 'oses';
		}
		else if ($group == 'cities' || $group == 'countries' || $group == 'continents' || $group == 'world' || substr($group, 0, 7) == 'country' || substr($group, 0, 6) == 'cities' || substr($group, 0, 7) == 'regions')
		{
			$table = 'geoip';
		}
		else
		{
			$table = $group;
		}

		if ($group == 'browser-versions' || $group == 'operatingsystem-versions')
		{
			$fields = array($fieldSum, array(EstatsDriver::ELEMENT_CONCATENATION, array('name', array(EstatsDriver::ELEMENT_VALUE, ' '), 'version'), 'name'));

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.version', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.version')), EstatsDriver::OPERATOR_AND, array($table.'2.name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.name')))))), 'before');
			}

			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('version', 'name'));
		}
		else if ($group == 'sites')
		{
			$fields = array($fieldSum, 'name', 'address');

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.address', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.address')))))), 'before');
			}

			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('address', 'name'));

			for ($i = 0, $c = count($result); $i < $c; ++$i)
			{
				$data[] = array(
	'amount_current' => $result[$i]['sum'],
	'amount_before' => ($fetchBefore?$result[$i]['before']:0),
	'name' => ($result[$i]['name']?$result[$i]['name']:$result[$i]['address']),
	'address' => $result[$i]['address']
	);
			}
		}
		else if (substr($group, 0, 6) == 'cities')
		{
			$fields = array($fieldSum, ((strlen($group) < 7)?array(EstatsDriver::ELEMENT_CONCATENATION, array('city', array(EstatsDriver::ELEMENT_VALUE, '-'), 'country'), 'city'):'city'), 'latitude', 'longitude');

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.latitude', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.latitude')), EstatsDriver::OPERATOR_AND, array($table.'2.longitude', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.longitude')))))), 'before');
			}

			if ($whereCurrent)
			{
				$whereCurrent[] = EstatsDriver::OPERATOR_AND;
			}

			$whereCurrent[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($group[6]) && $group[6] == '-')
			{
				$whereCurrent[] = EstatsDriver::OPERATOR_AND;
				$whereCurrent[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 7)));
			}

			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('city'));

			for ($i = 0, $c = count($result); $i < $c; ++$i)
			{
				$data[] = array(
	'amount_current' => $result[$i]['sum'],
	'amount_before' => ($fetchBefore?$result[$i]['before']:0),
	'name' => $result[$i]['city'],
	'latitude' => $result[$i]['latitude'],
	'longitude' => $result[$i]['longitude']
	);
			}

			$group = 'cities';
		}
		else if (substr ($group, 0, 7) == 'regions')
		{
			$fields = array($fieldSum, array(EstatsDriver::ELEMENT_CONCATENATION, array('country', array(EstatsDriver::ELEMENT_VALUE, '-'), 'region'), 'name'));

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.country', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.country')), EstatsDriver::OPERATOR_AND, array($table.'2.region', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.region')))))), 'before');
			}

			if ($whereCurrent)
			{
				$whereCurrent[] = EstatsDriver::OPERATOR_AND;
			}

			$whereCurrent[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 8)));
			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('name'));
		}
		else if ($group == 'continents')
		{
			$fields = array($fieldSum, array(EstatsDriver::ELEMENT_FIELD, 'continent', 'name'));

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.continent', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.continent')))))), 'before');
			}

			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('name'));
		}
		else if ($group == 'countries')
		{
			$fields = array($fieldSum, 'country', 'continent');

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.country', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.country')))))), 'before');
			}

			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('country', 'continent'));

			for ($i = 0, $c = count($result); $i < $c; ++$i)
			{
				$data[] = array(
	'amount_current' => $result[$i]['sum'],
	'amount_before' => ($fetchBefore?$result[$i]['before']:0),
	'name' => $result[$i]['country'],
	'continent' => $result[$i]['continent']
	);
			}
		}
		else
		{
			$fields = array($fieldSum, 'name');

			if ($fetchBefore)
			{
				$fields[] = array(EstatsDriver::ELEMENT_SUBQUERY, array(array(array($table, $table.'2')), array($fieldSum), array_merge($whereBefore, array(array(EstatsDriver::ELEMENT_OPERATION, array($table.'2.name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_FIELD, $table.'.name')))))), 'before');
			}

			$result = EstatsCore::driver()->selectData(array($table), $fields, $whereCurrent, $amount, $offset, array('sum' => FALSE), array('name'));
		}

		if ($group != 'countries' && $group != 'cities' && $group != 'sites')
		{
			for ($i = 0, $c = count($result); $i < $c; ++$i)
			{
				$data[] = array(
	'amount_current' => $result[$i]['sum'],
	'amount_before' => ($fetchBefore?$result[$i]['before']:0),
	'name' => $result[$i]['name']
	);
			}
		}

		return $data;
	}

/**
 * Returns group data for period
 * @param string Group
 * @param integer From
 * @param integer To
 * @param string Unit
 * @return array
 */

	static function selectDataPeriod($group, $from = 0, $to = 0, $unit = 'day')
	{
		$data = array();
	$units = array(
	'hour' => '%Y.%m.%d %H',
	'day' => '%Y.%m.%d',
	'month' => '%Y.%m',
	'year' => '%Y'
	);

		$where = EstatsCore::timeClause('time', $from, $to);

		if ($group == 'browser-versions')
		{
			$table = 'browsers';
		}
		else if ($group == 'operatingsystems' || $group == 'operatingsystem-versions')
		{
			$table = 'oses';
		}
		else if ($group == 'cities' || $group == 'countries' || $group == 'continents' || $group == 'world' || substr($group, 0, 7) == 'country' || substr($group, 0, 6) == 'cities' || substr($group, 0, 7) == 'regions')
		{
			$table = 'geoip';
		}
		else
		{
			$table = $group;
		}

		$fields = array(array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_DATETIME, array('time', $units[$unit])), 'unit'), array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount'), 'sum'));
		$groupBy = array('unit', 'title');

		if ($group == 'browser-versions' || $group == 'operatingsystem-versions')
		{
			$fields[] = array(EstatsDriver::ELEMENT_CONCATENATION, array('name', array(EstatsDriver::ELEMENT_VALUE, ' '), 'version'), 'title');
		}
		else if ($group == 'sites')
		{
			$fields[] = array(EstatsDriver::ELEMENT_CASE, array(array(array(EstatsDriver::ELEMENT_OPERATION, array('name', EstatsDriver::OPERATOR_EQUAL, array(EstatsDriver::ELEMENT_VALUE, ''))), 'address'), array('name')), 'title');
		}
		else if (substr($group, 0, 6) == 'cities')
		{
			if (strlen($group) < 7)
			{
				$fields[] = array(EstatsDriver::ELEMENT_CONCATENATION, array('city', array(EstatsDriver::ELEMENT_VALUE, '-'), 'country'), 'title');
			}
			else
			{
				$fields[] = array(EstatsDriver::ELEMENT_FIELD, 'city', 'title');
			}

			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($group[6]) && $group[6] == '-')
			{
				$where[] = EstatsDriver::OPERATOR_AND;
				$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 7)));
			}

			$groupBy = array(array(EstatsDriver::ELEMENT_CONCATENATION, array('longitude', array(EstatsDriver::ELEMENT_VALUE, '-'), 'latitude')));
		}
		else if (substr($group, 0, 7) == 'regions')
		{
			$fields[] = array(EstatsDriver::ELEMENT_CONCATENATION, array(substr($group, 8), array(EstatsDriver::ELEMENT_VALUE, '-'), 'region'), 'title');

			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 8)));
		}
		else if ($group == 'countries')
		{
			$fields[] = array(EstatsDriver::ELEMENT_FIELD, 'country', 'title');
		}
		else if ($group == 'continents')
		{
			$fields[] = array(EstatsDriver::ELEMENT_FIELD, 'continent', 'title');
		}
		else
		{
			$fields[] = array(EstatsDriver::ELEMENT_FIELD, 'name', 'title');
		}

		$result = EstatsCore::driver()->selectData(array($table), $fields, $where, 0, 0, array('sum' => FALSE), $groupBy);

		for ($i = 0, $c = count($result); $i < $c; ++$i)
		{
			$data[$result[$i]['unit']][$result[$i]['title']] = $result[$i]['sum'];
		}

		return $data;
	}

/**
 * Returns amount of group data
 * @param string Group
 * @param integer Amount
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function selectAmount($group, $amount, $from = 0, $to = 0)
	{
		$where = EstatsCore::timeClause('time', $from, $to);
		$field = 'name';

		if ($group == 'browser-versions')
		{
			$table = 'browsers';
		}
		else if ($group == 'operatingsystems' || $group == 'operatingsystem-versions')
		{
			$table = 'oses';
		}
		else if ($group == 'cities' || $group == 'countries' || $group == 'continents' || $group == 'world' || substr($group, 0, 7) == 'country' || substr($group, 0, 6) == 'cities' || substr($group, 0, 7) == 'regions')
		{
			$table = 'geoip';
		}
		else
		{
			$table = $group;
		}

		if ($group == 'browser-versions' || $group == 'operatingsystem-versions')
		{
			$field = array(EstatsDriver::ELEMENT_CONCATENATION, array('name', array(EstatsDriver::ELEMENT_VALUE, '-'), 'version'));
		}
		else if ($group == 'sites')
		{
			$field = 'address';
		}
		else if (substr($group, 0, 6) == 'cities')
		{
			$field = array(EstatsDriver::ELEMENT_CONCATENATION, array('longitude', array(EstatsDriver::ELEMENT_VALUE, '-'), 'latitude'));

			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($group[6]) && $group[6] == '-')
			{
				$where[] = EstatsDriver::OPERATOR_AND;
				$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 7)));
			}
		}
		else if (substr($group, 0, 7) == 'regions')
		{
			$field = 'region';

			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 8)));
		}
		else if ($group == 'countries')
		{
			$field = 'country';
		}
		else if ($group == 'continents')
		{
			$field = 'continent';
		}

		return count(EstatsCore::driver()->selectData(array($table), array($field), $where, 0, 0, NULL, NULL, NULL, TRUE));
	}

/**
 * Returns sum of group data
 * @param string Group
 * @param integer From
 * @param integer To
 * @return integer
 */

	static function selectSum($group, $from = 0, $to = 0)
	{
		if ($from)
		{
			$where = array(array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_GREATEROREQUAL, date('Y-m-d H:i:s', $from))), EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_LESSOREQUAL, date('Y-m-d H:i:s', $to))));
		}
		else
		{
			$where = array();
		}

		if ($group == 'browser-versions')
		{
			$table = 'browsers';
		}
		else if ($group == 'operatingsystems' || $group == 'operatingsystem-versions')
		{
			$table = 'oses';
		}
		else if ($group == 'cities' || $group == 'countries' || $group == 'continents' || $group == 'world' || substr($group, 0, 7) == 'country' || substr($group, 0, 6) == 'cities' || substr($group, 0, 7) == 'regions')
		{
			$table = 'geoip';
		}
		else
		{
			$table = $group;
		}

		if (substr($group, 0, 6) == 'cities')
		{
			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('city', (EstatsDriver::OPERATOR_NOT | EstatsDriver::OPERATOR_EQUAL), ''));

			if (isset($group[6]) && $group[6] == '-')
			{
				$where[] = EstatsDriver::OPERATOR_AND;
				$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 7)));
			}
		}
		else if (substr($group, 0, 7) == 'regions')
		{
			if ($where)
			{
				$where[] = EstatsDriver::OPERATOR_AND;
			}

			$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('country', EstatsDriver::OPERATOR_EQUAL, substr($group, 8)));
		}

		return EstatsCore::driver()->selectField($table, array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_SUM, 'amount')), $where);
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

	static function create($iD, $group, $title, $date, $page = 1, $extended = FALSE, $link = '')
	{
		$range = EstatsGUI::timeRange($date[0], $date[1], $date[2], $date[3]);
		$thisYear = date('Y');

		if ($date[1] == date('n') && $date[0] == $thisYear)
		{
			$suffix = 'current-month';
		}
		else if ($date[0] == $thisYear && !$date[1])
		{
			$suffix = 'current-year';
		}
		else if ($date[0] || $date[1])
		{
			$suffix = ($date[0]?$date[0]:'').($date[1]?'-'.$date[1]:'');
		}
		else
		{
			$suffix = '';
		}

		$options = array();
		$information = '';
		$fileName = $group.'-'.$page.($suffix?'-'.$suffix:'');

		if (EstatsCache::status($fileName, EstatsCore::option('Cache/others')))
		{
			if (ESTATS_USERLEVEL > 1 && EstatsCore::option('GroupAmount/'.$iD) == 0)
			{
				$information.= EstatsGUI::notificationWidget(EstatsLocale::translate('This group is disabled!'), 'warning');
			}

			$amount = self::selectAmount($group, EstatsCore::option('GroupAmount/'.$iD), $range[0], $range[1]);
			$pagesAmount = ceil($amount / EstatsCore::option('GroupAmount/'.$iD));

			if ($page < 1 || $page > $pagesAmount)
			{
				$page = 1;
			}

			$data = array(
	'data' => self::selectData($group, EstatsCore::option('GroupAmount/'.$iD), (($page - 1) * EstatsCore::option('GroupAmount/'.$iD)), $range[0], $range[1]),
	'amount' => $amount,
	'sum_current' => self::selectSum($group, $range[0], $range[1]),
	'sum_before' => ((EstatsCore::option('AmountDifferences') && $date[0])?self::selectSum($group, ($range[0] -($range[1] - $range[0])), $range[0]):0),
	);

			rsort($data['data']);

			EstatsCache::save($fileName, $data);
		}
		else
		{
			$data = EstatsCache::read($fileName);

			if (isset($data['amount']) && $data['amount'])
			{
				$pagesAmount = ceil($data['amount'] / EstatsCore::option('GroupAmount/'.$iD));
				$information.= EstatsGUI::notificationWidget(sprintf(EstatsLocale::translate('Data from <em>cache</em>, refreshed: %s.'), date('d.m.Y H:i:s', EstatsCache::timestamp($fileName))), 'information');
			}
			else
			{
				$pagesAmount = 0;
			}
		}

		if (!isset($data['amount']) || !$data['amount'] || !EstatsCore::option('GroupAmount/'.$iD))
		{
			$information.= EstatsGUI::notificationWidget(EstatsLocale::translate('No data to display!'), 'error');
		}

		if ($iD == 'countries' || $iD == 'regions')
		{
			$gLOBALS['Data'] = &$data;
		}
		else if ($iD == 'cities')
		{
			$gLOBALS['Cities'] = &$data;
		}

		EstatsTheme::add('group_chart', ($extended && isset($data['amount']) && $data['amount'] && EstatsGraphics::isAvailable() && $page == 1));
		EstatsTheme::add('group_difference', (EstatsCore::option('AmountDifferences') && $date[0]));

		if ($extended && isset($data['amount']) && $data['amount'])
		{
			if (EstatsGraphics::isAvailable() && $page == 1)
			{
				EstatsTheme::add('chartidpie', $iD.'-pie');

				$_SESSION[EstatsCore::session()]['imagedata'][$iD.'-pie'] = array(
	'type' => 'chart',
	'chart' => 'pie',
	'diagram' => &$iD,
	'cache' => EstatsCore::option('Cache/others'),
	'data' => &$data,
	);
			}

			$currentTime = 0;

			if ($date[1])
			{
				$period = 'month';
				$chartTitle = EstatsLocale::translate('Month');
				$datePeriod = array(&$date[0], &$date[1], 0);
			}
			else if ($date[0])
			{
				$period = 'year';
				$chartTitle = EstatsLocale::translate('Year');
				$datePeriod = array(&$date[0], 0, 0);
			}
			else
			{
				$period = 'years';
				$chartTitle = EstatsLocale::translate('Years');
				$datePeriod = array(0, 0, 0);
				$currentTime = 1;
			}

			$chartInformation = EstatsChart::information($period, $datePeriod, $currentTime);
			$dataPeriod = self::selectDataPeriod($group, $chartInformation['range'][0], $chartInformation['range'][1], $chartInformation['unit']);
			$types = array();
			$max = 0;

			foreach ($dataPeriod as $unit)
			{
				foreach ($unit as $key => $value)
				{
					if ($value > $max)
					{
						$max = $value;
					}

					if (isset($types[$key]))
					{
						$types[$key] += $value;
					}
					else
					{
						$types[$key] = $value;
					}
				}
			}

			arsort($types);

			$i = 0;
			$tmp = array();

			foreach ($types as $key => $value)
			{
				if (++$i >= 10)
				{
					break;
				}

				if ((($value / $max) * 100) < 5)
				{
					continue;
				}

				$tmp[] = $key;
			}

			$types = $tmp;
			$chartID = $iD.($datePeriod[0]?'-'.$datePeriod[0].'_'.$datePeriod[1].'_'.$datePeriod[2]:'').'-time';
			$chartSummary = array_merge(EstatsChart::summary($period, $dataPeriod, array(), $chartInformation, $types, 0), array(
	'amount' => $chartInformation['amount'],
	'chart' => $period,
	'step' => $chartInformation['step'],
	'timestamp' => $chartInformation['range'][0],
	'format' => $chartInformation['format'],
	'currenttime' => $currentTime,
	'maxall' => $max,
	'types' => &$types,
	));
			$_SESSION[EstatsCore::session()]['imagedata'][$chartID] = array(
	'type' => 'chart',
	'chart' => 'lines',
	'diagram' => &$iD,
	'data' => &$dataPeriod,
	'summary' => $chartSummary,
	'cache' => EstatsCore::option('Cache/others'),
	'join' => 0
	);

			EstatsTheme::add('chartidtime', $chartID);
			EstatsTheme::add('lang_sum', EstatsLocale::translate('Sum'));
			EstatsTheme::add('lang_most', EstatsLocale::translate('Most'));
			EstatsTheme::add('lang_average', EstatsLocale::translate('Average'));
			EstatsTheme::add('lang_least', EstatsLocale::translate('Least'));
			EstatsTheme::add('chart', EstatsChart::create($period, 'lines', $iD, $chartID, 'location.href = \''.$link.'\'', $chartInformation, $dataPeriod, array(), $chartSummary, $chartTitle, $currentTime, 0));
		}
		else
		{
			EstatsTheme::add('chart', '');
		}

		$colours = explode('|', EstatsTheme::option('ChartPieColours'));

		for ($i = 0; $i < 2; ++$i)
		{
			$colours[$i] = EstatsGraphics::colour($colours[$i]);
		}

		$amount = -1;
		$others = $number = $j = 0;

		for ($i = 0, $c = count($data['data']); $i < $c; ++$i)
		{
			$percent = (($data['data'][$i]['amount_current'] / $data['sum_current']) * 100);

			if (++$j <= 20 && ($percent >= 5 || (!$others && $j == $c)))
			{
				++$amount;

				$number += $data['data'][$i]['amount_current'];
			}
			else
			{
				++$others;
			}
		}

		if (($data['sum_current'] - $number) > 0)
		{
			++$amount;
		}

		$number = 0;

		if ($extended)
		{
			$coloursStep = array();

			for ($i = 0; $i < 3; ++$i)
			{
				$coloursStep[$i] = ($amount?(($colours[1][$i] - $colours[0][$i]) / $amount):0);
			}
		}

		$contents = '';

		for ($i = 0, $c = count($data['data']); $i < $c; ++$i)
		{
			$row = &$data['data'][$i];
			$name = trim($row['name']);
			$address = '';

			if ($iD == 'sites')
			{
				$address = htmlspecialchars($row['address']);
			}
			else if ($iD == 'websearchers')
			{
				$address = htmlspecialchars($name);
			}
			else if ($iD == 'referrers' && $name && $name != '?')
			{
				$address = htmlspecialchars($name);
			}
			else if ($iD == 'cities' && $name && $name != '?')
			{
				$address = EstatsGUI::mapLink($row['latitude'], $row['longitude']);
			}
			else if ($iD == 'countries' && $name && $name != '?')
			{
				$address = '{path}geolocation/'.$name.'/'.implode('-', $date).'{suffix}';
			}

			$string = EstatsGUI::itemText($name, $iD);

			if (ESTATS_USERLEVEL > 1 && ($iD == 'referrers' || $iD == 'keywords') && $name != '?')
			{
				if ($iD == 'referrers')
				{
					$referrer = parse_url($name);
				}

				$adminOptions = '
<a href="{selfpath}{separator}'.(($iD == 'referrers')?'referrer='.urlencode($referrer['host']):'keyword='.urlencode($name)).'" class="red" title="'.(($iD == 'referrers')?EstatsLocale::translate('Block counting of this referrer'):EstatsLocale::translate('Block counting of this keyword / phrase')).'" onclick="if (!confirm(\''.(($iD == 'referrers')?EstatsLocale::translate('Do you really want to exclude this referrer?'):EstatsLocale::translate('Do you really want to exclude this keyword / phrase?')).'\')) return false">
<strong>&#187;</strong>
</a>';
			}
			else
			{
				$adminOptions = '';
			}

			$colour = '#';

			for ($j = 0; $j < 3; ++$j)
			{
				$colour.= dechex($colours[0][$j]);
			}

			if ($i < $amount && $extended)
			{
				for ($j = 0; $j < 3; ++$j)
				{
					$colours[0][$j] += $coloursStep[$j];
				}
			}

			$colour = strtoupper($colour);
			$difference = EstatsGUI::formatDifference($row['amount_current'], $row['amount_before']);
			$icon = EstatsGUI::iconPath($name, $iD);
			$contents.= EstatsTheme::parse(EstatsTheme::get('group-row'), array(
	'title' => str_replace('{', '&#123;', htmlspecialchars($string)),
	'number' => (++$number + (($page - 1) * EstatsCore::option('GroupAmount/'.$iD))),
	'icon' => ($icon?EstatsGUI::iconTag($icon, $string).'
':''),
	'value' => ($address?'<a href="'.htmlspecialchars($address).'" title="'.htmlspecialchars($string).'" rel="nofollow">
':'').str_replace('{', '&#123;', EstatsGUI::cutString($string, EstatsTheme::option('Group'.($extended?'Single':'').'RowValueLength'))).($address?'
</a>':'').$adminOptions,
	'amount' => EstatsGUI::formatNumber($row['amount_current']),
	'percent' => round((($row['amount_current'] / $data['sum_current']) * 100), 2).'%',
	'bar' => ceil(($row['amount_current'] / $data['sum_current']) * 100),
	'colour' => $colour,
	'class' => (($difference == 0)?'remain':(($difference > 0)?'increase':'decrease')),
	'difference' => (($difference > 0)?'+':'').$difference.'%',
	));
		}

		if ($data['sum_current'] && EstatsCore::option('GroupAmount/'.$iD))
		{
			$difference = EstatsGUI::formatDifference($data['sum_current'], $data['sum_before']);
			$summary = EstatsTheme::parse(EstatsTheme::get('group-amount'), array(
	'amount' => EstatsGUI::formatNumber($data['sum_current']),
	'class' => (($difference == 0)?'remain':(($difference > 0)?'increase':'decrease')),
	'difference' => (($difference > 0)?'+':'').$difference.'%',
	));
		}
		else
		{
			$summary = EstatsTheme::get('group-none');
		}

		return EstatsTheme::parse(EstatsTheme::get('group'), array(
	'title' => ((!$extended && EstatsCore::option('GroupAmount/'.$iD) && $data['amount'] > EstatsCore::option('GroupAmount/'.$iD))?sprintf(EstatsLocale::translate('%s (%d of %d)'), $title, (int) ((EstatsCore::option('GroupAmount/'.$iD) > $data['amount'])?$data['amount']:EstatsCore::option('GroupAmount/'.$iD)), (int) $data['amount']):$title),
	'link' => ($link?str_replace('{date}', '{period}', $link):''),
	'links' => (($pagesAmount > 1)?EstatsGUI::linksWidget($page, $pagesAmount, str_replace('{date}', '{period}/{page}', $link)):''),
	'information' => ($information?str_replace('{information}', $information, EstatsTheme::get('group-information')):''),
	'rows' => $contents,
	'summary' => $summary,
	'id' => $iD,
	'lang_sum' => EstatsLocale::translate('Sum'),
	'lang_none' => EstatsLocale::translate('None'),
	));
	}
}
?>