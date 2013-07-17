<?php
/**
 * Visits GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 5.0.00
 */

if (!defined('eStats'))
{
	die();
}

$showDetails = (isset($path[2]) && $path[2] == 'visit');
$updateIDs = $updateFiles = array();

if ($showDetails)
{
	$showID = (int) (isset($path[3])?$path[3]:1);
	$where = array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, $showID)));
	$data = EstatsCore::driver()->selectData(array('visitors'), array('id', 'lastvisit'), $where);

	if ($data)
	{
		$updateIDs = array($showID);
		$updateFiles = array('visit-'.$showID.'-'.strtotime($data[0]['lastvisit']));
		$data = array(0);
	}
	else
	{
		$showDetails = FALSE;

		EstatsGUI::notify(EstatsLocale::translate('Invalid visit identifier!'), 'error');
	}
}

if (!$showDetails)
{
	$showRobots = (int) (isset($path[2])?$path[2]:((EstatsCookie::get('visitsShowRobots') !== NULL)?EstatsCookie::get('visitsShowRobots'):0));
	$page = (int) (isset($path[3])?$path[3]:1);

	if (isset($_POST['ChangeRobots']))
	{
		$showRobots = isset($_POST['ShowRobots']);

		EstatsCookie::set('visitsShowRobots', $showRobots);
	}

	$where = ($showRobots?NULL:array(EstatsDriver::OPERATOR_GROUPING_START, array(EstatsDriver::ELEMENT_OPERATION, array('robot', EstatsDriver::OPERATOR_EQUAL, '')), EstatsDriver::OPERATOR_OR, array(EstatsDriver::ELEMENT_OPERATION, array('robot', EstatsDriver::OPERATOR_EQUAL, '0')), EstatsDriver::OPERATOR_GROUPING_END));
	$amount = EstatsCore::driver()->selectAmount('visitors', $where);
	$pagesAmount = ceil($amount / EstatsCore::option('Visits/amount'));

	if ($pagesAmount > EstatsCore::option('Visits/maxpages') && ESTATS_USERLEVEL < 2)
	{
		$amount = (EstatsCore::option('Visits/amount') * EstatsCore::option('Visits/maxpages'));
		$pagesAmount = EstatsCore::option('Visits/maxpages');
	}

	if ($page < 1 || $page > $pagesAmount)
	{
		$page = 1;
	}

	$fileName = 'visits-'.$page.($showRobots?'':'-norobots');

	if (EstatsCache::status($fileName, EstatsCore::option('Cache/visits')))
	{
		$data = EstatsCore::driver()->selectData(array('visitors'), array('id', 'lastvisit'), $where, EstatsCore::option('Visits/amount'), (EstatsCore::option('Visits/amount') * ($page - 1)), array('lastvisit' => FALSE));

		EstatsCache::save($fileName, $data);
		EstatsTheme::add('cacheinformation', '');
	}
	else
	{
		$data = EstatsCache::read($fileName);

		EstatsTheme::add('cacheinformation', EstatsGUI::notificationWidget(sprintf(EstatsLocale::translate('Data from <em>cache</em>, refreshed: %s.'), date('d.m.Y H:i:s', EstatsCache::timestamp($fileName))), 'information'));
	}

	$j = -1;

	for ($i = 0, $c = count($data); $i < $c; ++$i)
	{
		$fileName = 'visit-'.$data[$i]['id'].'-'.strtotime($data[$i]['lastvisit']);

		if (EstatsCache::status($fileName, 86400))
		{
			$updateIDs[] = $data[$i]['id'];
			$updateFiles[] = $fileName;
			$data[$i] = ++$j;
		}
		else
		{
			$data[$i] = EstatsCache::read($fileName);
		}
	}
}

if ($updateIDs)
{
	if (!$showDetails)
	{
		if ($where)
		{
			$where[] = EstatsDriver::OPERATOR_AND;
		}

		$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('visitors.id', EstatsDriver::OPERATOR_IN, $updateIDs));
	}

	$newData = EstatsCore::driver()->selectData(array('visitors', array(EstatsDriver::JOIN_LEFT, EstatsDriver::OPERATOR_JOIN_USING, array('id')), 'details'), array('visitors.*', array(EstatsDriver::ELEMENT_FIELD, 'details.id', 'details')), $where, 0, 0, array('visitors.lastvisit' => FALSE), NULL, NULL, TRUE);
}

for ($i = 0, $c = count($data); $i < $c; ++$i)
{
	if (is_int($data[$i]))
	{
		EstatsCache::delete('visit-'.$updateIDs[$data[$i]].'-*');

		$index = $data[$i];
		$data[$i] = &$newData[$data[$i]];
		$data[$i]['browser'] = implode(' ', EstatsCore::detectBrowser($data[$i]['useragent']));
		$data[$i]['operatingsystem'] = implode(' ', EstatsCore::detectOperatingSystem($data[$i]['useragent']));
		$data[$i]['keywords'] = '';

		if ($data[$i]['referrer'] && !$data[$i]['robot'])
		{
			$referrer = parse_url($data[$i]['referrer']);
			$data[$i]['referrer-host'] = $referrer['host'];
			$data[$i]['websearch'] = EstatsCore::detectWebsearcher($data[$i]['referrer'], TRUE);

			if ($data[$i]['websearch'])
			{
				$data[$i]['keywords'] = implode(', ', $data[$i]['websearch'][1]);
			}

			if (in_array($referrer['host'], EstatsCore::option('Referrers')))
			{
				$data[$i]['referrer'] = '';
			}
		}

		$data[$i]['geolocation'] = (EstatsGeolocation::isAvailable()?EstatsGeolocation::information($data[$i]['ip']):NULL);

		EstatsCache::save($updateFiles[$index], $data[$i]);
	}

	if ($data[$i]['robot'])
	{
		$class = 'robot';
		$type = '$';
	}
	else if (isset($_SESSION[EstatsCore::session()]['visits'][$data[$i]['id']]))
	{
		$class = 'user';
		$type = '!';
	}
	else if (($_SERVER['REQUEST_TIME'] - strtotime($data[$i]['lastvisit'])) < 300)
	{
		$class = 'online';
		$type = '+';
	}
	else if ($data[$i]['previous'])
	{
		$class = 'returns';
		$type = '^';
	}
	else
	{
		$class = '';
		$type = '&nbsp;';
	}

	if (!$showDetails)
	{
		EstatsTheme::add('details-'.$data[$i]['id'], (boolean) $data[$i]['details']);
	}

	if (strstr($data[$i]['host'], '.') && ESTATS_USERLEVEL < 2)
	{
		$data[$i]['host'] = '*'.substr($data[$i]['host'], strpos($data[$i]['host'], '.'));
	}

	$first = (is_numeric($data[$i]['firstvisit'])?$data[$i]['firstvisit']:strtotime($data[$i]['firstvisit']));
	$last = (is_numeric($data[$i]['lastvisit'])?$data[$i]['lastvisit']:strtotime($data[$i]['lastvisit']));
	$robot = (($data[$i]['robot'] == '?')?EstatsLocale::translate('Unknown'):htmlspecialchars($data[$i]['robot']));
	$robotIcon = ($robot?EstatsGUI::iconTag(EstatsGUI::iconPath($robot, 'robots'), EstatsLocale::translate('Network robot').': '.EstatsGUI::itemText($robot, 'robots')):'');
	$operatingSystem = EstatsGUI::itemText($data[$i]['operatingsystem'], 'operatingsystem-versions');
	$operatingSystemIcon = EstatsGUI::iconTag(EstatsGUI::iconPath($data[$i]['operatingsystem'], 'operatingsystem-versions'), EstatsLocale::translate('Operating system').': '.$operatingSystem);
	$browser = EstatsGUI::itemText($data[$i]['browser'], 'browser-versions');
	$browserIcon = EstatsGUI::iconTag(EstatsGUI::iconPath($data[$i]['browser'], 'browser-versions'), EstatsLocale::translate('Browser').': '.$browser);
	$language = EstatsGUI::itemText($data[$i]['language'], 'languages');
	$languageIcon = ($language?EstatsGUI::iconTag(EstatsGUI::iconPath($data[$i]['language'], 'languages'), EstatsLocale::translate('Language').': '.$language):'');
	$screen = htmlspecialchars(EstatsGUI::itemText($data[$i]['screen'], 'screens'));
	$screenIcon = ($screen?EstatsGUI::iconTag(EstatsGUI::iconPath($data[$i]['screen'], 'screens'), EstatsLocale::translate('Screen resolution').': '.$screen):'');
	$flash = (($data[$i]['flash'] != 0 || $data[$i]['flash'] == '?')?EstatsGUI::itemText($data[$i]['flash'], 'flash'):EstatsLocale::translate('No plugin'));
	$flashIcon = (($data[$i]['flash'] != 0 || $data[$i]['flash'] == '?')?EstatsGUI::iconTag(EstatsGUI::iconPath('flash', 'miscellaneous'), EstatsLocale::translate('Flash plugin version').': '.$flash):'');
	$java = ($data[$i]['java']?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
	$javaIcon = ($data[$i]['java']?EstatsGUI::iconTag(EstatsGUI::iconPath('java', 'miscellaneous'), EstatsLocale::translate('Java enabled')):'');
	$javaScript = ($data[$i]['javascript']?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
	$javaScriptIcon = ($data[$i]['javascript']?EstatsGUI::iconTag(EstatsGUI::iconPath('javascript', 'miscellaneous'), EstatsLocale::translate('JavaScript enabled')):'');
	$cookies = ($data[$i]['cookies']?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
	$cookiesIcon = ($data[$i]['cookies']?EstatsGUI::iconTag(EstatsGUI::iconPath('cookies', 'miscellaneous'), EstatsLocale::translate('Cookies enabled')):'');
	$proxy = ($data[$i]['proxy']?EstatsGUI::whoisLink($data[$i]['proxyip'], EstatsLocale::translate('Proxy')).': '.htmlspecialchars($data[$i]['proxy']):'');
	$proxyIcon = ($proxy?EstatsGUI::whoisLink($data[$i]['proxyip'], '
'.EstatsGUI::iconTag(EstatsGUI::iconPath('proxy', 'miscellaneous'), EstatsLocale::translate('Proxy').': '.htmlspecialchars($data[$i]['proxy'])).'
'):'');
	$city = htmlspecialchars($data[$i]['geolocation']?$data[$i]['geolocation']['city']:'');
	$region = htmlspecialchars(($data[$i]['geolocation'] && $data[$i]['geolocation']['region'])?EstatsGUI::itemText($data[$i]['geolocation']['country'].'-'.$data[$i]['geolocation']['region'], 'regions'):'');
	$country = htmlspecialchars(($data[$i]['geolocation'] && $data[$i]['geolocation']['country'])?EstatsGUI::itemText($data[$i]['geolocation']['country'], 'countries'):'');
	$continent = htmlspecialchars(($data[$i]['geolocation'] && $data[$i]['geolocation']['continent'])?EstatsGUI::itemText($data[$i]['geolocation']['continent'], 'continents'):'');
	$coordinates = ($data[$i]['geolocation']?EstatsGeolocation::coordinates($data[$i]['geolocation']['latitude'], $data[$i]['geolocation']['longitude']):'');
	$location = ($data[$i]['geolocation']?'<a href="'.EstatsGUI::mapLink($data[$i]['geolocation']['latitude'], $data[$i]['geolocation']['longitude']).'">'.($city?$city.', ':'').$country.'</a>':'');
	$hours = intval(($last + 5 - $first) / 3600);
	$minutes = intval((($last + 5 - $first) / 60) - (($hours * 60)));
	$seconds = intval($last + 5 - $first - (($minutes * 60) + ($hours * 3600)));
	$difference = ($hours?$hours.':':'').(($minutes < 10)?'0':'').$minutes.':'.(($seconds < 10)?'0':'').$seconds;
	$entry = EstatsTheme::parse(EstatsTheme::get($showDetails?'details':'visits-row'), array(
	'class' => $class,
	'simpletype' => $type,
	'id' => $data[$i]['id'],
	'first' => date('d.m.Y H:i:s', $first),
	'last' => date('d.m.Y H:i:s', $last),
	'visits' => (int) $data[$i]['visitsamount'],
	'time' => $difference,
	'referrer' => (($data[$i]['referrer'] && !$data[$i]['robot'])?'<a href="'.htmlspecialchars($data[$i]['referrer']).'"'.($data[$i]['keywords']?' title="'.EstatsLocale::translate('Keywords').': '.htmlspecialchars($data[$i]['keywords']).'" class="tooltip"':'').' rel="nofollow">
'.EstatsGUI::cutString($data[$i]['referrer'], EstatsTheme::option('VisitsRowValueLength')).'
'.($data[$i]['keywords']?'<span>
<strong>'.EstatsLocale::translate('Keywords').':</strong><br>
'.$data[$i]['keywords'].'
</span>
':'').'</a>'.((ESTATS_USERLEVEL == 2)?'
<a href="{selfpath}{separator}referrer='.$data[$i]['referrer-host'].'" class="'.(in_array($data[$i]['referrer-host'], EstatsCore::option('Referrers'))?'green" title="'.EstatsLocale::translate('Unblock counting of this referrer').'"':'red" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to exclude this referrer?').'\')) return false" title="'.EstatsLocale::translate('Block counting of this referrer').'"').'><strong>&#187;</strong></a>':''):'&nbsp;'),
	'keywords' => EstatsGUI::cutString($data[$i]['keywords'], EstatsTheme::option('VisitsRowValueLength'), 1),
	'host' => (($data[$i]['host'] && $data[$i]['host'] !== '?')?EstatsGUI::cutString($data[$i]['host'], EstatsTheme::option('VisitsRowValueLength'), 1):EstatsLocale::translate('Unknown')),
	'ip' => ((ESTATS_USERLEVEL == 2 && $data[$i]['ip'])?(($data[$i]['ip'] == 'unknown')?EstatsLocale::translate('Unknown'):(($data[$i]['ip'] == '127.0.0.1')?$data[$i]['ip']:EstatsGUI::whoisLink($data[$i]['ip'], $data[$i]['ip'])).'
'.EstatsGUI::ignoreIPLink(EstatsCore::option('IgnoredIPs'), $data[$i]['ip'])):'&nbsp;'),
	'useragent' => htmlspecialchars($data[$i]['useragent']),
	'robot' => &$robot,
	'robot_icon' => &$robotIcon,
	'operatingsystem' => &$operatingSystem,
	'operatingsystem_icon' => &$operatingSystemIcon,
	'browser' => &$browser,
	'browser_icon' => &$browserIcon,
	'language' => &$language,
	'language_icon' => &$languageIcon,
	'screen' => &$screen,
	'screen_icon' => &$screenIcon,
	'flash' => &$flash,
	'flash_icon' => &$flashIcon,
	'java' => &$java,
	'java_icon' => &$javaIcon,
	'javascript' => &$javaScript,
	'javascript_icon' => &$javaScriptIcon,
	'cookies' => &$cookies,
	'cookies_icon' => &$cookiesIcon,
	'location' => &$location,
	'city' => &$city,
	'region' => &$region,
	'country' => &$country,
	'continent' => &$continent,
	'coordinates' => &$coordinates,
	'longitude' => ($data[$i]['geolocation']?$data[$i]['geolocation']['longitude']:''),
	'latitude' => ($data[$i]['geolocation']?$data[$i]['geolocation']['latitude']:''),
	'country_id' => htmlspecialchars($data[$i]['geolocation']?$data[$i]['geolocation']['country']:''),
	'country_icon' => ($country?EstatsGUI::iconTag(EstatsGUI::iconPath($data[$i]['geolocation']['country'], 'countries'), $country):''),
	'configuration' => (EstatsTheme::option('Icons')?($robot?$robotIcon.'
':$browserIcon.'
'.$operatingSystemIcon.'
'.$languageIcon.'
'.($screenIcon?$screenIcon.'
':'').($flashIcon?$flashIcon.'
':'').($javaIcon?$javaIcon.'
':'').($javaScriptIcon?$javaScriptIcon.'
':'').($cookiesIcon?$cookiesIcon.'
':'').($proxyIcon?$proxyIcon.'
':'')):'<small>
'.EstatsLocale::translate('User Agent').': <em>'.EstatsGUI::cutString($data[$i]['useragent'], 75).'</em>.<br>
'.($robot?EstatsLocale::translate('Network robot').': '.$robot.'<br>
':EstatsLocale::translate('Browser').': <em>'.$browser.'</em>.<br>
'.EstatsLocale::translate('Operating system').': <em>'.$operatingSystem.'</em>.<br>
'.(($data[$i]['language'] != '?')?EstatsLocale::translate('Language').': <em>'.$language.'</em>.<br>
':'')).($screen?EstatsLocale::translate('Screen resolution').': <em>'.$screen.'</em>.<br>
':'').EstatsLocale::translate('Flash plugin version').': <em>'.($flash?$flash:EstatsLocale::translate('Lack')).'.</em><br>
'.EstatsLocale::translate('Java').': <em>'.$java.'.</em><br>
'.EstatsLocale::translate('JavaScript').': <em>'.$javaScript.'.</em><br>
'.EstatsLocale::translate('Cookies').': <em>'.$cookies.'.</em><br>
'.($proxy?$proxy.'<br>
':'').'</small>
').($data[$i]['geolocation']?'<a href="'.EstatsGUI::mapLink($data[$i]['geolocation']['latitude'], $data[$i]['geolocation']['longitude']).'" class="tooltip">
'.(EstatsTheme::option('Icons')?EstatsGUI::iconTag(EstatsGUI::iconPath('geolocation', 'miscellaneous'), EstatsLocale::translate('Show location on map')).'
':'').'<span>
<strong>'.EstatsLocale::translate('Location').':</strong><br>
'.($city?EstatsLocale::translate('City').': <em>'.EstatsGUI::itemText($city, 'cities').'</em><br>
':'').($region?EstatsLocale::translate('Region').': <em>'.$region.'</em><br>
':'').($country?EstatsLocale::translate('Country').': <em>'.$country.'</em><br>
':'').($continent?EstatsLocale::translate('Continent').': <em>'.$continent.'</em><br>
':'').EstatsLocale::translate('Co-ordinates').': <em>'.$coordinates.'</em>
</span>
</a>
':''),
	), array(
	'referrer' => (boolean) $data[$i]['referrer'],
	'keywords' => (boolean) $data[$i]['keywords'],
	'robot' => (boolean) $robot,
	'location' => (boolean) $data[$i]['geolocation'],
	'technical' => (boolean) $data[$i]['javascript'],
	));

	if (!$showDetails)
	{
		EstatsTheme::append('rows', $entry);
	}
}

if ($showDetails)
{
	$page = (int) (isset($path[4])?$path[4]:1);

	EstatsTheme::add('title', sprintf(EstatsLocale::translate('Visit details #%d'), $showID));

	if ($page < 1 || $page > ceil($data[0]['visitsamount'] / EstatsCore::option('Visits/detailsamount')))
	{
		$page = 1;
	}

	$sites = EstatsCore::driver()->selectData(array('details'), array('time', 'address', array(EstatsDriver::ELEMENT_SUBQUERY, array(array('sites'), array('sites.name'), array(array(EstatsDriver::ELEMENT_OPERATION, array('sites.address', EstatsDriver::OPERATOR_EQUAL, 'details.address')))), 'title')), $where, EstatsCore::option('Visits/detailsamount'), (EstatsCore::option('Visits/detailsamount') * ($page - 1)), array('time' => FALSE));

	EstatsTheme::add('rows', '');

	for ($i = 0, $c = count($sites); $i < $c; ++$i)
	{
		$title = htmlspecialchars($sites[$i][empty($sites[$i]['title'])?'address':'title']);

		EstatsTheme::append('rows', EstatsTheme::parse(EstatsTheme::get('details-row'), array(
	'num' => ($data[0]['visitsamount'] - $i - (($page - 1) * EstatsCore::option('Visits/detailsamount'))),
	'date' => date('d.m.Y H:i:s', (is_numeric($sites[$i]['time'])?$sites[$i]['time']:strtotime($sites[$i]['time']))),
	'title' => $title,
	'link' => '<a href="'.htmlspecialchars($sites[$i]['address']).'">'.EstatsGUI::cutString($title, EstatsTheme::option('DetailsRowValueLength')).'</a>'
	)));
	}

	$pagesAmount = ceil($data[0]['visitsamount'] / EstatsCore::option('Visits/detailsamount'));

	if ($pagesAmount > 1)
	{
		EstatsTheme::add('title', sprintf(EstatsLocale::translate('%s - page %d. of %d'), EstatsTheme::get('title'), $page, $pagesAmount));
	}

	$otherIDs = array();
	$previousVisit = $data[0]['previous'];
	$nextVisit = $data[0]['id'];
	$i = 0;

	while ($previousVisit && $i < 10)
	{
		$otherIDs[] = $previousVisit;

		$previousVisit = EstatsCore::driver()->selectField('visitors', 'previous', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, $previousVisit))));

		++$i;
	}

	while ($nextVisit && $i < 20)
	{
		$nextVisit = EstatsCore::driver()->selectField('visitors', 'id', array(array(EstatsDriver::ELEMENT_OPERATION, array('previous', EstatsDriver::OPERATOR_EQUAL, $nextVisit))));

		if ($nextVisit)
		{
			$otherIDs[] = $nextVisit;
		}

		++$i;
	}

	EstatsTheme::add('page', str_replace('{rowspan}', (count($sites) + (($data[0]['visitsamount'] > EstatsCore::option('Visits/detailsamount'))?3:2) - 1), $entry));

	sort($otherIDs);

	$data = EstatsCore::driver()->selectData(array('visitors', array(EstatsDriver::JOIN_LEFT, EstatsDriver::OPERATOR_JOIN_USING, array('id')), 'details'), array('visitors.id', 'visitors.firstvisit', 'visitors.lastvisit', 'visitors.visitsamount', array(EstatsDriver::ELEMENT_FIELD, 'details.id', 'details')), array(array(EstatsDriver::ELEMENT_OPERATION, array('visitors.id', EstatsDriver::OPERATOR_IN, $otherIDs))), 0, 0, array('visitors.lastvisit' => FALSE), NULL, NULL, TRUE);

	EstatsTheme::add('other-visits', (count($data) > 0));

	if ($data)
	{
		for ($i = 0, $c = count($data); $i < $c; ++$i)
		{
			EstatsTheme::add('details-'.$data[$i]['id'], $data[$i]['details']);
			EstatsTheme::append('othervisits', EstatsTheme::parse(EstatsTheme::get('other-visits-row'), array(
	'id' => $data[$i]['id'],
	'first' => date('d.m.Y H:i:s', (is_numeric($data[$i]['firstvisit'])?$data[$i]['firstvisit']:strtotime($data[$i]['firstvisit']))),
	'last' => date('d.m.Y H:i:s', (is_numeric($data[$i]['lastvisit'])?$data[$i]['lastvisit']:strtotime($data[$i]['lastvisit']))),
	'amount' => (int) $data[$i]['visitsamount'],
	)));
		}
	}

	EstatsTheme::add('links', (($pagesAmount > 1)?EstatsGUI::linksWIdget($page, $pagesAmount, '{path}visits/visit/'.$showID.'/{page}{suffix}'):''));
}
else
{
	EstatsTheme::add('robotscheckbox', ($showRobots?' checked="checked"':''));

	if ($pagesAmount > 1 && EstatsCore::option('Visits/maxpages') > 1)
	{
		EstatsTheme::add('title', sprintf(EstatsLocale::translate('%s - page %d. of %d'), EstatsTheme::get('title'), $page, $pagesAmount));
	}

	EstatsTheme::add('links', (($pagesAmount > 1)?EstatsGUI::linksWIdget($page, $pagesAmount, '{path}visits/'.$showRobots.'/{page}{suffix}'):''));

	if (!count($data))
	{
		EstatsTheme::link('visits-none', 'rows');
	}
}

EstatsTheme::add('lang_date', EstatsLocale::translate('Date'));
EstatsTheme::add('lang_site', EstatsLocale::translate('Site'));
EstatsTheme::add('lang_details', EstatsLocale::translate('Details'));
EstatsTheme::add('lang_legend', EstatsLocale::translate('Legend'));
EstatsTheme::add('lang_yourvisits', EstatsLocale::translate('Your visits'));
EstatsTheme::add('lang_onlinevisitors', sprintf(EstatsLocale::translate('On-line visitors (last %d seconds)'), EstatsCore::option('OnlineTime')));
EstatsTheme::add('lang_returnsvisitors', EstatsLocale::translate('Returns visitors'));
EstatsTheme::add('lang_showrobots', EstatsLocale::translate('Show network robots'));
EstatsTheme::add('lang_firstvisit', EstatsLocale::translate('First view'));
EstatsTheme::add('lang_lastvisit', EstatsLocale::translate('Last view'));
EstatsTheme::add('lang_visitsamount', EstatsLocale::translate('Amount of views'));
EstatsTheme::add('lang_referrer', EstatsLocale::translate('Referrer website'));
EstatsTheme::add('lang_keywords', EstatsLocale::translate('Keywords'));
EstatsTheme::add('lang_host', EstatsLocale::translate('Host'));
EstatsTheme::add('lang_configuration', EstatsLocale::translate('Configuration'));
EstatsTheme::add('lang_none', EstatsLocale::translate('None'));
EstatsTheme::add('lang_ip', EstatsLocale::translate('IP'));
EstatsTheme::add('lang_useragent', EstatsLocale::translate('User Agent'));
EstatsTheme::add('lang_browser', EstatsLocale::translate('Browser'));
EstatsTheme::add('lang_os', EstatsLocale::translate('Operating system'));
EstatsTheme::add('lang_screen', EstatsLocale::translate('Screen resolution'));
EstatsTheme::add('lang_language', EstatsLocale::translate('Language'));
EstatsTheme::add('lang_location', EstatsLocale::translate('Location'));
EstatsTheme::add('lang_flash', EstatsLocale::translate('Flash plugin version'));
EstatsTheme::add('lang_java', EstatsLocale::translate('Java'));
EstatsTheme::add('lang_javascript', EstatsLocale::translate('JavaScript'));
EstatsTheme::add('lang_cookies', EstatsLocale::translate('Cookies'));
EstatsTheme::add('lang_robot', EstatsLocale::translate('Network robot'));
EstatsTheme::add('lang_robots', EstatsLocale::translate('Network robots'));
EstatsTheme::add('lang_visitstime', EstatsLocale::translate('Visit time'));
EstatsTheme::add('lang_visitedpages', EstatsLocale::translate('Visited pages'));
EstatsTheme::add('lang_othervisits', EstatsLocale::translate('Other visits of this visitor'));
?>