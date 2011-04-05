<?php
/**
 * Visits GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

$ShowDetails = (isset($Path[2]) && $Path[2] == 'visit');
$UpdateIDs = $UpdateFiles = array();

if ($ShowDetails)
{
	$ShowID = (int) (isset($Path[3])?$Path[3]:1);
	$Where = array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, $ShowID)));
	$Data = EstatsCore::driver()->selectData(array('visitors'), array('id', 'lastvisit'), $Where);

	if ($Data)
	{
		$UpdateIDs = array($ShowID);
		$UpdateFiles = array('visit-'.$ShowID.'-'.strtotime($Data[0]['lastvisit']));
		$Data = array(0);
	}
	else
	{
		$ShowDetails = FALSE;

		EstatsGUI::notify(EstatsLocale::translate('Invalid visit identifier!'), 'error');
	}
}

if (!$ShowDetails)
{
	$ShowRobots = (int) (isset($Path[2])?$Path[2]:((EstatsCookie::get('visitsShowRobots') !== NULL)?EstatsCookie::get('visitsShowRobots'):0));
	$Page = (int) (isset($Path[3])?$Path[3]:1);

	if (isset($_POST['ChangeRobots']))
	{
		$ShowRobots = isset($_POST['ShowRobots']);

		EstatsCookie::set('visitsShowRobots', $ShowRobots);
	}

	$Where = ($ShowRobots?NULL:array(EstatsDriver::OPERATOR_GROUPING_START, array(EstatsDriver::ELEMENT_OPERATION, array('robot', EstatsDriver::OPERATOR_EQUAL, '')), EstatsDriver::OPERATOR_OR, array(EstatsDriver::ELEMENT_OPERATION, array('robot', EstatsDriver::OPERATOR_EQUAL, '0')), EstatsDriver::OPERATOR_GROUPING_END));
	$Amount = EstatsCore::driver()->selectAmount('visitors', $Where);
	$PagesAmount = ceil($Amount / EstatsCore::option('Visits|amount'));

	if ($PagesAmount > EstatsCore::option('Visits|maxpages') && ESTATS_USERLEVEL < 2)
	{
		$Amount = (EstatsCore::option('Visits|amount') * EstatsCore::option('Visits|maxpages'));
		$PagesAmount = EstatsCore::option('Visits|maxpages');
	}

	if ($Page < 1 || $Page > $PagesAmount)
	{
		$Page = 1;
	}

	$FileName = 'visits-'.$Page.($ShowRobots?'':'-norobots');

	if (EstatsCache::status($FileName, EstatsCore::option('Cache|visits')))
	{
		$Data = EstatsCore::driver()->selectData(array('visitors'), array('id', 'lastvisit'), $Where, EstatsCore::option('Visits|amount'), (EstatsCore::option('Visits|amount') * ($Page - 1)), array('lastvisit' => FALSE));

		EstatsCache::save($FileName, $Data);
		EstatsTheme::add('cacheinformation', '');
	}
	else
	{
		$Data = EstatsCache::read($FileName);

		EstatsTheme::add('cacheinformation', EstatsGUI::notificationWidget(sprintf(EstatsLocale::translate('Data from <em>cache</em>, refreshed: %s.'), date('d.m.Y H:i:s', EstatsCache::timestamp($FileName))), 'information'));
	}

	$j = -1;

	for ($i = 0, $c = count($Data); $i < $c; ++$i)
	{
		$FileName = 'visit-'.$Data[$i]['id'].'-'.strtotime($Data[$i]['lastvisit']);

		if (EstatsCache::status($FileName, 86400))
		{
			$UpdateIDs[] = $Data[$i]['id'];
			$UpdateFiles[] = $FileName;
			$Data[$i] = ++$j;
		}
		else
		{
			$Data[$i] = EstatsCache::read($FileName);
		}
	}
}

if ($UpdateIDs)
{
	if (!$ShowDetails)
	{
		if ($Where)
		{
			$Where[] = EstatsDriver::OPERATOR_AND;
		}

		$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('visitors.id', EstatsDriver::OPERATOR_IN, $UpdateIDs));
	}

	$NewData = EstatsCore::driver()->selectData(array('visitors', array(EstatsDriver::JOIN_LEFT, EstatsDriver::OPERATOR_JOIN_USING, array('id')), 'details'), array('visitors.*', array(EstatsDriver::ELEMENT_FIELD, 'details.id', 'details')), $Where, 0, 0, array('visitors.lastvisit' => FALSE), NULL, NULL, TRUE);
}

for ($i = 0, $c = count($Data); $i < $c; ++$i)
{
	if (is_integer($Data[$i]))
	{
		EstatsCache::delete('visit-'.$UpdateIDs[$Data[$i]].'-*');

		$Index = $Data[$i];
		$Data[$i] = &$NewData[$Data[$i]];
		$Data[$i]['browser'] = implode(' ', EstatsCore::detectBrowser($Data[$i]['useragent']));
		$Data[$i]['operatingsystem'] = implode(' ', EstatsCore::detectOperatingSystem($Data[$i]['useragent']));
		$Data[$i]['keywords'] = '';

		if ($Data[$i]['referrer'] && !$Data[$i]['robot'])
		{
			$Referrer = parse_url($Data[$i]['referrer']);
			$Data[$i]['referrer-host'] = $Referrer['host'];
			$Data[$i]['websearch'] = EstatsCore::detectWebsearcher($Data[$i]['referrer'], TRUE);

			if ($Data[$i]['websearch'])
			{
				$Data[$i]['keywords'] = implode(', ', $Data[$i]['websearch'][1]);
			}

			if (in_array($Referrer['host'], EstatsCore::option('Referrers')))
			{
				$Data[$i]['referrer'] = '';
			}
		}

		$Data[$i]['geolocation'] = (EstatsGeolocation::isAvailable()?EstatsGeolocation::information($Data[$i]['ip']):NULL);

		EstatsCache::save($UpdateFiles[$Index], $Data[$i]);
	}

	if ($Data[$i]['robot'])
	{
		$Class = 'robot';
		$Type = '$';
	}
	else if (isset($_SESSION[EstatsCore::session()]['visits'][$Data[$i]['id']]))
	{
		$Class = 'user';
		$Type = '!';
	}
	else if (($_SERVER['REQUEST_TIME'] - strtotime($Data[$i]['lastvisit'])) < 300)
	{
		$Class = 'online';
		$Type = '+';
	}
	else if ($Data[$i]['previous'])
	{
		$Class = 'returns';
		$Type = '^';
	}
	else
	{
		$Class = '';
		$Type = '&nbsp;';
	}

	if (!$ShowDetails)
	{
		EstatsTheme::add('details-'.$Data[$i]['id'], (boolean) $Data[$i]['details']);
	}

	if (strstr($Data[$i]['host'], '.') && ESTATS_USERLEVEL < 2)
	{
		$Data[$i]['host'] = '*'.substr($Data[$i]['host'], strpos($Data[$i]['host'], '.'));
	}

	$First = (is_numeric($Data[$i]['firstvisit'])?$Data[$i]['firstvisit']:strtotime($Data[$i]['firstvisit']));
	$Last = (is_numeric($Data[$i]['lastvisit'])?$Data[$i]['lastvisit']:strtotime($Data[$i]['lastvisit']));
	$Robot = (($Data[$i]['robot'] == '?')?EstatsLocale::translate('Unknown'):htmlspecialchars($Data[$i]['robot']));
	$RobotIcon = ($Robot?EstatsGUI::iconTag(EstatsGUI::iconPath($Robot, 'robots'), EstatsLocale::translate('Network robot').': '.EstatsGUI::itemText($Robot, 'robots')):'');
	$OperatingSystem = EstatsGUI::itemText($Data[$i]['operatingsystem'], 'operatingsystem-versions');
	$OperatingSystemIcon = EstatsGUI::iconTag(EstatsGUI::iconPath($Data[$i]['operatingsystem'], 'operatingsystem-versions'), EstatsLocale::translate('Operating system').': '.$OperatingSystem);
	$Browser = EstatsGUI::itemText($Data[$i]['browser'], 'browser-versions');
	$BrowserIcon = EstatsGUI::iconTag(EstatsGUI::iconPath($Data[$i]['browser'], 'browser-versions'), EstatsLocale::translate('Browser').': '.$Browser);
	$Language = EstatsGUI::itemText($Data[$i]['language'], 'languages');
	$LanguageIcon = ($Language?EstatsGUI::iconTag(EstatsGUI::iconPath($Data[$i]['language'], 'languages'), EstatsLocale::translate('Language').': '.$Language):'');
	$Screen = htmlspecialchars(EstatsGUI::itemText($Data[$i]['screen'], 'screens'));
	$ScreenIcon = ($Screen?EstatsGUI::iconTag(EstatsGUI::iconPath($Data[$i]['screen'], 'screens'), EstatsLocale::translate('Screen resolution').': '.$Screen):'');
	$Flash = (($Data[$i]['flash'] != 0 || $Data[$i]['flash'] == '?')?EstatsGUI::itemText($Data[$i]['flash'], 'flash'):EstatsLocale::translate('No plugin'));
	$FlashIcon = (($Data[$i]['flash'] != 0 || $Data[$i]['flash'] == '?')?EstatsGUI::iconTag(EstatsGUI::iconPath('flash', 'miscellaneous'), EstatsLocale::translate('Flash plugin version').': '.$Flash):'');
	$Java = ($Data[$i]['java']?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
	$JavaIcon = ($Data[$i]['java']?EstatsGUI::iconTag(EstatsGUI::iconPath('java', 'miscellaneous'), EstatsLocale::translate('Java enabled')):'');
	$JavaScript = ($Data[$i]['javascript']?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
	$JavaScriptIcon = ($Data[$i]['javascript']?EstatsGUI::iconTag(EstatsGUI::iconPath('javascript', 'miscellaneous'), EstatsLocale::translate('JavaScript enabled')):'');
	$Cookies = ($Data[$i]['cookies']?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
	$CookiesIcon = ($Data[$i]['cookies']?EstatsGUI::iconTag(EstatsGUI::iconPath('cookies', 'miscellaneous'), EstatsLocale::translate('Cookies enabled')):'');
	$Proxy = ($Data[$i]['proxy']?EstatsGUI::whoisLink($Data[$i]['proxyip'], EstatsLocale::translate('Proxy')).': '.htmlspecialchars($Data[$i]['proxy']):'');
	$ProxyIcon = ($Proxy?EstatsGUI::whoisLink($Data[$i]['proxyip'], '
'.EstatsGUI::iconTag(EstatsGUI::iconPath('proxy', 'miscellaneous'), EstatsLocale::translate('Proxy').': '.htmlspecialchars($Data[$i]['proxy'])).'
'):'');
	$City = htmlspecialchars($Data[$i]['geolocation']?$Data[$i]['geolocation']['city']:'');
	$Region = htmlspecialchars(($Data[$i]['geolocation'] && $Data[$i]['geolocation']['region'])?EstatsGUI::itemText($Data[$i]['geolocation']['country'].'-'.$Data[$i]['geolocation']['region'], 'regions'):'');
	$Country = htmlspecialchars(($Data[$i]['geolocation'] && $Data[$i]['geolocation']['country'])?EstatsGUI::itemText($Data[$i]['geolocation']['country'], 'countries'):'');
	$Continent = htmlspecialchars(($Data[$i]['geolocation'] && $Data[$i]['geolocation']['continent'])?EstatsGUI::itemText($Data[$i]['geolocation']['continent'], 'continents'):'');
	$Coordinates = ($Data[$i]['geolocation']?EstatsGeolocation::coordinates($Data[$i]['geolocation']['latitude'], $Data[$i]['geolocation']['longitude']):'');
	$Location = ($Data[$i]['geolocation']?'<a href="'.EstatsGUI::mapLink($Data[$i]['geolocation']['latitude'], $Data[$i]['geolocation']['longitude']).'" tabindex="'.EstatsGUI::tabindex().'">'.($City?$City.', ':'').$Country.'</a>':'');
	$Hours = intval(($Last + 5 - $First) / 3600);
	$Minutes = intval((($Last + 5 - $First) / 60) - (($Hours * 60)));
	$Seconds = intval($Last + 5 - $First - (($Minutes * 60) + ($Hours * 3600)));
	$Difference = ($Hours?$Hours.':':'').(($Minutes < 10)?'0':'').$Minutes.':'.(($Seconds < 10)?'0':'').$Seconds;
	$Entry = EstatsTheme::parse(EstatsTheme::get($ShowDetails?'details':'visits-row'), array(
	'class' => $Class,
	'simpletype' => $Type,
	'id' => $Data[$i]['id'],
	'first' => date('d.m.Y H:i:s', $First),
	'last' => date('d.m.Y H:i:s', $Last),
	'visits' => (int) $Data[$i]['visitsamount'],
	'time' => $Difference,
	'tabindex' => EstatsGUI::tabindex(),
	'referrer' => (($Data[$i]['referrer'] && !$Data[$i]['robot'])?'<a href="'.htmlspecialchars($Data[$i]['referrer']).'" tabindex="'.EstatsGUI::tabindex().'"'.($Data[$i]['keywords']?' title="'.EstatsLocale::translate('Keywords').': '.htmlspecialchars($Data[$i]['keywords']).'" class="tooltip"':'').' rel="nofollow">
'.EstatsGUI::cutString($Data[$i]['referrer'], EstatsTheme::option('VisitsRowValueLength')).'
'.($Data[$i]['keywords']?'<span>
<strong>'.EstatsLocale::translate('Keywords').':</strong><br />
'.$Data[$i]['keywords'].'
</span>
':'').'</a>'.((ESTATS_USERLEVEL == 2)?'
<a href="{selfpath}{separator}referrer='.$Data[$i]['referrer-host'].'" class="'.(in_array($Data[$i]['referrer-host'], EstatsCore::option('Referrers'))?'green" title="'.EstatsLocale::translate('Unblock counting of this referrer').'"':'red" onclick="if (!confirm(\''.EstatsLocale::translate('Do You really want to exclude this referrer?').'\')) return false" title="'.EstatsLocale::translate('Block counting of this referrer').'"').' tabindex="'.EstatsGUI::tabindex().'"><strong>&#187;</strong></a>':''):'&nbsp;'),
	'keywords' => EstatsGUI::cutString($Data[$i]['keywords'], EstatsTheme::option('VisitsRowValueLength'), 1),
	'host' => (($Data[$i]['host'] && $Data[$i]['host'] !== '?')?EstatsGUI::cutString($Data[$i]['host'], EstatsTheme::option('VisitsRowValueLength'), 1):EstatsLocale::translate('Unknown')),
	'ip' => ((ESTATS_USERLEVEL == 2 && $Data[$i]['ip'])?(($Data[$i]['ip'] == 'unknown')?EstatsLocale::translate('Unknown'):(($Data[$i]['ip'] == '127.0.0.1')?$Data[$i]['ip']:EstatsGUI::whoisLink($Data[$i]['ip'], $Data[$i]['ip'])).'
'.EstatsGUI::ignoreIPLink(EstatsCore::option('IgnoredIPs'), $Data[$i]['ip'])):'&nbsp;'),
	'useragent' => htmlspecialchars($Data[$i]['useragent']),
	'robot' => &$Robot,
	'robot_icon' => &$RobotIcon,
	'operatingsystem' => &$OperatingSystem,
	'operatingsystem_icon' => &$OperatingSystemIcon,
	'browser' => &$Browser,
	'browser_icon' => &$BrowserIcon,
	'language' => &$Language,
	'language_icon' => &$LanguageIcon,
	'screen' => &$Screen,
	'screen_icon' => &$ScreenIcon,
	'flash' => &$Flash,
	'flash_icon' => &$FlashIcon,
	'java' => &$Java,
	'java_icon' => &$JavaIcon,
	'javascript' => &$JavaScript,
	'javascript_icon' => &$JavaScriptIcon,
	'cookies' => &$Cookies,
	'cookies_icon' => &$CookiesIcon,
	'location' => &$Location,
	'city' => &$City,
	'region' => &$Region,
	'country' => &$Country,
	'continent' => &$Continent,
	'coordinates' => &$Coordinates,
	'longitude' => ($Data[$i]['geolocation']?$Data[$i]['geolocation']['longitude']:''),
	'latitude' => ($Data[$i]['geolocation']?$Data[$i]['geolocation']['latitude']:''),
	'country_id' => htmlspecialchars($Data[$i]['geolocation']?$Data[$i]['geolocation']['country']:''),
	'country_icon' => ($Country?EstatsGUI::iconTag(EstatsGUI::iconPath($Data[$i]['geolocation']['country'], 'countries'), $Country):''),
	'configuration' => (EstatsTheme::option('Icons')?($Robot?$RobotIcon.'
':$BrowserIcon.'
'.$OperatingSystemIcon.'
'.$LanguageIcon.'
'.($ScreenIcon?$ScreenIcon.'
':'').($FlashIcon?$FlashIcon.'
':'').($JavaIcon?$JavaIcon.'
':'').($JavaScriptIcon?$JavaScriptIcon.'
':'').($CookiesIcon?$CookiesIcon.'
':'').($ProxyIcon?$ProxyIcon.'
':'')):'<small>
'.EstatsLocale::translate('User Agent').': <em>'.EstatsGUI::cutString($Data[$i]['useragent'], 75).'</em>.<br />
'.($Robot?EstatsLocale::translate('Network robot').': '.$Robot.'<br />
':EstatsLocale::translate('Browser').': <em>'.$Browser.'</em>.<br />
'.EstatsLocale::translate('Operating system').': <em>'.$OperatingSystem.'</em>.<br />
'.(($Data[$i]['language'] != '?')?EstatsLocale::translate('Language').': <em>'.$Language.'</em>.<br />
':'')).($Screen?EstatsLocale::translate('Screen resolution').': <em>'.$Screen.'</em>.<br />
':'').EstatsLocale::translate('Flash plugin version').': <em>'.($Flash?$Flash:EstatsLocale::translate('Lack')).'.</em><br />
'.EstatsLocale::translate('Java').': <em>'.$Java.'.</em><br />
'.EstatsLocale::translate('JavaScript').': <em>'.$JavaScript.'.</em><br />
'.EstatsLocale::translate('Cookies').': <em>'.$Cookies.'.</em><br />
'.($Proxy?$Proxy.'<br />
':'').'</small>
').($Data[$i]['geolocation']?'<a href="'.EstatsGUI::mapLink($Data[$i]['geolocation']['latitude'], $Data[$i]['geolocation']['longitude']).'" tabindex="'.EstatsGUI::tabindex().'" class="tooltip">
'.(EstatsTheme::option('Icons')?EstatsGUI::iconTag(EstatsGUI::iconPath('geolocation', 'miscellaneous'), EstatsLocale::translate('Show location on map')).'
':'').'<span>
<strong>'.EstatsLocale::translate('Location').':</strong><br />
'.($City?EstatsLocale::translate('City').': <em>'.EstatsGUI::itemText($City, 'cities').'</em><br />
':'').($Region?EstatsLocale::translate('Region').': <em>'.$Region.'</em><br />
':'').($Country?EstatsLocale::translate('Country').': <em>'.$Country.'</em><br />
':'').($Continent?EstatsLocale::translate('Continent').': <em>'.$Continent.'</em><br />
':'').EstatsLocale::translate('Co-ordinates').': <em>'.$Coordinates.'</em>
</span>
</a>
':''),
	), array(
	'referrer' => (boolean) $Data[$i]['referrer'],
	'keywords' => (boolean) $Data[$i]['keywords'],
	'robot' => (boolean) $Robot,
	'location' => (boolean) $Data[$i]['geolocation'],
	'technical' => (boolean) $Data[$i]['javascript'],
	));

	if (!$ShowDetails)
	{
		EstatsTheme::append('rows', $Entry);
	}
}

if ($ShowDetails)
{
	$Page = (int) (isset($Path[4])?$Path[4]:1);

	EstatsTheme::add('title', sprintf(EstatsLocale::translate('Visit details #%d'), $ShowID));

	if ($Page < 1 || $Page > ceil($Data[0]['visitsamount'] / EstatsCore::option('Visits|detailsamount')))
	{
		$Page = 1;
	}

	$Sites = EstatsCore::driver()->selectData(array('details'), array('time', 'address', array(EstatsDriver::ELEMENT_SUBQUERY, array(array('sites'), array('sites.name'), array(array(EstatsDriver::ELEMENT_OPERATION, array('sites.address', EstatsDriver::OPERATOR_EQUAL, 'details.address')))), 'title')), $Where, EstatsCore::option('Visits|detailsamount'), (EstatsCore::option('Visits|detailsamount') * ($Page - 1)), array('time' => FALSE));

	EstatsTheme::add('rows', '');

	for ($i = 0, $c = count($Sites); $i < $c; ++$i)
	{
		$Title = htmlspecialchars($Sites[$i][empty($Sites[$i]['title'])?'address':'title']);

		EstatsTheme::append('rows', EstatsTheme::parse(EstatsTheme::get('details-row'), array(
	'num' => ($Data[0]['visitsamount'] - $i - (($Page - 1) * EstatsCore::option('Visits|detailsamount'))),
	'date' => date('d.m.Y H:i:s', (is_numeric($Sites[$i]['time'])?$Sites[$i]['time']:strtotime($Sites[$i]['time']))),
	'title' => $Title,
	'link' => '<a href="'.htmlspecialchars($Sites[$i]['address']).'" tabindex="'.EstatsGUI::tabindex().'">'.EstatsGUI::cutString($Title, EstatsTheme::option('DetailsRowValueLength')).'</a>'
	)));
	}

	$PagesAmount = ceil($Data[0]['visitsamount'] / EstatsCore::option('Visits|detailsamount'));

	if ($PagesAmount > 1)
	{
		EstatsTheme::add('title', sprintf(EstatsLocale::translate('%s - page %d. of %d'), EstatsTheme::get('title'), $Page, $PagesAmount));
	}

	$OtherIDs = array();
	$PreviousVisit = $Data[0]['previous'];
	$NextVisit = $Data[0]['id'];
	$i = 0;

	while ($PreviousVisit && $i < 10)
	{
		$OtherIDs[] = $PreviousVisit;

		$PreviousVisit = EstatsCore::driver()->selectField('visitors', 'previous', array(array(EstatsDriver::ELEMENT_OPERATION, array('id', EstatsDriver::OPERATOR_EQUAL, $PreviousVisit))));

		++$i;
	}

	while ($NextVisit && $i < 20)
	{
		$NextVisit = EstatsCore::driver()->selectField('visitors', 'id', array(array(EstatsDriver::ELEMENT_OPERATION, array('previous', EstatsDriver::OPERATOR_EQUAL, $NextVisit))));

		if ($NextVisit)
		{
			$OtherIDs[] = $NextVisit;
		}

		++$i;
	}

	EstatsTheme::add('page', str_replace('{rowspan}', (count($Sites) + (($Data[0]['visitsamount'] > EstatsCore::option('Visits|detailsamount'))?3:2) - 1), $Entry));

	sort($OtherIDs);

	$Data = EstatsCore::driver()->selectData(array('visitors', array(EstatsDriver::JOIN_LEFT, EstatsDriver::OPERATOR_JOIN_USING, array('id')), 'details'), array('visitors.id', 'visitors.firstvisit', 'visitors.lastvisit', 'visitors.visitsamount', array(EstatsDriver::ELEMENT_FIELD, 'details.id', 'details')), array(array(EstatsDriver::ELEMENT_OPERATION, array('visitors.id', EstatsDriver::OPERATOR_IN, $OtherIDs))), 0, 0, array('visitors.lastvisit' => FALSE), NULL, NULL, TRUE);

	EstatsTheme::add('other-visits', (count($Data) > 0));

	if ($Data)
	{
		for ($i = 0, $c = count($Data); $i < $c; ++$i)
		{
			EstatsTheme::add('details-'.$Data[$i]['id'], $Data[$i]['details']);
			EstatsTheme::append('othervisits', EstatsTheme::parse(EstatsTheme::get('other-visits-row'), array(
	'id' => $Data[$i]['id'],
	'tabindex' => EstatsGUI::tabindex(),
	'first' => date('d.m.Y H:i:s', (is_numeric($Data[$i]['firstvisit'])?$Data[$i]['firstvisit']:strtotime($Data[$i]['firstvisit']))),
	'last' => date('d.m.Y H:i:s', (is_numeric($Data[$i]['lastvisit'])?$Data[$i]['lastvisit']:strtotime($Data[$i]['lastvisit']))),
	'amount' => (int) $Data[$i]['visitsamount'],
	)));
		}
	}

	EstatsTheme::add('links', (($PagesAmount > 1)?EstatsGUI::linksWIdget($Page, $PagesAmount, '{path}visits/visit/'.$ShowID.'/{page}{suffix}'):''));
}
else
{
	EstatsTheme::add('robotscheckbox', 'tabindex="'.EstatsGUI::tabindex().'"'.($ShowRobots?' checked="checked"':''));
	EstatsTheme::add('robotsformindex', EstatsGUI::tabindex());

	if ($PagesAmount > 1 && EstatsCore::option('Visits|maxpages') > 1)
	{
		EstatsTheme::add('title', sprintf(EstatsLocale::translate('%s - page %d. of %d'), EstatsTheme::get('title'), $Page, $PagesAmount));
	}

	EstatsTheme::add('links', (($PagesAmount > 1)?EstatsGUI::linksWIdget($Page, $PagesAmount, '{path}visits/'.$ShowRobots.'/{page}{suffix}'):''));

	if (!count($Data))
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