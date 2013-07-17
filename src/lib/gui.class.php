<?php
/**
 * GUI class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 5.0.00
 */

class EstatsGUI
{

/**
 * Field type custom
 */

	const FIELD_CUSTOM = 0;

/**
 * Field type boolean value
 */

	const FIELD_BOOLEAN = 1;

/**
 * Field type one line value
 */

	const FIELD_VALUE = 2;

/**
 * Field type multi line value
 */

	const FIELD_MULTILINE = 3;

/**
 * Field type array value
 */

	const FIELD_ARRAY = 4;

/**
 * Field type array value
 */

	const FIELD_SELECT = 5;

/**
 * Contains notifications
 */

	static private $notifications;

/**
 * Generates cache for icons information
 * @param string Type
 * @return array
 */

	private static function iconCache($type)
	{
		$fileName = 'icons-'.$type;

		if (EstatsCache::status($fileName, 31536000))
		{
			$data = array();
			$array = EstatsCore::loadData('share/data/'.$type.'.ini');

			foreach ($array as $key => $value)
			{
				if (isset($value['icon']))
				{
					$data[strtolower(str_replace('.', ' ', $key))] = $value['icon'];
				}
			}

			EstatsCache::save($fileName, $data);
		}
		else
		{
			$data = EstatsCache::read($fileName);
		}

		return $data;
	}

/**
 * Generates icon tag
 * @param string FileName
 * @param string Title
 * @return string
 */

	static function iconTag($fileName, $title = '')
	{
		if (!is_dir('./share/icons/') || !EstatsTheme::option('Icons'))
		{
			return '';
		}

		return '<img src="'.EstatsTheme::get('datapath').$fileName.'" alt="'.$title.'"'.($title?' title="'.$title.'"':'').'>';
	}

/**
 * Generates icon path
 * @param string Icon
 * @param string Category
 * @return string
 */

	static function iconPath($icon, $category)
	{
		$icon = str_replace('/', '', strtolower(trim($icon)));

		switch ($category)
		{
			case 'cities':
				if (substr($icon, -3, 1) != '-')
				{
					return '';
				}

				$category = 'countries';
				$icon = substr($icon, -2);
			break;
			case 'continents':
				$continents = EstatsCore::loadData('share/data/continents.ini');
				$icon = (isset($continents[$icon])?$continents[$icon]:'');
			break;
			case 'languages':
				$languageToCountry = EstatsCore::loadData('share/data/language-to-country.ini');
				$countries = EstatsCore::loadData('share/data/countries.ini');
				$category = 'countries';
				$language = explode('-', strtolower($icon));

				if (isset($language[1]) && isset($countries[$language[1]]))
				{
					$icon = $language[1];
				}
				else if (isset($languageToCountry[$language[0]]))
				{
					$icon = $languageToCountry[$language[0]];
				}
				else
				{
					$icon = '?';
				}
			break;
			case 'screens':
				$category = 'miscellaneous';
				$array = array(0, 800, 1024, 1280, 1600, 5000);
				$screens = array('smallest', 'small', 'medium', 'big', 'biggest');

				if ((int) $icon)
				{
					for ($i = 0; $i < 5; ++$i)
					{
						if ((int) $icon >= $array[$i] &&(int) $icon < $array[$i + 1])
						{
							$icon = 'screen_'.$screens[$i];

							break;
						}
					}
				}
				else
				{
					$icon = '?';
				}
			break;
			case 'browser-versions':
				$icon = preg_replace('#\s[\d\.]+\w*$#', '', $icon);
			case 'browsers':
				$category = 'browsers';
				$array = self::iconCache('browsers');

				if (isset($array[$icon]))
				{
					$icon = $array[$icon];
				}
			break;
			case 'operatingsystem-versions':
			case 'operatingsystems':
				$category = 'operatingsystems';
				$array = self::iconCache('operating-systems');

				if (isset($array[$icon]))
				{
					$icon = $array[$icon];
				}
				else if (strstr(trim($icon), ' '))
				{
					$array = explode(' ', $icon);

					if (is_file('./share/icons/operatingsystems/'.$array[1].'.png'))
					{
						$icon = &$array[1];
					}
					else
					{
						$icon = &$array[0];
					}
				}
			break;
			case 'robots':
				$array = self::iconCache('robots');

				if (isset($array[$icon]))
				{
					$icon = $array[$icon];
				}
			break;
			case 'pages':
				if (!is_file('./share/icons/pages/'.$icon.'.png'))
				{
					$icon = 'plugin';
				}
			break;
			case 'countries':
				$category = 'flags';
			break;
			case 'miscellaneous':
			break;
			default:
				return '';
		}

		$icon = str_replace(' ', '', $icon);

		if ($icon == '?' || !is_file('./share/icons/'.$category.'/'.$icon.'.png'))
		{
			$icon = 'unknown';
			$category = 'miscellaneous';
		}

		if ($category == 'miscellaneous' && is_file('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/icons/'.$icon.'.png'))
		{
			return 'share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/icons/'.$icon.'.png';
		}

		return 'share/icons/'.$category.'/'.$icon.'.png';
	}

/**
 * Generates item name text
 * @param string String
 * @param string Category
 * @return string
 */

	static function itemText($string, $category)
	{
		if (trim($string) == '?')
		{
			return (($category == 'referrers')?EstatsLocale::translate('Direct entries'):EstatsLocale::translate('Unknown'));
		}
		else if (in_array($category, array('java', 'javascript', 'cookies')))
		{
			return ($string?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
		}
		else if ($category == 'flash' && !$string)
		{
			return EstatsLocale::translate('No');
		}

		$countries = EstatsCore::loadData('share/data/countries.ini');

		switch ($category)
		{
			case 'time':
				$array = array(
	'views' => EstatsLocale::translate('Views'),
	'returns' => EstatsLocale::translate('Returns'),
	'unique' => EstatsLocale::translate('Unique'),
	'current' => EstatsLocale::translate('Current period'),
	'previous' => EstatsLocale::translate('Previous period'),
	);
				if (isset($array[$string]))
				{
					$string = $array[$string];
				}
			break;
			case 'cities':
				$country = (int) ($string[strlen($string) - 3] == '-');
				$city = ($country?substr($string, 0, -3):$string);
				$string = (function_exists('utf8_encode')?utf8_encode($city):$city).($country?', '.EstatsLocale::translate($countries[substr($string, -2)]):'');
			break;
			case 'countries':
				$string = (isset($countries[$string])?EstatsLocale::translate($countries[$string]):EstatsLocale::translate('Unknown'));
			break;
			case 'continents':
				$continents = EstatsCore::loadData('share/data/continents.ini');
				$string = ($string?EstatsLocale::translate($continents[$string]):EstatsLocale::translate('Unknown'));
			break;
			case 'languages':
				$languages = EstatsCore::loadData('share/data/languages.ini');
				$language = explode('-', strtolower($string));

				if (isset($languages[$language[0]]))
				{
					$string = EstatsLocale::translate($languages[$language[0]]);

					if (isset($language[1]) && isset($countries[$language[1]]))
					{
						$string.= ' ('.EstatsLocale::translate($countries[$language[1]]).')';
					}
				}
				else
				{
					$string = EstatsLocale::translate('Unknown');
				}
			break;
			case 'operatingsystems':
				if ($string == 'mobile')
				{
					$string = EstatsLocale::translate('Mobile devices');
				}
			break;
			case 'regions':
				$regions = EstatsCore::loadData('share/data/regions.ini');
				$region = explode('-', $string);
				$string = (isset($regions[$region[0]][$region[1]])?$regions[$region[0]][$region[1]]:EstatsLocale::translate('Unknown'));
			break;
			case 'operatingsystem-versions':
				if ($string == 'mobile')
				{
					$string = EstatsLocale::translate('Mobile devices');
				}
				else if (substr($string, 0, 6) == 'mobile')
				{
					$string = substr($string, 7);
				}
			break;
		}

		return $string;
	}

/**
 * Calculates percent difference between values
 * @param integer Current
 * @param integer Before
 * @return string
 */

	static function formatDifference($current, $before)
	{
		if ($current == $before)
		{
			return 0;
		}
		else if (!$before)
		{
			return 100;
		}
		else if (!$current)
		{
			return -100;
		}
		else if ($current < $before)
		{
			return -round(((($before - $current) / $before) * 100), 2);
		}
		else
		{
			return round((($current / $before) * 100), 2);
		}
	}

/**
 * Formats number
 * @param float $number
 * @param boolean Tag
 * @return string
 */

	static function formatNumber($number, $tag = TRUE)
	{
		$value = (($number < 1000)?round($number, 2):(($number < 1000000)?(round($number / 1000, 1)).'K':(round($number / 1000000, 1)).'M'));

		if ($tag)
		{
			return '<em'.(($number >= 1000 || is_float($number))?' title="'.round($number, 5).'"':'').'>'.$value.'</em>';
		}

		return $value;
	}

/**
 * Formats size
 * @param integer Size
 * @param boolean Title
 * @return string
 */

	static function formatSize($size, $title = TRUE)
	{
		return ($title?'<span title="'.number_format($size, 0, '', ' ').' B">':'').(($size > 1024)?(($size > 1048576)?(($size > 1073741824)?round($size / 1073741824, 2).' GB':round($size / 1048576, 2).' MB'):round($size / 1024, 2).' KB'):(int) $size.' B').($title?'</span>':'');
	}

/**
 * Cuts string to given length
 * @param string String
 * @param integer Length
 * @param boolean Title
 * @param boolean Dots
 * @return string
 */

	static function cutString($string, $length, $title = FALSE, $dots = TRUE)
	{
		if (!$length)
		{
			return htmlspecialchars($string);
		}

		if (function_exists('mb_substr'))
		{
			return (mb_strwidth($string, 'UTF-8') > ($length + 3) || !$dots)?($title?'<span title="'.htmlspecialchars($string).'">'.htmlspecialchars(mb_substr($string, 0, $length, 'UTF-8')).($dots?'...':'').'</span>':htmlspecialchars(mb_substr($string, 0, $length, 'UTF-8')).($dots?'...':'')):htmlspecialchars($string);
		}
		else
		{
			return (strlen($string) > ($length + 3) || !$dots)?($title?'<span title="'.htmlspecialchars($string).'">'.htmlspecialchars(substr_replace($string, ($dots?'...':''), $length)).'</span>':htmlspecialchars(substr_replace($string, ($dots?'...':''), $length))):htmlspecialchars($string);
		}
	}

/**
 * Returns IP ignore rule link
 * @param array IPs
 * @param strin IP
 * @param boolean Ignored
 * @return string
 */

	static function ignoreIPLink($iPs, $iP, $ignored = TRUE)
	{
		for ($i = 0, $c = count($iPs); $i < $c; ++$i)
		{
			if ($iP == $iPs[$i] || (strstr($iPs[$i], '*') && substr($iP, 0, (strlen($iPs[$i]) - 1)) == substr($iPs[$i], 0, -1)))
			{
				return '<a href="{selfpath}{separator}'.($ignored?'ignored':'blocked').'IP='.htmlspecialchars($iPs[$i]).'" class="green" title="'.(($iP == $iPs[$i])?EstatsLocale::translate('Unblock IP'):EstatsLocale::translate('Unblock IPs range')).(($iP == $iPs[$i])?'':' ('.htmlspecialchars($iPs[$i]).')').'"><strong>&#187;</strong></a>';
			}
		}

		return '<a href="{selfpath}{separator}'.($ignored?'ignored':'blocked').'IP='.$iP.'" class="red" title="'.EstatsLocale::translate('Block this IP').'" onclick="if (!confirm (\''.EstatsLocale::translate('Do You really want to ban this IP address?').'\')) return false"><strong>&#187;</strong></a>';
	}

/**
 * Returns whois link
 * @param string Data
 * @param string String
 * @return string
 */

	static function whoisLink($data, $string = '')
	{
		return '<a href="'.str_replace('{data}', htmlspecialchars($data), EstatsCore::option('WhoisLink')).'" title="'.EstatsLocale::translate('Whois').'">'.($string?$string:EstatsLocale::translate('Whois')).'</a>';
	}

/**
 * Returns map link
 * @param float Latitude
 * @param float Longitude
 * @return string
 */

	static function mapLink($latitude, $longitude)
	{
		return str_replace(array('{latitude}', '{longitude}'), array(number_format($latitude, 2, '.', ''), number_format($longitude, 2, '.', '')), htmlspecialchars(EstatsCore::option('MapLink')));
	}

/**
 * Calculates time range
 * @param integer Year
 * @param integer Month
 * @param integer Day
 * @param integer Hour
 * @return array
 */

	static function timeRange($year = 0, $month = 0, $day = 0, $hour = 0)
	{
		if (!$year)
		{
			return array(0, $_SERVER['REQUEST_TIME']);
		}

		if ($month)
		{
			if ($day)
			{
				if ($hour)
				{
					$from = strtotime($year.'-'.(($month < 10)?'0':'').$month.'-'.(($day < 10)?'0':'').$day.' '.(($hour < 10)?'0':'').$hour.':00');

					return array($from, ($from + 3600));
				}
				else
				{
					$from = strtotime($year.'-'.(($month < 10)?'0':'').$month.'-'.(($day < 10)?'0':'').$day);

					return array($from, ($from + 86400));
				}
			}
			else
			{
				$from = strtotime($year.'-'.(($month < 10)?'0':'').$month.'-01');

				return array($from, ($from + (date('t', $from) * 86400)));
			}
		}

		return array(strtotime($year.'-01-01'), strtotime(($year + 1).'-01-01'));
	}

/**
 * Calculates time unit and timestamp
 * @param string Period
 * @param integer Timestamp
 * @param integer Step
 * @param string Format
 * @param boolean CurrentTime
 * @return array
 */

	static function timeUnit($period, $timestamp, $step, $format, $currentTime)
	{
		$popularity = in_array($period, array('hours', 'weekdays'));

		if ($period == 'weekdays' && EstatsLocale::option('WeekStartDay'))
		{
			$weekDayTransition = range(0, 6);
			$weekDayTransition = array_merge(array_slice($weekDayTransition, EstatsLocale::option('WeekStartDay')), array_slice($weekDayTransition, 0, EstatsLocale::option('WeekStartDay')));
		}

		if ($period == 'year')
		{
			$step = (date('t', $timestamp) * 86400);
		}
		else if ($period == 'years')
		{
			$step = ((date('L', $timestamp) + 365) * 86400);
		}

		if (($currentTime || $popularity) && $step)
		{
			$timestamp += $step;
		}

		if ($period == 'hours')
		{
			$unitID = (($timestamp < 10)?'0':'').$timestamp;
		}
		else if ($period == 'weekdays')
		{
			if (EstatsLocale::option('WeekStartDay'))
			{
				$unitID = $weekDayTransition[$timestamp];
			}
			else
			{
				$unitID = $timestamp;
			}
		}
		else
		{
			$unitID = date($format, $timestamp);
		}

		if (!$currentTime && !$popularity && $step)
		{
			$timestamp += $step;
		}

		return array($unitID, $timestamp);
	}

/**
 * Generates links block
 * @param integer Page
 * @param integer Amount
 * @param string Path
 * @return string
 */

	static function linksWidget($page, $amount, $path)
	{
		if ($amount < 2)
		{
			return '';
		}

		$locale = array(
	'first' => EstatsLocale::translate('Go to first page (%d.)'),
	'previous' => EstatsLocale::translate('Go to previous page (%d.)'),
	'next' => EstatsLocale::translate('Go to next page (%d.)'),
	'last' => EstatsLocale::translate('Go to last page (%d.)'),
	'default' => EstatsLocale::translate('Go to page %d.')
	);
		$array = array(
	'first' => '&laquo;',
	'previous' => '&lsaquo;',
	'next' => '&rsaquo;',
	'last' => '&raquo;'
	);
		$tmpArray = array_merge(array('first' => 1, 'previous' => ($page - 1)), range(($page - (($page == 5)?4:3)), ($page + (($page == ($amount - 4))?4:3))), array('next' => ($page + 1), 'last' => $amount));
		$links = array();

		foreach ($tmpArray as $key => $value)
		{
			if (is_numeric($key))
			{
				$key = $value;
			}

			if (!is_numeric($key) || ($key > 0 && $key <= $amount))
			{
				if ($value > 0 && $value <= $amount && $value != $page)
				{
					$links[] = '<a href="'.str_replace('{page}', $value, $path).'" title="'.sprintf($locale[is_numeric($key)?'default':$key], $value).'">'.(is_numeric($key)?$key:$array[$key]).'</a>';
				}
				else
				{
					$links[] = '<strong>'.(is_numeric($key)?$key:$array[$key]).'</strong>';
				}

				if (($page > 4 && $key == 'previous' && $page != 5) || ($page < ($amount - 3) && $key == ($page + 3) && $page != ($amount - 4)))
				{
					$links[] = '...';
				}
			}
		}

		return str_replace('{links}', implode('
|
', $links), EstatsTheme::get('links'));
	}

/**
 * Generates announcement
 * @param string Content
 * @param string Type
 * @return string
 */

	static function notificationWidget($content, $type)
	{
		$locale = array(
			'warning' => EstatsLocale::translate('Warning'),
			'success' => EstatsLocale::translate('Success'),
			'error' => EstatsLocale::translate('Error'),
			'information' => EstatsLocale::translate('Information'),
			'loading' => EstatsLocale::translate('Loading...'),
			);

		$type = str_replace('.', '', $type);

		return EstatsTheme::parse(EstatsTheme::get('announcement'), array(
	'class' => $type,
	'type' => $locale[$type],
	'content' => $content
	));
	}

/**
 * Generates configuration option entry
 * @param string Label
 * @param string Description
 * @param string Name
 * @param string Value
 * @param integer Type
 * @param mixed Options
 * @param string Default
 * @return string
 */

	static function optionRowWidget($label, $description, $name, $value = '', $type = self::FIELD_VALUE, $options = NULL, $default = NULL)
	{
 		$iD = str_replace(array('[]', '|'), '', $name);

		if (!is_array($value) && $type != self::FIELD_CUSTOM)
		{
			$value = str_replace(array('{', '}'), array('&#123;', '&#125;'), $value);
		}

		switch ($type)
		{
			case self::FIELD_CUSTOM:
				$form = &$value;
			break;
			case self::FIELD_BOOLEAN:
				$form = '<input type="checkbox" name="'.$name.'" id="F_'.$iD.'" value="1"'.($value?' checked="checked"':'').(($default !== NULL)?' onchange="checkDefault(\''.$iD.'\', '.($default?1:0).')"':'').'>';
			break;
			case self::FIELD_VALUE:
				$form = '<input'.(stristr($name, 'pass')?' type="password"':'').' name="'.$name.'" id="F_'.$iD.'" value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8', FALSE).'"'.(($default !== NULL)?' onkeyup="checkDefault(\''.$iD.'\', \''.str_replace(array("\r\n", "\n", '{', '}'), array('\r\n', '\n', '&#123;', '&#125;'), htmlspecialchars($default, ENT_QUOTES, 'UTF-8')).'\')"':'').'>';
			break;
			case self::FIELD_SELECT:
				$form = '<select name="'.$name.'" id="F_'.$iD.'"'.(is_array($value)?' multiple="multiple" size="3"':'').(($default !== NULL)?' onchange="checkDefault(\''.$iD.'\', \''.str_replace(array('{', '}'), array('&#123;', '&#125;'), htmlspecialchars($default, ENT_QUOTES, 'UTF-8')).'\')"':'').'>
';

				for ($i = 0, $c = count($options); $i < $c; ++$i)
				{
					if (empty($options[$i]))
					{
						continue;
					}

					if (is_array($options[$i]))
					{
						$text = $options[$i][1];
						$options[$i] = $options[$i][0];
					}
					else
					{
						$text = $options[$i];
					}

					$select = (is_array($value)?in_array($options[$i], $value):($options[$i] == $value));
					$form.= '<option'.(($text != $options[$i])?' value="'.htmlspecialchars($options[$i]).'"':'').($select?' selected="selected"':'').'>'.htmlspecialchars($text).'</option>
';
				}

				$form.= '</select>';
			break;
			case self::FIELD_MULTILINE:
			case self::FIELD_ARRAY:
				$form = '<textarea rows="1" cols="25" name="'.$name.'" id="F_'.$iD.'"'.(($type == self::FIELD_ARRAY)?' title="'.EstatsLocale::translate('Array, elements separated by |').'"':'').(($default !== NULL)?' onkeyup="checkDefault(\''.$iD.'\', \''.str_replace(array("\r\n", "\n", '{', '}'), array('\r\n', '\n', '&#123;', '&#125;'), htmlspecialchars($default, ENT_QUOTES, 'UTF-8')).'\')"':'').'>'.htmlspecialchars((is_array($value)?implode('|', $value):$value), ENT_QUOTES, 'UTF-8', FALSE).'</textarea>';
			break;
			default:
				return '';
			break;
		}

		if ($default !== NULL)
		{
			$form.= '
<input type="button" value="'.EstatsLocale::translate('Default').'"  onclick="setDefault(\''.$iD.'\', \''.str_replace(array("\r\n", "\n", '{', '}'), array('\r\n', '\n', '&#123;', '&#125;'), htmlspecialchars($default, ENT_QUOTES, 'UTF-8')).'\')" title="'.EstatsLocale::translate('Default value').': '.htmlspecialchars(str_replace(array("\r\n", "\n", '{', '}'), array(' ', ' ', '&#123;', '&#125;'), $default)).'">';
		}

		return EstatsTheme::parse(EstatsTheme::get('option-row'), array(
	'changed' => (($default === NULL || str_replace(array('{', '}', '\r\n'), array('&#123;', '&#125;', "\r\n"),$default) == (is_array($value)?implode('|', $value):$value))?'':' class="changed" title="'.EstatsLocale::translate('Field value is other than default').'"'),
	'id' => &$iD,
	'form' => &$form,
	'option' => &$label,
	'description' => ($description?'<br>
<dfn>'.$description.'</dfn>':'')
	));
	}

/**
 * Checks tool availability level
 * @param string Tool
 * @return integer
 */

	static function toolLevel($tool)
	{
		if (is_file('./plugins/tools/'.$tool.'/plugin.ini'))
		{
			$information = EstatsCore::loadData('plugins/tools/'.$tool.'/plugin.ini', FALSE, FALSE);

			if (isset($information['Level']))
			{
				return $information['Level'];
			}
			else
			{
				return 100;
			}
		}
		else
		{
			return 100;
		}
	}

/**
 * Prepares configuration data to save
 * @param array Options
 * @param array Values
 * @param boolean RestoreDefaults
 */

	static function saveConfiguration($options, $values, $restoreDefaults = FALSE)
	{
		if ($restoreDefaults)
		{
			$defaults = EstatsCore::loadData('share/data/configuration.ini');
			$defaults = array_merge($defaults['Core'], $defaults['GUI']);

			if (count($defaults) == 0)
			{
				return;
			}
		}

		$configuration = array();

		for ($i = 0, $c = count($options); $i < $c; ++$i)
		{
			if ($restoreDefaults)
			{
				$configuration[$options[$i]] = str_replace('\r\n', "\r\n", $defaults[$options[$i]]['value']);
			}
			else
			{
				$configuration[$options[$i]] = (isset($values[$options[$i]])?stripslashes($values[$options[$i]]):0);
			}
		}

		EstatsCore::setConfiguration($configuration);
		EstatsCore::logEvent(EstatsCore::EVENT_CONFIGURATIONCHANGED);
		EstatsGUI::notify(EstatsLocale::translate('Configuration saved successfully.'), 'success');
	}

/**
 * Creates notification
 * @param string Message
 * @param string Type
 */

	static function notify($message, $type)
	{
		self::$notifications[] = array($message, $type);
	}

/**
 * Returns notifications data
 * @return array
 */
	static function notifications()
	{
		return self::$notifications;
	}
}
?>