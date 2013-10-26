<?php
/**
 * UserAgent class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 0.9.01
 */

class EstatsUserAgent
{

/**
 * Property: user agent belongs to known network robot
 */

	const USERAGENT_ROBOT = 1;

/**
 * Property: user agent belongs to known mobile device
 */

	const USERAGENT_MOBILE = 2;

/**
 * Property: user agent belongs to known mail client
 */

	const USERAGENT_MAILCLIENT = 4;

/**
 * Property: user agent belongs to known feeds reader
 */

	const USERAGENT_FEEDSREADER = 8;

/**
 * Property: user agent belongs to known text based web browser
 */

	const USERAGENT_TEXTBASED = 16;

/**
 * Detection rules for network robots
 */

	static private $robots = NULL;

/**
 * Detection rules for browsers
 */

	static private $browsers = NULL;

/**
 * Detection rules for operating systems
 */

	static private $operatingSystems = NULL;

/**
 * Detection rules for devices
 */

	static private $devices = NULL;

/**
 * Supplied user agent string
 */

	private $userAgent = NULL;

/**
 * Resulting user agent context string
 */

	private $context = NULL;

/**
 * Supplied IP address
 */

	private $iP = NULL;

/**
 * Bit filed mask containing various user agent properties 
 */

	private $properties = 0;

/**
 * Detected browser family
 */

	private $browserFamily = NULL;

/**
 * Detected browser version
 */

	private $browserVersion = NULL;

/**
 * Detected operating system family
 */

	private $operatingSystemFamily = NULL;

/**
 * Detected operatingSystem version
 */

	private $operatingSystemVersion = NULL;

/**
 * Detected device family
 */

	private $deviceFamily = NULL;

/**
 * Detected device version
 */

	private $deviceVersion = NULL;

/**
 * Handles detection using regular expressions etc.
 * @param array data
 * @return array
 */

	static private function detect($data)
	{
		$result = array('family' => '', 'version' => '', 'context' => array());

		foreach ($data as $key => $value)
		{
			if (isset($value['ips']) && EstatsCore::containsIP($this->IP, $value['ips']))
			{
				$result['family'] = $key;
				$result['context'][] = 'IP:'.$value['ips'];
			
				break;
			}

			if (isset($value['rules']))
			{
				$version = 0;

				if (strstr($key, '.'))
				{
					$version = explode('.', $key);
					$key = $version[0];
				}

				for ($i = 0, $c = count($value['rules']); $i < $c; ++$i)
				{
					if (($version && preg_match('#'.$value['rules'][$i].'#i', $this->userAgent)) || preg_match('#'.$value['rules'][$i].'#i', $this->userAgent, $version))
					{
						$result['family'] = $key;

						if (isset($version[1]))
						{
							$result['version'] = $version[1];
						}

						break 2;
					}
				}
			}
			else if (stristr($this->userAgent, $key))
			{
				$result['family'] = $key;

				break;
			}
		}

		if (empty($result))
		{
			return NULL;
		}

		if (isset($value['context']))
		{
			$result['context'][] = $value['metadata'];
		}

		return $result;
	}

/**
 * Constructor
 * @param string userAgent
 * @param string IP
 */

	__construct($userAgent, $iP)
	{
		$this->userAgent = &$userAgent;
		$this->IP = &$iP;

		if (!empty($userAgent))
		{
			$context = array();

			if (empty(self::$robots))
			{
				self::$robots = EstatsCore::loadData('share/data/robots.ini');
			}

			$robot = self::detect(self::$robots);

			if (empty($robot))
			{
				if (empty(self::$browsers))
				{
					self::$browsers = EstatsCore::loadData('share/data/browsers.ini');
				}

				$browser = self::detect(self::$browsers);

				$this->browserFamily = &$bbrowser['family'];
				$this->browserVersion = &$bbrowser['version'];

				$context = array_merge($context, $browser['context']);

				if (empty(self::$operatingSystems))
				{
					self::$operatingSystems = EstatsCore::loadData('share/data/operating-systems.ini');
				}

				$operatingSystem = self::detect(self::$operatingSystem);

				$this->operatingSystemFamily = &$operatingSystem['family'];
				$this->operatingSystemVersion = &$operatingSystem['version'];

				$context = array_merge($context, $operatingSystem['context']);

				if (empty(self::$devices))
				{
					self::$devices = EstatsCore::loadData('share/data/devices.ini');
				}

				$device = self::detect(self::$devices);

				$this->deviceFamily = &$device['family'];
				$this->deviceVersion = &$device['version'];

				$context = array_merge($context, $device['context']);
			}
			else
			{
				$this->browserFamily = &$robot['family'];
				$this->properties |= self::USERAGENT_ROBOT;

				$context = array_merge($context, $robot['context']);
			}

			$this->context = json_encode($context);
		}
	}

/**
 * Returns user agent string
 * @return string
 */

	static function getUserAgent()
	{
		return $this->userAgent;
	}

/**
 * Returns user agent context string
 * @return string
 */

	static function getContext()
	{
		return $this->context;
	}

/**
 * Returns browser family name
 * @return string
 */

	static function getBrowserFamily()
	{
		return $this->browserFamily;
	}

/**
 * Returns browser version
 * @return string
 */

	static function getBrowserVersion()
	{
		return $this->browserVersion;
	}

/**
 * Returns operating system family name
 * @return string
 */

	static function getOperatingSystemFamily()
	{
		return $this->operatingSystemFamily;
	}

/**
 * Returns operating system version
 * @return string
 */

	static function getOperatingSystemVersion()
	{
		return $this->operatingSystemVersion;
	}

/**
 * Returns device family name
 * @return string
 */

	static function getDeviceFamily()
	{
		return $this->deviceFamily;
	}

/**
 * Returns device version
 * @return string
 */

	static function getDeviceVersion()
	{
		return $this->deviceVersion;
	}

/**
 * Returns user agent properties mask
 * @return integer
 */

	static function getProperties()
	{
		return $this->properties;
	}
}
?>