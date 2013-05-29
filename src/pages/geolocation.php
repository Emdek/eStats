<?php
/**
 * Geolocation information GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

$Regions = EstatsCore::loadData('share/data/regions.ini');

if (!EstatsGeolocation::isAvailable())
{
	estats_error_message('Extension unavailable!', __FILE__, __LINE__, 1);
}
else
{
	$Array = array();

	for ($i = 0, $c = count($AvailableCountries); $i < $c; ++$i)
	{
		$Array[$AvailableCountries[$i]] = EstatsGUI::itemText($AvailableCountries[$i], 'countries');
	}

	asort($Array);

	$MapsList = '';

	foreach ($Array as $Key => $Value)
	{
		$MapsList.= '<option'.(($Path[$Var - 1] == $Key)?' selected="selected"':'').' value="'.$Key.'">'.$Value.(is_file ('share/maps/'.$Key.'/map.ini')?' ('.EstatsLocale::translate('Map').')':'').'</option>
';
	}

	EstatsTheme::add('selectmap', '<select name="map" id="map" title="'.EstatsLocale::translate('Map view').'">
<optgroup label="'.EstatsLocale::translate('World').'">
<option'.(($Path[$Var - 1] == 'countries')?' selected="selected"':'').' value="countries">'.EstatsLocale::translate('Countries').' ('.EstatsLocale::translate('Map').')</option>
<option'.(($Path[$Var - 1] == 'continents')?' selected="selected"':'').' value="continents">'.EstatsLocale::translate('Continents').' ('.EstatsLocale::translate('Map').')</option>
</optgroup>
'.($MapsList?'<optgroup label="'.EstatsLocale::translate('Countries').'">
'.$MapsList.'</optgroup>
':'').'</select>
');

	$Map = ((!isset($Path[$Var - 1]) || !is_file('share/maps/'.$Path[$Var - 1].'/map.ini') || in_array($Path[$Var - 1], array('continents', 'countries')))?'world':$Path[$Var - 1]);
	$SingleCountry = !in_array($Path[$Var - 1], array('continents', 'countries'));

	EstatsTheme::add('singlecountry', $SingleCountry);
	EstatsTheme::add('map', (EstatsGraphics::isAvailable() && is_file('./share/maps/'.(in_array($Path[$Var - 1], array('continents', 'countries', 'cities'))?'world':$Path[$Var - 1]).'/map.ini')));

	if ($SingleCountry)
	{
		$GeolocationGroups = array('cities', 'regions',);
	}
	else
	{
		$GeolocationGroups = &$Groups['geolocation'];
	}

	EstatsTheme::add('maptype', (((isset($Path[$Var - 1]) && in_array($Path[$Var - 1], array('continents', 'countries', 'cities')) && $Path[2] == 'world') || in_array($Path[2], array('countries', 'continents')))?EstatsLocale::translate('World').(($Path[2] != 'world')?': '.EstatsLocale::translate(ucfirst($Path[2])):''):EstatsGUI::itemText($Path[2], 'countries')));
	EstatsTheme::append('title', ' - '.EstatsTheme::get('maptype'));

	if ($Var == 4 && isset($Path[$Var - 1]) && in_array($Path[$Var - 1], $GeolocationGroups))
	{
		EstatsTheme::load('group');
		EstatsTheme::load('chart');
		EstatsTheme::append('title', ': '.EstatsLocale::translate(ucfirst ($Path[$Var - 1])));
		EstatsTheme::add('group', EstatsGroup::create($Path[$Var - 1], $Path[$Var - 1].(($Path[$Var - 1] == 'regions' || ($Path[$Var - 1] == 'cities' && strlen($Path[2]) == 2))?'-'.$Path[2]:''), $Titles[$Path[$Var - 1]], $Date, (isset($Path[$Var + 1])?(int) $Path[$Var + 1]:1), TRUE, '{path}geolocation/'.(in_array($Path[2], array('countries', 'continents'))?'world':$Path[2]).'/'.$Path[$Var - 1].'/{date}{suffix}'));
		EstatsTheme::link('group-page', 'page');
	}
	else
	{
		for ($i = 0, $c = count($GeolocationGroups); $i < $c; ++$i)
		{
			EstatsTheme::add($GeolocationGroups[$i], EstatsGroup::create($GeolocationGroups[$i], $GeolocationGroups[$i].(($GeolocationGroups[$i] == 'regions' || ($GeolocationGroups[$i] == 'cities' && strlen($Path[2]) == 2))?'-'.$Path[2]:''), $Titles[$GeolocationGroups[$i]], $Date, 1, FALSE, '{path}geolocation/'.(in_array($Path[2], array('countries', 'continents'))?'world':$Path[2]).'/'.$GeolocationGroups[$i].'/{date}{suffix}'));
		}

		$Data['max'] = 0;

		if ($Path[$Var - 1] == 'continents')
		{
			$Data['continents'] = array_fill(0, 7, 0);
		}

		$MapData = array();

		for ($i = 0, $c = count($Data['data']); $i < $c; ++$i)
		{
			$MapData[$Data['data'][$i]['name']] = (int) $Data['data'][$i]['amount_current'];

			if ($Path[$Var - 1] == 'continents')
			{
				$Data['continents'][$Data['data'][$i]['continent']] += $Data['data'][$i]['amount_current'];

				if ($Data['continents'][$Data['data'][$i]['continent']] > $Data['max'])
				{
					$Data['max'] = $Data['continents'][$Data['data'][$i]['continent']];
				}
			}
			else if ($Data['data'][$i]['amount_current'] > $Data['max'])
			{
				$Data['max'] = (int) $Data['data'][$i]['amount_current'];
			}
		}

		$MapID = $Map.(($Map == 'world')?'-'.$Path[$Var - 1]:'');
		$MapInformation = EstatsCore::loadData('share/maps/'.$Map.'/map.ini', TRUE, FALSE);
		$Data['data'] = &$MapData;
		$Data['cities'] = &$Cities;

		EstatsTheme::add('mapid', $MapID);
		EstatsTheme::add('mapauthor', $MapInformation['Information']['Author']);
		EstatsTheme::add('maplink', $MapInformation['Information']['URL']);
		EstatsTheme::add('maptime', date('Y.m.d H:i:s', $MapInformation['Information']['Time']));
		EstatsTheme::add('maphrefs', '');
		EstatsTheme::add('maptooltips', '');

		$_SESSION[EstatsCore::session()]['imagedata']['geolocation-'.$MapID] = array(
		'type' => 'map',
		'data' => $Data,
		'map' => $Map.(($Map == 'world')?'-'.$Path[$Var - 1]:'')
		);

		if (isset($MapInformation['Cities']) && $Data['sum_current'])
		{
			foreach ($Data['cities']['data'] as $Key => $Value)
			{
				$Coordinates = number_format(round($Value['latitude'], 2), 2, '.', '').','.number_format(round($Value['longitude'], 2), 2, '.', '');

				if (!isset($MapInformation['Cities'][$Coordinates]))
				{
					continue;
				}

				$Amount = &$Value['amount_current'];
				$Entry = EstatsGUI::itemText($Value['name'], 'cities');
				$ID = md5($Key);

				EstatsTheme::append('maphrefs', '<area shape="circle" alt="'.$Entry.'" title="'.$Entry.' - '.$Amount.' '.round((($Amount / $Data['sum_current']) * 100), 2).'%" onmouseover="document.getElementById (\'geolocation_tooltip_'.$ID.'\').style.display = \'block\'" onmouseout="document.getElementById (\'geolocation_tooltip_'.$ID.'\').style.display = \'none\'" coords="'.$MapInformation['Cities'][$Coordinates].',4">
');

				$Icon = EstatsGUI::iconPath($Value['name'], 'cities');

				EstatsTheme::append('maptooltips', '<div id="geolocation_tooltip_'.$ID.'" class="maptooltip">
'.($Icon?EstatsGUI::iconTag($Icon, $Entry).'
':'').$Entry.' - '.$Amount.'
('.round ((($Amount / $Data['sum_current']) * 100), 2).'%)
</div>
');
			}
		}

		if ($Map == 'world')
		{
			$MapInformation['Coordinates'] = EstatsCore::loadData('share/maps/world/'.$Path[$Var - 1].'.ini', TRUE, FALSE);
		}

		if (isset($MapInformation['Coordinates']) && $Data['sum_current'])
		{
			foreach ($MapInformation['Coordinates'] as $Key => $Value)
			{
				$Amount = (isset($Data[($Path[$Var - 1] == 'continents')?'continents':'data'][(($Map != 'world')?$Path[$Var - 1].'-':'').$Key])?(int) $Data[($Path[$Var - 1] == 'continents')?'continents':'data'][(($Map != 'world')?$Path[$Var - 1].'-':'').$Key]:0);
				$Entry = EstatsGUI::itemText((($Map == 'world')?'':$Path[$Var - 1].'-').$Key, (($Map == 'world')?(($Path[$Var - 1] == 'continents')?'continents':'countries'):'regions'));

				EstatsTheme::append('maphrefs', '<area shape="poly" alt="'.$Entry.'" title="'.$Entry.($Amount?' - '.$Amount.($Amount?' ('.round((($Amount / $Data['sum_current']) * 100), 2).'%)" onmouseover="document.getElementById(\'geolocation_tooltip_'.$Key.'\').style.display = \'block\'" onmouseout="document.getElementById (\'geolocation_tooltip_'.$Key.'\').style.display = \'none\''.(($Map == 'world')?'" href="{path}geolocation/'.$Key.'/'.implode('-', $Date).'{suffix}':''):''):'').'" coords="'.$Value.'">
');

				if ($Amount)
				{
					EstatsTheme::append('maptooltips', '<div id="geolocation_tooltip_'.$Key.'" class="maptooltip">
'.(($Path[$Var - 1] == 'countries')?EstatsGUI::iconTag(EstatsGUI::iconPath($Key, 'countries'), EstatsGUI::itemText($Key, 'countries')).'
':'').$Entry.' - '.$Amount.'
('.round ((($Amount / $Data['sum_current']) * 100), 2).'%)
</div>
');
				}
			}
		}
	}
}

EstatsTheme::add('lang_fulllist', EstatsLocale::translate('Full list'));
EstatsTheme::add('lang_chart', EstatsLocale::translate('Chart'));
EstatsTheme::add('lang_map', EstatsLocale::translate('Map'));
EstatsTheme::add('lang_author', EstatsLocale::translate('Author'));
?>