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

$regions = EstatsCore::loadData('share/data/regions.ini');

if (!EstatsGeolocation::isAvailable())
{
	estats_error_message('Extension unavailable!', __FILE__, __LINE__, 1);
}
else
{
	$array = array();

	for ($i = 0, $c = count($availableCountries); $i < $c; ++$i)
	{
		$array[$availableCountries[$i]] = EstatsGUI::itemText($availableCountries[$i], 'countries');
	}

	asort($array);

	$mapsList = '';

	foreach ($array as $key => $value)
	{
		$mapsList.= '<option'.(($path[$var - 1] == $key)?' selected="selected"':'').' value="'.$key.'">'.$value.(is_file ('share/maps/'.$key.'/map.ini')?' ('.EstatsLocale::translate('Map').')':'').'</option>
';
	}

	EstatsTheme::add('selectmap', '<select name="map" id="map" title="'.EstatsLocale::translate('Map view').'">
<optgroup label="'.EstatsLocale::translate('World').'">
<option'.(($path[$var - 1] == 'countries')?' selected="selected"':'').' value="countries">'.EstatsLocale::translate('Countries').' ('.EstatsLocale::translate('Map').')</option>
<option'.(($path[$var - 1] == 'continents')?' selected="selected"':'').' value="continents">'.EstatsLocale::translate('Continents').' ('.EstatsLocale::translate('Map').')</option>
</optgroup>
'.($mapsList?'<optgroup label="'.EstatsLocale::translate('Countries').'">
'.$mapsList.'</optgroup>
':'').'</select>
');

	$map = ((!isset($path[$var - 1]) || !is_file('share/maps/'.$path[$var - 1].'/map.ini') || in_array($path[$var - 1], array('continents', 'countries')))?'world':$path[$var - 1]);
	$singleCountry = !in_array($path[$var - 1], array('continents', 'countries'));

	EstatsTheme::add('singlecountry', $singleCountry);
	EstatsTheme::add('map', (EstatsGraphics::isAvailable() && is_file('./share/maps/'.(in_array($path[$var - 1], array('continents', 'countries', 'cities'))?'world':$path[$var - 1]).'/map.ini')));

	if ($singleCountry)
	{
		$geolocationGroups = array('cities', 'regions',);
	}
	else
	{
		$geolocationGroups = &$groups['geolocation'];
	}

	EstatsTheme::add('maptype', (((isset($path[$var - 1]) && in_array($path[$var - 1], array('continents', 'countries', 'cities')) && $path[2] == 'world') || in_array($path[2], array('countries', 'continents')))?EstatsLocale::translate('World').(($path[2] != 'world')?': '.EstatsLocale::translate(ucfirst($path[2])):''):EstatsGUI::itemText($path[2], 'countries')));
	EstatsTheme::append('title', ' - '.EstatsTheme::get('maptype'));

	if ($var == 4 && isset($path[$var - 1]) && in_array($path[$var - 1], $geolocationGroups))
	{
		EstatsTheme::load('group');
		EstatsTheme::load('chart');
		EstatsTheme::append('title', ': '.EstatsLocale::translate(ucfirst ($path[$var - 1])));
		EstatsTheme::add('group', EstatsGroup::create($path[$var - 1], $path[$var - 1].(($path[$var - 1] == 'regions' || ($path[$var - 1] == 'cities' && strlen($path[2]) == 2))?'-'.$path[2]:''), $titles[$path[$var - 1]], $date, (isset($path[$var + 1])?(int) $path[$var + 1]:1), TRUE, '{path}geolocation/'.(in_array($path[2], array('countries', 'continents'))?'world':$path[2]).'/'.$path[$var - 1].'/{date}{suffix}'));
		EstatsTheme::link('group-page', 'page');
	}
	else
	{
		for ($i = 0, $c = count($geolocationGroups); $i < $c; ++$i)
		{
			EstatsTheme::add($geolocationGroups[$i], EstatsGroup::create($geolocationGroups[$i], $geolocationGroups[$i].(($geolocationGroups[$i] == 'regions' || ($geolocationGroups[$i] == 'cities' && strlen($path[2]) == 2))?'-'.$path[2]:''), $titles[$geolocationGroups[$i]], $date, 1, FALSE, '{path}geolocation/'.(in_array($path[2], array('countries', 'continents'))?'world':$path[2]).'/'.$geolocationGroups[$i].'/{date}{suffix}'));
		}

		$data['max'] = 0;

		if ($path[$var - 1] == 'continents')
		{
			$data['continents'] = array_fill(0, 7, 0);
		}

		$mapData = array();

		for ($i = 0, $c = count($data['data']); $i < $c; ++$i)
		{
			$mapData[$data['data'][$i]['name']] = (int) $data['data'][$i]['amount_current'];

			if ($path[$var - 1] == 'continents')
			{
				$data['continents'][$data['data'][$i]['continent']] += $data['data'][$i]['amount_current'];

				if ($data['continents'][$data['data'][$i]['continent']] > $data['max'])
				{
					$data['max'] = $data['continents'][$data['data'][$i]['continent']];
				}
			}
			else if ($data['data'][$i]['amount_current'] > $data['max'])
			{
				$data['max'] = (int) $data['data'][$i]['amount_current'];
			}
		}

		$mapID = $map.(($map == 'world')?'-'.$path[$var - 1]:'');
		$mapInformation = EstatsCore::loadData('share/maps/'.$map.'/map.ini', TRUE, FALSE);
		$data['data'] = &$mapData;
		$data['cities'] = &$cities;

		EstatsTheme::add('mapid', $mapID);
		EstatsTheme::add('mapauthor', $mapInformation['Information']['Author']);
		EstatsTheme::add('maplink', $mapInformation['Information']['URL']);
		EstatsTheme::add('maptime', date('Y.m.d H:i:s', $mapInformation['Information']['Time']));
		EstatsTheme::add('maphrefs', '');
		EstatsTheme::add('maptooltips', '');

		$_SESSION[EstatsCore::session()]['imagedata']['geolocation-'.$mapID] = array(
		'type' => 'map',
		'data' => $data,
		'map' => $map.(($map == 'world')?'-'.$path[$var - 1]:'')
		);

		if (isset($mapInformation['Cities']) && $data['sum_current'])
		{
			foreach ($data['cities']['data'] as $key => $value)
			{
				$coordinates = number_format(round($value['latitude'], 2), 2, '.', '').','.number_format(round($value['longitude'], 2), 2, '.', '');

				if (!isset($mapInformation['Cities'][$coordinates]))
				{
					continue;
				}

				$amount = &$value['amount_current'];
				$entry = EstatsGUI::itemText($value['name'], 'cities');
				$iD = md5($key);

				EstatsTheme::append('maphrefs', '<area shape="circle" alt="'.$entry.'" title="'.$entry.' - '.$amount.' '.round((($amount / $data['sum_current']) * 100), 2).'%" onmouseover="document.getElementById (\'geolocation_tooltip_'.$iD.'\').style.display = \'block\'" onmouseout="document.getElementById (\'geolocation_tooltip_'.$iD.'\').style.display = \'none\'" coords="'.$mapInformation['Cities'][$coordinates].',4">
');

				$icon = EstatsGUI::iconPath($value['name'], 'cities');

				EstatsTheme::append('maptooltips', '<div id="geolocation_tooltip_'.$iD.'" class="maptooltip">
'.($icon?EstatsGUI::iconTag($icon, $entry).'
':'').$entry.' - '.$amount.'
('.round ((($amount / $data['sum_current']) * 100), 2).'%)
</div>
');
			}
		}

		if ($map == 'world')
		{
			$mapInformation['Coordinates'] = EstatsCore::loadData('share/maps/world/'.$path[$var - 1].'.ini', TRUE, FALSE);
		}

		if (isset($mapInformation['Coordinates']) && $data['sum_current'])
		{
			foreach ($mapInformation['Coordinates'] as $key => $value)
			{
				$amount = (isset($data[($path[$var - 1] == 'continents')?'continents':'data'][(($map != 'world')?$path[$var - 1].'-':'').$key])?(int) $data[($path[$var - 1] == 'continents')?'continents':'data'][(($map != 'world')?$path[$var - 1].'-':'').$key]:0);
				$entry = EstatsGUI::itemText((($map == 'world')?'':$path[$var - 1].'-').$key, (($map == 'world')?(($path[$var - 1] == 'continents')?'continents':'countries'):'regions'));

				EstatsTheme::append('maphrefs', '<area shape="poly" alt="'.$entry.'" title="'.$entry.($amount?' - '.$amount.($amount?' ('.round((($amount / $data['sum_current']) * 100), 2).'%)" onmouseover="document.getElementById(\'geolocation_tooltip_'.$key.'\').style.display = \'block\'" onmouseout="document.getElementById (\'geolocation_tooltip_'.$key.'\').style.display = \'none\''.(($map == 'world')?'" href="{path}geolocation/'.$key.'/'.implode('-', $date).'{suffix}':''):''):'').'" coords="'.$value.'">
');

				if ($amount)
				{
					EstatsTheme::append('maptooltips', '<div id="geolocation_tooltip_'.$key.'" class="maptooltip">
'.(($path[$var - 1] == 'countries')?EstatsGUI::iconTag(EstatsGUI::iconPath($key, 'countries'), EstatsGUI::itemText($key, 'countries')).'
':'').$entry.' - '.$amount.'
('.round ((($amount / $data['sum_current']) * 100), 2).'%)
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