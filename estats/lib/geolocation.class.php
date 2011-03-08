<?php
/**
 * Geolocation class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 2.0.06
 */

class EstatsGeolocation
{

/**
 * PDO object
 */

	static private $PDO = NULL;

/**
 * Checks if geolocation information is available
 * @return boolean
 */

	static function isAvailable()
	{
		return ((function_exists('geoip_record_by_name') && geoip_db_avail(GEOIP_CITY_EDITION_REV0)) || (is_readable(EstatsCore::path(TRUE).'geoip_'.EstatsCore::security().'.sqlite') && class_exists('PDO')));
	}

/**
 * Returns geolocation information for given IP
 * @param string IP
 * @return array
 */

	static function information($IP)
	{
		if ($IP == '127.0.0.1' || $IP == 'unknown' || !self::isAvailable())
		{
			return array();
		}

		if (function_exists('geoip_record_by_name') && geoip_db_avail(GEOIP_CITY_EDITION_REV0))
		{
			$Data = geoip_record_by_name($IP);
		}
		else
		{
			if (!self::$PDO)
			{
				try
				{
					self::$PDO = new PDO('sqlite:'.realpath(EstatsCore::path(TRUE).'geoip_'.EstatsCore::security().'.sqlite'), '', '', array(PDO::ATTR_PERSISTENT => TRUE));
				}
				catch (Exception $e)
				{
					return array();
				}
			}

			$IP = explode('.', $IP);
			$Statement = self::$PDO->prepare('SELECT "l"."city" AS "city", "l"."region" AS "region", "l"."country_code" AS "country_code", "l"."latitude" AS "latitude", "l"."longitude" AS "longitude" FROM "blocks" "b", "locations" "l" WHERE ? BETWEEN "b"."ipstart" AND "b"."ipend" AND "b"."location" = "l"."location"');

			if (!$Statement)
			{
				return array();
			}

			$Result = $Statement->execute(((16777216 * $IP[0]) + (65536 * $IP[1]) + (256 * $IP[2]) + $IP[3]));
			$Data = (isset($Result[0])?$Result[0]:array());
		}

		if (!$Data || empty($Data['continent_code']) || $Data['continent_code'] == '--')
		{
			return array();
		}

		$Continents = array(
	'EU' => 4,
	'NA' => 5,
	'SA' => 6,
	'AS' => 2,
	'AF' => 1,
	'AU' => 3,
	'OC' => 3,
	'AN' => 7
);

		$RegionCorrections = EstatsCore::loadData('share/data/region-corrections.ini');
		$Data['country_code'] = strtolower($Data['country_code']);
		$Data['region'] = (int) $Data['region'];

		if (isset($RegionCorrections[$Data['country_code']][$Data['region']]))
		{
			$Data['region'] = $RegionCorrections[$Data['country_code']][$Data['region']];
		}

		return array(
	'city' => $Data['city'],
	'region' => $Data['region'],
	'country' => $Data['country_code'],
	'continent' => $Continents[$Data['continent_code']],
	'latitude' => round($Data['latitude'], 3),
	'longitude' => round($Data['longitude'], 3)
	);
	}

/**
 * Generates coordinates string
 * @param float Latitude
 * @param float Longitude
 * @return string
 */

	static function coordinates($Latitude, $Longitude)
	{
		$LatitudeSuffix = (($Latitude < 0)?'S':'N');
		$LongitudeSuffix = (($Longitude < 0)?'W':'E');

		return round(abs($Latitude), 2).'&#176; '.$LatitudeSuffix.' '.round(abs($Longitude), 2).'&#176; '.$LongitudeSuffix;
	}
}
?>