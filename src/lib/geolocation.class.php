<?php
/**
 * Geolocation class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 2.0.07
 */

class EstatsGeolocation
{

/**
 * Geolocation availability indicator
 */

	static private $available = NULL;

/**
 * PDO object
 */

	static private $pDO = NULL;

/**
 * Checks if geolocation information is available
 * @return boolean
 */

	static function isAvailable()
	{
		if (self::$available === NULL)
		{
			self::$available = ((function_exists('geoip_record_by_name') && geoip_db_avail(GEOIP_CITY_EDITION_REV0)) || (is_readable(EstatsCore::path(TRUE).'geoip_'.EstatsCore::security().'.sqlite') && class_exists('PDO')));
		}

		return self::$available;
	}

/**
 * Returns geolocation information for given IP
 * @param string IP
 * @return array
 */

	static function information($iP)
	{
		if ($iP == '127.0.0.1' || $iP == 'unknown' || !self::isAvailable())
		{
			return array();
		}

		if (function_exists('geoip_record_by_name') && geoip_db_avail(GEOIP_CITY_EDITION_REV0))
		{
			$data = geoip_record_by_name($iP);
		}
		else
		{
			if (!self::$pDO)
			{
				try
				{
					self::$pDO = new PDO('sqlite:'.realpath(EstatsCore::path(TRUE).'geoip_'.EstatsCore::security().'.sqlite'), '', '', array(PDO::ATTR_PERSISTENT => TRUE));
				}
				catch (Exception $e)
				{
					return array();
				}
			}

			$statement = self::$pDO->prepare('SELECT * FROM "locations" WHERE "location" = (SELECT "location" FROM "blocks" WHERE ? BETWEEN "ipstart" AND "ipend")');

			if (!$statement)
			{
				return array();
			}

			$iP = explode('.', $iP);
			$result = $statement->execute(array((16777216 * $iP[0]) + (65536 * $iP[1]) + (256 * $iP[2]) + $iP[3]));
			$data = (isset($result[0])?$result[0]:array());
		}

		if (!$data || $data['continent_code'] == '--')
		{
			return array();
		}

		$continents = array(
	'EU' => 4,
	'NA' => 5,
	'SA' => 6,
	'AS' => 2,
	'AF' => 1,
	'AU' => 3,
	'OC' => 3,
	'AN' => 7
);

		$regionCorrections = EstatsCore::loadData('share/data/region-corrections.ini');
		$data['country_code'] = strtolower($data['country_code']);
		$data['region'] = (int) $data['region'];

		if (isset($regionCorrections[$data['country_code']][$data['region']]))
		{
			$data['region'] = $regionCorrections[$data['country_code']][$data['region']];
		}

		return array(
	'city' => $data['city'],
	'region' => $data['region'],
	'country' => $data['country_code'],
	'continent' => $continents[$data['continent_code']],
	'latitude' => round($data['latitude'], 3),
	'longitude' => round($data['longitude'], 3)
	);
	}

/**
 * Generates coordinates string
 * @param float Latitude
 * @param float Longitude
 * @return string
 */

	static function coordinates($latitude, $longitude)
	{
		$latitudeSuffix = (($latitude < 0)?'S':'N');
		$longitudeSuffix = (($longitude < 0)?'W':'E');

		return round(abs($latitude), 2).'&#176; '.$latitudeSuffix.' '.round(abs($longitude), 2).'&#176; '.$longitudeSuffix;
	}
}
?>