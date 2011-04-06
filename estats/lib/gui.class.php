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

	static private $Notifications;

/**
 * Generates cache for icons information
 * @param string Type
 * @return array
 */

	private static function iconCache($Type)
	{
		$FileName = 'icons-'.$Type;

		if (EstatsCache::status($FileName, 31536000))
		{
			$Data = array();
			$Array = EstatsCore::loadData('share/data/'.$Type.'.ini');

			foreach ($Array as $Key => $Value)
			{
				if (isset($Value['icon']))
				{
					$Data[str_replace('.', ' ', $Key)] = $Value['icon'];
				}
			}

			EstatsCache::save($FileName, $Data);
		}
		else
		{
			$Data = EstatsCache::read($FileName);
		}

		return $Data;
	}

/**
 * Generates icon tag
 * @param string FileName
 * @param string Title
 * @return string
 */

	static function iconTag($FileName, $Title = '')
	{
		if (!is_dir('./share/icons/') || !EstatsTheme::option('Icons'))
		{
			return '';
		}

		return '<img src="'.EstatsTheme::get('datapath').$FileName.'" alt="'.$Title.'"'.($Title?' title="'.$Title.'"':'').' />';
	}

/**
 * Generates icon path
 * @param string Icon
 * @param string Category
 * @return string
 */

	static function iconPath($Icon, $Category)
	{
		$Icon = str_replace('/', '', strtolower(trim($Icon)));

		switch ($Category)
		{
			case 'cities':
				if (substr($Icon, -3, 1) != '-')
				{
					return '';
				}

				$Category = 'countries';
				$Icon = substr($Icon, -2);
			break;
			case 'continents':
				$Continents = EstatsCore::loadData('share/data/continents.ini');
				$Icon = (isset($Continents[$Icon])?$Continents[$Icon]:'');
			break;
			case 'languages':
				$LanguageToCountry = EstatsCore::loadData('share/data/language-to-country.ini');
				$Countries = EstatsCore::loadData('share/data/countries.ini');
				$Category = 'countries';
				$Language = explode('-', strtolower($Icon));

				if (isset($Language[1]) && isset($Countries[$Language[1]]))
				{
					$Icon = $Language[1];
				}
				else if (isset($LanguageToCountry[$Language[0]]))
				{
					$Icon = $LanguageToCountry[$Language[0]];
				}
				else
				{
					$Icon = '?';
				}
			break;
			case 'screens':
				$Category = 'miscellaneous';
				$Array = array(0, 800, 1024, 1280, 1600, 5000);
				$Screens = array('smallest', 'small', 'medium', 'big', 'biggest');

				if ((int) $Icon)
				{
					for ($i = 0; $i < 5; ++$i)
					{
						if ((int) $Icon >= $Array[$i] &&(int) $Icon < $Array[$i + 1])
						{
							$Icon = 'screen_'.$Screens[$i];

							break;
						}
					}
				}
				else
				{
					$Icon = '?';
				}
			break;
			case 'browser-versions':
				$Icon = preg_replace('#\s[\d\.]+\w*$#', '', $Icon);
			case 'browsers':
				$Category = 'browsers';
				$Array = self::iconCache('browsers');

				if (isset($Array[$Icon]))
				{
					$Icon = $Array[$Icon];
				}
			break;
			case 'operatingsystem-versions':
			case 'operatingsystems':
				$Category = 'operatingsystems';
				$Array = self::iconCache('operating-systems');

				if (isset($Array[$Icon]))
				{
					$Icon = $Array[$Icon];
				}
				else if (strstr(trim($Icon), ' '))
				{
					$Array = explode(' ', $Icon);

					if (is_file('./share/icons/operatingsystems/'.$Array[1].'.png'))
					{
						$Icon = &$Array[1];
					}
					else
					{
						$Icon = &$Array[0];
					}
				}
			break;
			case 'robots':
				$Array = self::iconCache('robots');

				if (isset($Array[$Icon]))
				{
					$Icon = $Array[$Icon];
				}
			break;
			case 'pages':
				if (!is_file('./share/icons/pages/'.$Icon.'.png'))
				{
					$Icon = 'plugin';
				}
			case 'countries':
			case 'miscellaneous':
			break;
			default:
				return '';
		}

		$Icon = str_replace(' ', '', $Icon);

		if ($Icon == '?' || !is_file('./share/icons/'.$Category.'/'.$Icon.'.png'))
		{
			$Icon = 'unknown';
			$Category = 'miscellaneous';
		}

		if ($Category == 'miscellaneous' && is_file('./share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/icons/'.$Icon.'.png'))
		{
			return 'share/themes/'.$_SESSION[EstatsCore::session()]['theme'].'/icons/'.$Icon.'.png';
		}

		return 'share/icons/'.$Category.'/'.$Icon.'.png';
	}

/**
 * Generates item name text
 * @param string String
 * @param string Category
 * @return string
 */

	static function itemText($String, $Category)
	{
		if (trim($String) == '?')
		{
			return (($Category == 'referrers')?EstatsLocale::translate('Direct entries'):EstatsLocale::translate('Unknown'));
		}
		else if (in_array($Category, array('java', 'javascript', 'cookies')))
		{
			return ($String?EstatsLocale::translate('Yes'):EstatsLocale::translate('No'));
		}
		else if ($Category == 'flash' && !$String)
		{
			return EstatsLocale::translate('No');
		}

		$Countries = EstatsCore::loadData('share/data/countries.ini');

		switch ($Category)
		{
			case 'time':
				$Array = array(
	'views' => EstatsLocale::translate('Views'),
	'returns' => EstatsLocale::translate('Returns'),
	'unique' => EstatsLocale::translate('Unique'),
	'current' => EstatsLocale::translate('Current period'),
	'previous' => EstatsLocale::translate('Previous period'),
	);
				if (isset($Array[$String]))
				{
					$String = $Array[$String];
				}
			break;
			case 'cities':
				$Country = (int) ($String[strlen($String) - 3] == '-');
				$City = ($Country?substr($String, 0, -3):$String);
				$String = (function_exists('utf8_encode')?utf8_encode($City):$City).($Country?', '.EstatsLocale::translate($Countries[substr($String, -2)]):'');
			break;
			case 'countries':
				$String = (isset($Countries[$String])?EstatsLocale::translate($Countries[$String]):EstatsLocale::translate('Unknown'));
			break;
			case 'continents':
				$Continents = EstatsCore::loadData('share/data/continents.ini');
				$String = ($String?EstatsLocale::translate($Continents[$String]):EstatsLocale::translate('Unknown'));
			break;
			case 'languages':
				$Languages = EstatsCore::loadData('share/data/languages.ini');
				$Language = explode('-', strtolower($String));

				if (isset($Languages[$Language[0]]))
				{
					$String = EstatsLocale::translate($Languages[$Language[0]]);

					if (isset($Language[1]) && isset($Countries[$Language[1]]))
					{
						$String.= ' ('.EstatsLocale::translate($Countries[$Language[1]]).')';
					}
				}
				else
				{
					$String = EstatsLocale::translate('Unknown');
				}
			break;
			case 'operatingsystems':
				if ($String == 'mobile')
				{
					$String = EstatsLocale::translate('Mobile devices');
				}
			break;
			case 'regions':
				$Regions = EstatsCore::loadData('share/data/regions.ini');
				$Region = explode('-', $String);
				$String = (isset($Regions[$Region[0]][$Region[1]])?$Regions[$Region[0]][$Region[1]]:EstatsLocale::translate('Unknown'));
			break;
			case 'operatingsystem-versions':
				if ($String == 'mobile')
				{
					$String = EstatsLocale::translate('Mobile devices');
				}
				else if (substr($String, 0, 6) == 'mobile')
				{
					$String = substr($String, 7);
				}
			break;
		}

		return $String;
	}

/**
 * Calculates percent difference between values
 * @param integer Current
 * @param integer Before
 * @return string
 */

	static function formatDifference($Current, $Before)
	{
		if ($Current == $Before)
		{
			return 0;
		}
		else if (!$Before)
		{
			return 100;
		}
		else if (!$Current)
		{
			return -100;
		}
		else if ($Current < $Before)
		{
			return -round(((($Before - $Current) / $Before) * 100), 2);
		}
		else
		{
			return round((($Current / $Before) * 100), 2);
		}
	}

/**
 * Formats number
 * @param float $Number
 * @param boolean Tag
 * @return string
 */

	static function formatNumber($Number, $Tag = TRUE)
	{
		$Value = (($Number < 1000)?round($Number, 2):(($Number < 1000000)?(round($Number / 1000, 1)).'K':(round($Number / 1000000, 1)).'M'));

		if ($Tag)
		{
			return '<em'.(($Number >= 1000 || is_float($Number))?' title="'.round($Number, 5).'"':'').'>'.$Value.'</em>';
		}

		return $Value;
	}

/**
 * Formats size
 * @param integer Size
 * @param boolean Title
 * @return string
 */

	static function formatSize($Size, $Title = TRUE)
	{
		return ($Title?'<span title="'.number_format($Size, 0, '', ' ').' B">':'').(($Size > 1024)?(($Size > 1048576)?(($Size > 1073741824)?round($Size / 1073741824, 2).' GB':round($Size / 1048576, 2).' MB'):round($Size / 1024, 2).' KB'):(int) $Size.' B').($Title?'</span>':'');
	}

/**
 * Cuts string to given length
 * @param string String
 * @param integer Length
 * @param boolean Title
 * @param boolean Dots
 * @return string
 */

	static function cutString($String, $Length, $Title = FALSE, $Dots = TRUE)
	{
		if (!$Length)
		{
			return htmlspecialchars($String);
		}

		if (function_exists('mb_substr'))
		{
			return (mb_strwidth($String, 'UTF-8') > ($Length + 3) || !$Dots)?($Title?'<span title="'.htmlspecialchars($String).'">'.htmlspecialchars(mb_substr($String, 0, $Length, 'UTF-8')).($Dots?'...':'').'</span>':htmlspecialchars(mb_substr($String, 0, $Length, 'UTF-8')).($Dots?'...':'')):htmlspecialchars($String);
		}
		else
		{
			return (strlen($String) > ($Length + 3) || !$Dots)?($Title?'<span title="'.htmlspecialchars($String).'">'.htmlspecialchars(substr_replace($String, ($Dots?'...':''), $Length)).'</span>':htmlspecialchars(substr_replace($String, ($Dots?'...':''), $Length))):htmlspecialchars($String);
		}
	}

/**
 * Returns IP ignore rule link
 * @param array IPs
 * @param strin IP
 * @param boolean Ignored
 * @return string
 */

	static function ignoreIPLink($IPs, $IP, $Ignored = TRUE)
	{
		for ($i = 0, $c = count($IPs); $i < $c; ++$i)
		{
			if ($IP == $IPs[$i] || (strstr($IPs[$i], '*') && substr($IP, 0, (strlen($IPs[$i]) - 1)) == substr($IPs[$i], 0, -1)))
			{
				return '<a href="{selfpath}{separator}'.($Ignored?'ignored':'blocked').'IP='.htmlspecialchars($IPs[$i]).'" class="green" title="'.(($IP == $IPs[$i])?EstatsLocale::translate('Unblock IP'):EstatsLocale::translate('Unblock IPs range')).(($IP == $IPs[$i])?'':' ('.htmlspecialchars($IPs[$i]).')').'" tabindex="'.self::tabindex().'"><strong>&#187;</strong></a>';
			}
		}

		return '<a href="{selfpath}{separator}'.($Ignored?'ignored':'blocked').'IP='.$IP.'" class="red" title="'.EstatsLocale::translate('Block this IP').'" onclick="if (!confirm (\''.EstatsLocale::translate('Do You really want to ban this IP address?').'\')) return false" tabindex="'.self::tabindex().'"><strong>&#187;</strong></a>';
	}

/**
 * Returns whois link
 * @param string Data
 * @param string String
 * @return string
 */

	static function whoisLink($Data, $String = '')
	{
		return '<a href="'.str_replace('{data}', htmlspecialchars($Data), EstatsCore::option('WhoisLink')).'" title="'.EstatsLocale::translate('Whois').'" tabindex="'.self::tabindex().'">'.($String?$String:EstatsLocale::translate('Whois')).'</a>';
	}

/**
 * Returns map link
 * @param float Latitude
 * @param float Longitude
 * @return string
 */

	static function mapLink($Latitude, $Longitude)
	{
		return str_replace(array('{latitude}', '{longitude}'), array(number_format($Latitude, 2, '.', ''), number_format($Longitude, 2, '.', '')), htmlspecialchars(EstatsCore::option('MapLink')));
	}

/**
 * Calculates time range
 * @param integer Year
 * @param integer Month
 * @param integer Day
 * @param integer Hour
 * @return array
 */

	static function timeRange($Year = 0, $Month = 0, $Day = 0, $Hour = 0)
	{
		if (!$Year)
		{
			return array(0, $_SERVER['REQUEST_TIME']);
		}

		if ($Month)
		{
			if ($Day)
			{
				if ($Hour)
				{
					$From = strtotime($Year.'-'.(($Month < 10)?'0':'').$Month.'-'.(($Day < 10)?'0':'').$Day.' '.(($Hour < 10)?'0':'').$Hour.':00');

					return array($From, ($From + 3600));
				}
				else
				{
					$From = strtotime($Year.'-'.(($Month < 10)?'0':'').$Month.'-'.(($Day < 10)?'0':'').$Day);

					return array($From, ($From + 86400));
				}
			}
			else
			{
				$From = strtotime($Year.'-'.(($Month < 10)?'0':'').$Month.'-01');

				return array($From, ($From + (date('t', $From) * 86400)));
			}
		}

		return array(strtotime($Year.'-01-01'), strtotime(($Year + 1).'-01-01'));
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

	static function timeUnit($Period, $Timestamp, $Step, $Format, $CurrentTime)
	{
		$Popularity = in_array($Period, array('hours', 'weekdays'));

		if ($Period == 'weekdays' && EstatsLocale::option('WeekStartDay'))
		{
			$WeekDayTransition = range(0, 6);
			$WeekDayTransition = array_merge(array_slice($WeekDayTransition, EstatsLocale::option('WeekStartDay')), array_slice($WeekDayTransition, 0, EstatsLocale::option('WeekStartDay')));
		}

		if ($Period == 'year')
		{
			$Step = (date('t', $Timestamp) * 86400);
		}
		else if ($Period == 'years')
		{
			$Step = ((date('L', $Timestamp) + 365) * 86400);
		}

		if (($CurrentTime || $Popularity) && $Step)
		{
			$Timestamp += $Step;
		}

		if ($Period == 'hours')
		{
			$UnitID = (($Timestamp < 10)?'0':'').$Timestamp;
		}
		else if ($Period == 'weekdays')
		{
			if (EstatsLocale::option('WeekStartDay'))
			{
				$UnitID = $WeekDayTransition[$Timestamp];
			}
			else
			{
				$UnitID = $Timestamp;
			}
		}
		else
		{
			$UnitID = date($Format, $Timestamp);
		}

		if (!$CurrentTime && !$Popularity && $Step)
		{
			$Timestamp += $Step;
		}

		return array($UnitID, $Timestamp);
	}

/**
 * Returns next free tabindex
 * @return integer
 */

	static function tabindex()
	{
		static $Tabindex;

		return ++$Tabindex;
	}

/**
 * Generates links block
 * @param integer Page
 * @param integer Amount
 * @param string Path
 * @return string
 */

	static function linksWidget($Page, $Amount, $Path)
	{
		if ($Amount < 2)
		{
			return '';
		}

		$Locale = array(
	'first' => EstatsLocale::translate('Go to first page (%d.)'),
	'previous' => EstatsLocale::translate('Go to previous page (%d.)'),
	'next' => EstatsLocale::translate('Go to next page (%d.)'),
	'last' => EstatsLocale::translate('Go to last page (%d.)'),
	'default' => EstatsLocale::translate('Go to page %d.')
	);
		$Array = array(
	'first' => '&laquo;',
	'previous' => '&lsaquo;',
	'next' => '&rsaquo;',
	'last' => '&raquo;'
	);
		$TmpArray = array_merge(array('first' => 1, 'previous' => ($Page - 1)), range(($Page - (($Page == 5)?4:3)), ($Page + (($Page == ($Amount - 4))?4:3))), array('next' => ($Page + 1), 'last' => $Amount));
		$Links = array();

		foreach ($TmpArray as $Key => $Value)
		{
			if (is_numeric($Key))
			{
				$Key = $Value;
			}

			if (!is_numeric($Key) || ($Key > 0 && $Key <= $Amount))
			{
				if ($Value > 0 && $Value <= $Amount && $Value != $Page)
				{
					$Links[] = '<a href="'.str_replace('{page}', $Value, $Path).'" tabindex="'.self::tabindex().'" title="'.sprintf($Locale[is_numeric($Key)?'default':$Key], $Value).'">'.(is_numeric($Key)?$Key:$Array[$Key]).'</a>';
				}
				else
				{
					$Links[] = '<strong>'.(is_numeric($Key)?$Key:$Array[$Key]).'</strong>';
				}

				if (($Page > 4 && $Key == 'previous' && $Page != 5) || ($Page < ($Amount - 3) && $Key == ($Page + 3) && $Page != ($Amount - 4)))
				{
					$Links[] = '...';
				}
			}
		}

		return str_replace('{links}', implode('
|
', $Links), EstatsTheme::get('links'));
	}

/**
 * Generates announcement
 * @param string Content
 * @param string Type
 * @return string
 */

	static function notificationWidget($Content, $Type)
	{
		$Locale = array(
			'warning' => EstatsLocale::translate('Warning'),
			'success' => EstatsLocale::translate('Success'),
			'error' => EstatsLocale::translate('Error'),
			'information' => EstatsLocale::translate('Information'),
			'loading' => EstatsLocale::translate('Loading...'),
			);

		$Type = str_replace('.', '', $Type);

		return EstatsTheme::parse(EstatsTheme::get('announcement'), array(
	'class' => $Type,
	'type' => $Locale[$Type],
	'content' => $Content
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

	static function optionRowWidget($Label, $Description, $Name, $Value = '', $Type = self::FIELD_VALUE, $Options = NULL, $Default = NULL)
	{
 		$ID = str_replace(array('[]', '|'), '', $Name);

		if (!is_array($Value) && $Type != self::FIELD_CUSTOM)
		{
			$Value = str_replace(array('{', '}'), array('&#123;', '&#125;'), $Value);
		}

		switch ($Type)
		{
			case self::FIELD_CUSTOM:
				$Form = &$Value;
			break;
			case self::FIELD_BOOLEAN:
				$Form = '<input type="checkbox" name="'.$Name.'" id="F_'.$ID.'" value="1" tabindex="'.self::tabindex().'"'.($Value?' checked="checked"':'').(($Default !== NULL)?' onchange="checkDefault(\''.$ID.'\', '.($Default?1:0).')"':'').' />';
			break;
			case self::FIELD_VALUE:
				$Form = '<input'.(stristr($Name, 'pass')?' type="password"':'').' name="'.$Name.'" id="F_'.$ID.'" value="'.htmlspecialchars($Value, ENT_QUOTES, 'UTF-8', FALSE).'" tabindex="'.self::tabindex().'"'.(($Default !== NULL)?' onkeyup="checkDefault(\''.$ID.'\', \''.str_replace(array("\r\n", "\n", '{', '}'), array('\r\n', '\n', '&#123;', '&#125;'), htmlspecialchars($Default, ENT_QUOTES, 'UTF-8')).'\')"':'').' />';
			break;
			case self::FIELD_SELECT:
				$Form = '<select name="'.$Name.'" id="F_'.$ID.'" tabindex="'.self::tabindex().'"'.(is_array($Value)?' multiple="multiple" size="3"':'').(($Default !== NULL)?' onchange="checkDefault(\''.$ID.'\', \''.str_replace(array('{', '}'), array('&#123;', '&#125;'), htmlspecialchars($Default, ENT_QUOTES, 'UTF-8')).'\')"':'').'>
';

				for ($i = 0, $c = count($Options); $i < $c; ++$i)
				{
					if (empty($Options[$i]))
					{
						continue;
					}

					if (is_array($Options[$i]))
					{
						$Text = $Options[$i][1];
						$Options[$i] = $Options[$i][0];
					}
					else
					{
						$Text = $Options[$i];
					}

					$Select = (is_array($Value)?in_array($Options[$i], $Value):($Options[$i] == $Value));
					$Form.= '<option'.(($Text != $Options[$i])?' value="'.htmlspecialchars($Options[$i]).'"':'').($Select?' selected="selected"':'').'>'.htmlspecialchars($Text).'</option>
';
				}

				$Form.= '</select>';
			break;
			case self::FIELD_MULTILINE:
			case self::FIELD_ARRAY:
				$Form = '<textarea rows="1" cols="25" name="'.$Name.'" id="F_'.$ID.'" tabindex="'.self::tabindex().'"'.(($Type == self::FIELD_ARRAY)?' title="'.EstatsLocale::translate('Array, elements separated by |').'"':'').(($Default !== NULL)?' onkeyup="checkDefault(\''.$ID.'\', \''.str_replace(array("\r\n", "\n", '{', '}'), array('\r\n', '\n', '&#123;', '&#125;'), htmlspecialchars($Default, ENT_QUOTES, 'UTF-8')).'\')"':'').'>'.htmlspecialchars((is_array($Value)?implode('|', $Value):$Value), ENT_QUOTES, 'UTF-8', FALSE).'</textarea>';
			break;
			default:
				return '';
			break;
		}

		if ($Default !== NULL)
		{
			$Form.= '
<input type="button" value="'.EstatsLocale::translate('Default').'"  onclick="setDefault(\''.$ID.'\', \''.str_replace(array("\r\n", "\n", '{', '}'), array('\r\n', '\n', '&#123;', '&#125;'), htmlspecialchars($Default, ENT_QUOTES, 'UTF-8')).'\')" title="'.EstatsLocale::translate('Default value').': '.htmlspecialchars(str_replace(array("\r\n", "\n", '{', '}'), array(' ', ' ', '&#123;', '&#125;'), $Default)).'" tabindex="'.self::tabindex().'" />';
		}

		return EstatsTheme::parse(EstatsTheme::get('option-row'), array(
	'changed' => (($Default === NULL || str_replace(array('{', '}', '\r\n'), array('&#123;', '&#125;', "\r\n"),$Default) == (is_array($Value)?implode('|', $Value):$Value))?'':' class="changed" title="'.EstatsLocale::translate('Field value is other than default').'"'),
	'id' => &$ID,
	'form' => &$Form,
	'option' => &$Label,
	'description' => ($Description?'<br />
<dfn>'.$Description.'</dfn>':'')
	));
	}

/**
 * Checks tool availability level
 * @param string Tool
 * @return integer
 */

	static function toolLevel($Tool)
	{
		if (is_file('./plugins/tools/'.$Tool.'/plugin.ini'))
		{
			$Information = EstatsCore::loadData('plugins/tools/'.$Tool.'/plugin.ini', FALSE, FALSE);

			if (isset($Information['Level']))
			{
				return $Information['Level'];
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

	static function saveConfiguration($Options, $Values, $RestoreDefaults = FALSE)
	{
		if ($RestoreDefaults)
		{
			$Defaults = EstatsCore::loadData('share/data/configuration.ini');
			$Defaults = array_merge($Defaults['Core'], $Defaults['GUI']);

			if (count($Defaults) == 0)
			{
				return;
			}
		}

		$Configuration = array();

		for ($i = 0, $c = count($Options); $i < $c; ++$i)
		{
			if ($RestoreDefaults)
			{
				$Configuration[$Options[$i]] = str_replace('\r\n', "\r\n", $Defaults[$Options[$i]]['value']);
			}
			else
			{
				$Configuration[$Options[$i]] = (isset($Values[$Options[$i]])?stripslashes($Values[$Options[$i]]):0);
			}
		}

		EstatsCore::setConfiguration($Configuration);
		EstatsCore::logEvent(EstatsCore::EVENT_CONFIGURATIONCHANGED);
		EstatsGUI::notify(EstatsLocale::translate('Configuration saved successfully.'), 'success');
	}

/**
 * Creates notification
 * @param string Message
 * @param string Type
 */

	static function notify($Message, $Type)
	{
		self::$Notifications[] = array($Message, $Type);
	}

/**
 * Returns notifications data
 * @return array
 */
	static function notifications()
	{
		return self::$Notifications;
	}
}
?>