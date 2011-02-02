<?php
/**
 * Graphics class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 2.0.01
 */

class EstatsGraphics
{

/**
 * Checks if graphics plugin is available
 * @return boolean
 */

	static function isAvailable()
	{
		return (function_exists('gd_info') && EstatsCore::option('GraphicsEnabled'));
	}

/**
 * Generates colour array from string
 * @param string String
 * @return array
 */

	static function colour($String)
	{
		$Colour = array();

		for ($i = 0; $i < 3; ++$i)
		{
			$Colour[$i] = hexdec('0x'.substr($String, (2 * $i), 2));
		}

		return $Colour;
	}

/**
 * Writes text on image
 * @param resource Image
 * @param integer X
 * @param integer Y
 * @param integer Size
 * @param string String
 * @param array Colour
 */

	static function drawText($Image, $X, $Y, $Size, $String, $Colour = 0)
	{
		if (!$Colour)
		{
			$Colour = self::colour($Colour);
		}

		imagettftext($Image, $Size, 0, $X, $Y, imagecolorallocate($Image, $Colour[0], $Colour[1], $Colour[2]), './share/fonts/LiberationMono-Regular.ttf', $String);
	}

/**
 * Checks if cache is valid and uses it
 * @param string ID
 * @param integer Time
 */

	function cacheImage($ID, $Time)
	{
		if (!EstatsCache::status($ID, $Time, '.png'))
		{
			self::outputImage($ID);
		}
	}

/**
 * Saves image data
 * @param string ID
 * @param resource Image
 */

	static function saveImage($ID, $Image)
	{
		imagetruecolortopalette($Image, 0, 256);

		$FileName = EstatsCache::path($ID, '.png');

		if (is_file($FileName))
		{
			unlink($FileName);
		}

		touch($FileName);
		chmod($FileName, 0666);

		if (!imagepng($Image, $FileName))
		{
			header('Content-type: image/png');
			imagepng($Image);
			imagedestroy($Image);
			die();
		}

		imagedestroy($Image);

		self::outputImage($ID);
	}

/**
 * Sends image to browser
 * @param string ID
 */

	static function outputImage($ID)
	{
		header('Content-type: image/png');
		die(file_get_contents(EstatsCache::path($ID, '.png')));
	}

/**
 * Generates pie chart
 * @param string ID
 * @param array Data
 * @param string ID
 */

	static function chartPie($ID, $Data, $ID)
	{
		arsort($Data['data']);

		$Others = $Amount = $j = 0;
		$Percents = $Names = $Slices = $Coordinates = array();

		for ($i = 0, $c = count($Data['data']); $i < $c; ++$i)
		{
			$Percent = (($Data['data'][$i]['amount_current'] / $Data['sum_current']) * 100);

			if (++$j <= 20 && ($Percent >= 5 || (!$Others && $j == $c)))
			{
				$Percents[] = $Percent;
				$Names[] = $Data['data'][$i]['name'];
				$Amount += $Data['data'][$i]['amount_current'];
			}
			else
			{
				++$Others;
			}
		}

		if (($Data['sum_current'] - $Amount) > 0)
		{
			$Percents[] = ((($Data['sum_current'] - $Amount) / $Data['sum_current']) * 100);
		}

		unset($Data);

		$Amount = count($Percents);
		$Colours = explode('|', EstatsTheme::option('ChartPieColours'));

		for ($i = 0; $i < 2; ++$i)
		{
			$Colours[$i] = self::colour($Colours[$i]);
		}

		$Image = imagecreatetruecolor(400, 400);

		imagefill($Image, 0, 0, imagecolorallocate($Image, 255, 255, 255));
		imageantialias($Image, TRUE);

		$Start = 150;
		$End = 0;
		$X = 390;
		$ColoursStep = array();

		for ($i = 0; $i < 3; ++$i)
		{
			$ColoursStep[$i] = (($Colours[1][$i] - $Colours[0][$i]) / $Amount);
		}

		for ($i = 0, $c = count($Percents); $i < $c; ++$i)
		{
			$Slices[] = array(
	$Start,
	($Start = ceil(($End += $Percents[$i]) * 3.6) + 150),
	imagecolorallocate($Image, $Colours[0][0], $Colours[0][1], $Colours[0][2]),
	imagecolorallocate($Image, ($Colours[0][0] - 30), ($Colours[0][1] - 30), ($Colours[0][2] - 30))
	);
			$Rad = deg2rad($Slices[$i][0] + (($Slices[$i][1] - $Slices[$i][0]) / 2));
			$Coordinates[] = array(((int) (100 * cos($Rad)) + 150), ((int) (75 * sin($Rad)) + 105));
			$X += 42;

			for ($j = 0; $j < 3; ++$j)
			{
				$Colours[0][$j] += $ColoursStep[$j];
			}
		}

		for ($i = 170; $i > 150; --$i)
		{
			for ($j = 0, $l = count($Slices); $j < $l; ++$j)
			{
				if ($Percents[$j] < 1)
				{
					continue;
				}

				imagefilledarc($Image, 200, $i, 397, 297, $Slices[$j][0], $Slices[$j][1], $Slices[$j][3], IMG_ARC_PIE);
			}
		}

		for ($i = 0, $c = count($Slices); $i < $c; ++$i)
		{
			if ($Percents[$i] < 1)
			{
				continue;
			}

			imagefilledarc($Image, 200, 150, 397, 297, $Slices[$i][0], $Slices[$i][1], $Slices[$i][2], IMG_ARC_PIE);
		}

		$FinalImage = imagecreatetruecolor(200, 200);

		imagefill($FinalImage, 0, 0, imagecolorallocate($Image, 255, 255, 255));
		imagecopyresampled($FinalImage, $Image, 0, 0, 0, 0, 200, 200, 400, 400);
		imagedestroy($Image);

		if (function_exists('imagefilter'))
		{
			imagefilter($FinalImage, IMG_FILTER_SMOOTH, 5);
		}

		for ($i = 0, $c = count($Percents); $i < $c; ++$i)
		{
			$Coordinates[$i][0] -= ($Coordinates[$i][0] / 3);
			$Coordinates[$i][1] -= ($Coordinates[$i][1] / 3);

			if (isset($Names[$i]))
			{
				$Icon = EstatsGUI::iconPath($Names[$i], $ID);

				if ($Icon && is_file('./'.$Icon))
				{
					$TmpImage = imagecreatefrompng('./'.$Icon);

					if ($Percents[$i] < 100 && $Percents[$i] >= 5)
					{
						imagecopyresampled($FinalImage, $TmpImage, ($Coordinates[$i][0] - 10), ($Coordinates[$i][1] + 2), 0, 0, 16, 16, 16, 16);
					}

					imagedestroy($TmpImage);
				}
			}

			if ($Percents[$i] != 100)
			{
				$String = round($Percents[$i], 2).'%';

				if ($Percents[$i] >= 1)
				{
					self::drawText($FinalImage, ($Coordinates[$i][0] - 20), $Coordinates[$i][1], 7, ((strlen($String) < 5)?str_repeat(' ', ((7 - strlen($String)) / 2)):'').$String);
				}
			}
		}

		imagecolortransparent($FinalImage, imagecolorallocate($FinalImage, 255, 255, 255));

		self::saveImage($ID, $FinalImage);
	}

/**
 * Generates time chart
 * @param string ID
 * @param array Data
 * @param array Summary
 * @param string ID
 * @param string Type
 * @param boolean Join
 */

	static function chartTime($ID, $Data, $Summary, $ID, $Type, $Join)
	{
		if (!$Summary['maxall'])
		{
			$Image = imagecreatetruecolor(7500, 170);

			imagefill($Image, 0, 0, imagecolorallocate($Image, 255, 255, 255));
			imagecolortransparent($Image, imagecolorallocate($Image, 255, 255, 255));

			self::saveImage($ID, $Image);
		}

		$Image = imagecreatetruecolor(1500, 340);

		imagefill($Image, 0, 0, imagecolorallocate($Image, 255, 255, 255));
		imagecolortransparent($Image, imagecolorallocate($Image, 255, 255, 255));
		imageantialias($Image, TRUE);

		$Colours = explode('|', EstatsTheme::option('ChartTimeColours'));

		for ($i = 0, $c = count($Colours); $i < $c; ++$i)
		{
			$Colour = self::colour($Colours[$i]);
			$Colours[$i] = imagecolorallocatealpha($Image, $Colour[0], $Colour[1], $Colour[2], 30);
			$ColoursDark[$i] = imagecolorallocate($Image, $Colour[0], $Colour[1], $Colour[2]);
		}

		$TypesAmount = count($Summary['types']);
		$Width = (2 * round(700 /($Summary['amount'])));
		$ChartData = array();
		$TimeUnit = array(0, (in_array($Summary['chart'], array('hours', 'weekdays'))?($Summary['currenttime']?-1:0):$Summary['timestamp']));

		for ($i = 0; $i < $Summary['amount']; ++$i)
		{
			$TimeUnit = EstatsGUI::timeUnit($Summary['chart'], $TimeUnit[1], $Summary['step'], $Summary['format'], $Summary['currenttime']);
			$UnitID = &$TimeUnit[0];

			for ($j = 0; $j < $TypesAmount; ++$j)
			{
				$ChartData[$Summary['types'][$j]][$i] = (isset($Data[$UnitID][$Summary['types'][$j]])?$Data[$UnitID][$Summary['types'][$j]]:0);
			}
		}

		unset($Data);

		if ($Type == 'bars')
		{
			$BarWidth = (($Width / $TypesAmount) * 0.8);
			$BarMargin = (($Width / $TypesAmount) * 0.3);
		}
		else
		{
			for ($i = 0; $i < $TypesAmount; ++$i)
			{
				$ChartData[$Summary['types'][$i]][-1] = $ChartData[$Summary['types'][$i]][$Join?($Summary['amount'] - 1):0];
				$ChartData[$Summary['types'][$i]][$Summary['amount']] = $ChartData[$Summary['types'][$i]][$Join?0:($Summary['amount'] - 1)];
			}
		}

		for ($i = 0; $i < $TypesAmount; ++$i)
		{
			$X = (($Type == 'bars')?0:-($Width / 2));

			for ($j = (($Type == 'bars')?0:-1); $j < $Summary['amount']; ++$j)
			{
				$Y = (336 - (($ChartData[$Summary['types'][$i]][$j] / $Summary['maxall']) * 300));

				if (!$ChartData[$Summary['types'][$i]][$j] && !$ChartData[$Summary['types'][$i]][$j + 1])
				{
					$X += $Width;

					continue;
				}

				switch ($Type)
				{
					case 'bars':
						imagefilledrectangle($Image, ($X + $BarMargin +($BarWidth * $i) - 2), ($Y - 2), ($X +($BarWidth *($i + 1)) + 2), 340, $ColoursDark[$i]);
						imagefilledrectangle($Image, ($X + $BarMargin +($BarWidth * $i)), $Y, ($X +($BarWidth *($i + 1))), 340, $Colours[$i]);
					break;
					case 'lines':
						imageline($Image, $X, $Y, ($X + $Width), (336 -(($ChartData[$Summary['types'][$i]][$j + 1] / $Summary['maxall']) * 300)), $Colours[$i]);
						imageline($Image, $X, ($Y + 1), ($X + $Width), (337 -(($ChartData[$Summary['types'][$i]][$j + 1] / $Summary['maxall']) * 300)), $Colours[$i]);
					break;
					case 'areas':
						$Points = array(
	$X, 340,
	$X, $Y,
	($X + $Width), (336 -(($ChartData[$Summary['types'][$i]][$j + 1] / $Summary['maxall']) * 300)),
	($X + $Width), 340
	);
						imagefilledpolygon($Image, $Points, 4, $Colours[$i]);
					break;
				}

				if ($Type != 'bars' && $ChartData[$Summary['types'][$i]][$j])
				{
					imagefilledellipse($Image, $X, $Y, 8, 8, $ColoursDark[$i]);
				}

				$X += $Width;
			}
		}

		$FinalImage = imagecreatetruecolor(700, 170);

		imagecopyresampled($FinalImage, $Image, 0, 0, 0, 0, 700, 170, 1400, 340);
		imagecolortransparent($FinalImage, imagecolorallocate($FinalImage, 255, 255, 255));

		if (function_exists('imagefilter'))
		{
			imagefilter($FinalImage, IMG_FILTER_SMOOTH, 25);
		}

		imagedestroy($Image);

		self::saveImage($ID, $FinalImage);
	}

/**
 * Generates map
 * @param string MapType
 * @param array Data
 * @param string ID
 */

	static function map($MapType, $Data, $ID)
	{
		$Continents = isset($Data['continents']);
		$Map = EstatsCore::loadData('share/maps/'.$MapType[0].'/map.ini', TRUE);
		$Colours = explode('|', EstatsTheme::option('MapColours'));
		$Start = self::colour($Colours[0]);
		$End = self::colour($Colours[1]);
		$ColoursStep = array();

		for ($i = 0; $i < 3; ++$i)
		{
			$ColoursStep[$i] = (($Start[$i] - $End[$i]) / 100);
		}

		$Image = imagecreatefrompng('./share/maps/'.$MapType[0].'/map.png');

		if ($Data['max'])
		{
			$Border = imagecolorat($Image, 0, 0);

			if ($Continents)
			{
				$CountryToContinent = EstatsCore::loadData('share/data/country-to-continent.ini');
			}

			foreach ($Map['Divisions'] as $Key => $Value)
			{
				$Key = trim($Key, '\\');

				if ($MapType[0] != 'world')
				{
					$Key = $MapType[0].'-'.$Key;
				}

				if ($Continents)
				{
					$Key = $CountryToContinent[$Key];
				}

				if (isset($Data[$Continents?'continents':'data'][$Key]) && $Data[$Continents?'continents':'data'][$Key])
				{
					$Percent = (($Data[$Continents?'continents':'data'][$Key] / $Data['max']) * 100);
					$Colour = imagecolorallocate($Image, (floor($ColoursStep[0] * $Percent) + $End[0]), (floor($ColoursStep[1] * $Percent) + $End[1]), (floor($ColoursStep[2] * $Percent) + $End[2]));
					$Value = explode(',', $Value);

					for ($i = 0, $c = count($Value); $i < $c; $i += 2)
					{
						imagefilltoborder($Image, $Value[$i], $Value[$i + 1], $Border, $Colour);
					}
				}

				if (isset($Map['Flags'][$Key]))
				{
					$ImageFile = EstatsGUI::iconPath($Key, 'countries');

					if (is_file('./'.$ImageFile))
					{
						$Coordinates = explode(',', $Map['Flags'][$Key]);
						$TmpImage = imagecreatefrompng('./'.$ImageFile);

						imagecopyresampled($Image, $TmpImage, $Coordinates[0], $Coordinates[1], 0, 0, 16, 16, 16, 16);
						imagedestroy($TmpImage);
					}
				}
			}

			if (isset($Map['Cities']))
			{
				$Background = imagecolorallocate($Image, 250, 250, 250);

				foreach ($Data['cities']['data'] as $Key => $Value)
				{
					$Key = number_format(round($Value['latitude'], 2), 2, '.', '').','.number_format(round($Value['longitude'], 2), 2, '.', '');

					if (!isset($Map['Cities'][$Key]))
					{
						continue;
					}

					$Percent = (($Value['amount_current'] / $Data['max']) * 100);
					$Colour = imagecolorallocate($Image, (floor($ColoursStep[0] * $Percent) + $End[0]), (floor($ColoursStep[1] * $Percent) + $End[1]), (floor($ColoursStep[2] * $Percent) + $End[2]));
					$Value = explode(',', $Map['Cities'][$Key]);

					imagefilledellipse($Image, $Value[0], $Value[1], 7, 7, $Background);
					imagefilledellipse($Image, $Value[0], $Value[1], 5, 5, $Colour);
				}
			}

			$Legend = explode(',', $Map['Options']['LegendLocation']);
			$Border = imagecolorallocate($Image, $Start[0], $Start[1], $Start[2]);

			imagefilledrectangle($Image, $Legend[0], $Legend[1], ($Legend[0] + 7), ($Legend[1] + 52), $Border);

			for ($i = 100; $i >= 0; $i -= 2)
			{
				imageline($Image, ($Legend[0] + 1), ($Legend[1] -($i / 2) + 51), ($Legend[0] + 6), ($Legend[1] -($i / 2) + 51), imagecolorallocate($Image, (floor($ColoursStep[0] * $i) + $End[0]), (floor($ColoursStep[1] * $i) + $End[1]), (floor($ColoursStep[2] * $i) + $End[2])));
			}

			self::drawText($Image, ($Legend[0] + 10), ($Legend[1] + 8), 8, $Data['max'].' ('.round((($Data['max'] / $Data['sum_current']) * 100), 2).'%)');
			self::drawText($Image, ($Legend[0] + 10), ($Legend[1] + 52), 8, '0');
		}

		self::saveImage($ID, $Image);
	}
}
?>