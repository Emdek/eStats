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

	static function colour($string)
	{
		$colour = array();

		for ($i = 0; $i < 3; ++$i)
		{
			$colour[$i] = hexdec('0x'.substr($string, (2 * $i), 2));
		}

		return $colour;
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

	static function drawText($image, $x, $y, $size, $string, $colour = 0)
	{
		if (!$colour)
		{
			$colour = self::colour($colour);
		}

		imagettftext($image, $size, 0, $x, $y, imagecolorallocate($image, $colour[0], $colour[1], $colour[2]), './share/fonts/LiberationMono-Regular.ttf', $string);
	}

/**
 * Checks if cache is valid and uses it
 * @param string ID
 * @param integer Time
 */

	function cacheImage($iD, $time)
	{
		if (!EstatsCache::status($iD, $time, '.png'))
		{
			self::outputImage($iD);
		}
	}

/**
 * Saves image data
 * @param string ID
 * @param resource Image
 */

	static function saveImage($iD, $image)
	{
		imagetruecolortopalette($image, 0, 256);

		$fileName = EstatsCache::path($iD, '.png');

		if (is_file($fileName))
		{
			unlink($fileName);
		}

		touch($fileName);
		chmod($fileName, 0666);

		if (!imagepng($image, $fileName))
		{
			header('Content-type: image/png');
			imagepng($image);
			imagedestroy($image);
			die();
		}

		imagedestroy($image);

		self::outputImage($iD);
	}

/**
 * Sends image to browser
 * @param string ID
 */

	static function outputImage($iD)
	{
		header('Content-type: image/png');
		die(file_get_contents(EstatsCache::path($iD, '.png')));
	}

/**
 * Generates pie chart
 * @param string ID
 * @param array Data
 * @param string Category
 */

	static function chartPie($iD, $data, $category)
	{
		arsort($data['data']);

		$others = $amount = $j = 0;
		$percents = $names = $slices = $coordinates = array();

		for ($i = 0, $c = count($data['data']); $i < $c; ++$i)
		{
			$percent = (($data['data'][$i]['amount_current'] / $data['sum_current']) * 100);

			if (++$j <= 20 && ($percent >= 5 || (!$others && $j == $c)))
			{
				$percents[] = $percent;
				$names[] = $data['data'][$i]['name'];
				$amount += $data['data'][$i]['amount_current'];
			}
			else
			{
				++$others;
			}
		}

		if (($data['sum_current'] - $amount) > 0)
		{
			$percents[] = ((($data['sum_current'] - $amount) / $data['sum_current']) * 100);
		}

		unset($data);

		$amount = count($percents);
		$colours = explode('|', EstatsTheme::option('ChartPieColours'));

		for ($i = 0; $i < 2; ++$i)
		{
			$colours[$i] = self::colour($colours[$i]);
		}

		$image = imagecreatetruecolor(400, 400);

		imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
		imageantialias($image, TRUE);

		$start = 150;
		$end = 0;
		$x = 390;
		$coloursStep = array();

		for ($i = 0; $i < 3; ++$i)
		{
			$coloursStep[$i] = (($colours[1][$i] - $colours[0][$i]) / $amount);
		}

		for ($i = 0, $c = count($percents); $i < $c; ++$i)
		{
			$slices[] = array(
	$start,
	($start = ceil(($end += $percents[$i]) * 3.6) + 150),
	imagecolorallocate($image, $colours[0][0], $colours[0][1], $colours[0][2]),
	imagecolorallocate($image, ($colours[0][0] - 30), ($colours[0][1] - 30), ($colours[0][2] - 30))
	);
			$rad = deg2rad($slices[$i][0] + (($slices[$i][1] - $slices[$i][0]) / 2));
			$coordinates[] = array(((int) (100 * cos($rad)) + 150), ((int) (75 * sin($rad)) + 105));
			$x += 42;

			for ($j = 0; $j < 3; ++$j)
			{
				$colours[0][$j] += $coloursStep[$j];
			}
		}

		for ($i = 170; $i > 150; --$i)
		{
			for ($j = 0, $l = count($slices); $j < $l; ++$j)
			{
				if ($percents[$j] < 1)
				{
					continue;
				}

				imagefilledarc($image, 200, $i, 397, 297, $slices[$j][0], $slices[$j][1], $slices[$j][3], IMG_ARC_PIE);
			}
		}

		for ($i = 0, $c = count($slices); $i < $c; ++$i)
		{
			if ($percents[$i] < 1)
			{
				continue;
			}

			imagefilledarc($image, 200, 150, 397, 297, $slices[$i][0], $slices[$i][1], $slices[$i][2], IMG_ARC_PIE);
		}

		$finalImage = imagecreatetruecolor(200, 200);

		imagefill($finalImage, 0, 0, imagecolorallocate($image, 255, 255, 255));
		imagecopyresampled($finalImage, $image, 0, 0, 0, 0, 200, 200, 400, 400);
		imagedestroy($image);

		if (function_exists('imagefilter'))
		{
			imagefilter($finalImage, IMG_FILTER_SMOOTH, 5);
		}

		for ($i = 0, $c = count($percents); $i < $c; ++$i)
		{
			$coordinates[$i][0] -= ($coordinates[$i][0] / 3);
			$coordinates[$i][1] -= ($coordinates[$i][1] / 3);

			if (isset($names[$i]))
			{
				$icon = EstatsGUI::iconPath($names[$i], $category);

				if ($icon && is_file('./'.$icon))
				{
					$tmpImage = imagecreatefrompng('./'.$icon);

					if ($percents[$i] < 100 && $percents[$i] >= 5)
					{
						imagecopyresampled($finalImage, $tmpImage, ($coordinates[$i][0] - 10), ($coordinates[$i][1] + 2), 0, 0, 16, 16, 16, 16);
					}

					imagedestroy($tmpImage);
				}
			}

			if ($percents[$i] != 100)
			{
				$string = round($percents[$i], 2).'%';

				if ($percents[$i] >= 1)
				{
					self::drawText($finalImage, ($coordinates[$i][0] - 20), $coordinates[$i][1], 7, ((strlen($string) < 5)?str_repeat(' ', ((7 - strlen($string)) / 2)):'').$string);
				}
			}
		}

		imagecolortransparent($finalImage, imagecolorallocate($finalImage, 255, 255, 255));

		self::saveImage($iD, $finalImage);
	}

/**
 * Generates time chart
 * @param string ID
 * @param array Data
 * @param array Summary
 * @param string Type
 * @param boolean Join
 */

	static function chartTime($iD, $data, $summary, $type, $join)
	{
		if (!$summary['maxall'])
		{
			$image = imagecreatetruecolor(7500, 170);

			imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
			imagecolortransparent($image, imagecolorallocate($image, 255, 255, 255));

			self::saveImage($iD, $image);
		}

		$image = imagecreatetruecolor(1500, 340);

		imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
		imagecolortransparent($image, imagecolorallocate($image, 255, 255, 255));
		imageantialias($image, TRUE);

		$colours = explode('|', EstatsTheme::option('ChartTimeColours'));

		for ($i = 0, $c = count($colours); $i < $c; ++$i)
		{
			$colour = self::colour($colours[$i]);
			$colours[$i] = imagecolorallocatealpha($image, $colour[0], $colour[1], $colour[2], 30);
			$coloursDark[$i] = imagecolorallocate($image, $colour[0], $colour[1], $colour[2]);
		}

		$typesAmount = count($summary['types']);
		$width = (2 * round(700 /($summary['amount'])));
		$chartData = array();
		$timeUnit = array(0, (in_array($summary['chart'], array('hours', 'weekdays'))?($summary['currenttime']?-1:0):$summary['timestamp']));

		for ($i = 0; $i < $summary['amount']; ++$i)
		{
			$timeUnit = EstatsGUI::timeUnit($summary['chart'], $timeUnit[1], $summary['step'], $summary['format'], $summary['currenttime']);
			$unitID = &$timeUnit[0];

			for ($j = 0; $j < $typesAmount; ++$j)
			{
				$chartData[$summary['types'][$j]][$i] = (isset($data[$unitID][$summary['types'][$j]])?$data[$unitID][$summary['types'][$j]]:0);
			}
		}

		unset($data);

		if ($type == 'bars')
		{
			$barWidth = (($width / $typesAmount) * 0.8);
			$barMargin = (($width / $typesAmount) * 0.3);
		}
		else
		{
			for ($i = 0; $i < $typesAmount; ++$i)
			{
				$chartData[$summary['types'][$i]][-1] = $chartData[$summary['types'][$i]][$join?($summary['amount'] - 1):0];
				$chartData[$summary['types'][$i]][$summary['amount']] = $chartData[$summary['types'][$i]][$join?0:($summary['amount'] - 1)];
			}
		}

		for ($i = 0; $i < $typesAmount; ++$i)
		{
			$x = (($type == 'bars')?0:-($width / 2));

			for ($j = (($type == 'bars')?0:-1); $j < $summary['amount']; ++$j)
			{
				$y = (336 - (($chartData[$summary['types'][$i]][$j] / $summary['maxall']) * 300));

				if (!$chartData[$summary['types'][$i]][$j] && !$chartData[$summary['types'][$i]][$j + 1])
				{
					$x += $width;

					continue;
				}

				switch ($type)
				{
					case 'bars':
						imagefilledrectangle($image, ($x + $barMargin +($barWidth * $i) - 2), ($y - 2), ($x +($barWidth *($i + 1)) + 2), 340, $coloursDark[$i]);
						imagefilledrectangle($image, ($x + $barMargin +($barWidth * $i)), $y, ($x +($barWidth *($i + 1))), 340, $colours[$i]);
					break;
					case 'lines':
						imageline($image, $x, $y, ($x + $width), (336 -(($chartData[$summary['types'][$i]][$j + 1] / $summary['maxall']) * 300)), $colours[$i]);
						imageline($image, $x, ($y + 1), ($x + $width), (337 -(($chartData[$summary['types'][$i]][$j + 1] / $summary['maxall']) * 300)), $colours[$i]);
					break;
					case 'areas':
						$points = array(
	$x, 340,
	$x, $y,
	($x + $width), (336 -(($chartData[$summary['types'][$i]][$j + 1] / $summary['maxall']) * 300)),
	($x + $width), 340
	);
						imagefilledpolygon($image, $points, 4, $colours[$i]);
					break;
				}

				if ($type != 'bars' && $chartData[$summary['types'][$i]][$j])
				{
					imagefilledellipse($image, $x, $y, 8, 8, $coloursDark[$i]);
				}

				$x += $width;
			}
		}

		$finalImage = imagecreatetruecolor(700, 170);

		imagecopyresampled($finalImage, $image, 0, 0, 0, 0, 700, 170, 1400, 340);
		imagecolortransparent($finalImage, imagecolorallocate($finalImage, 255, 255, 255));

		if (function_exists('imagefilter'))
		{
			imagefilter($finalImage, IMG_FILTER_SMOOTH, 25);
		}

		imagedestroy($image);

		self::saveImage($iD, $finalImage);
	}

/**
 * Generates map
 * @param string ID
 * @param array Data
 * @param array Type
 */

	static function map($iD, $data, $type)
	{
		$continents = isset($data['continents']);
		$map = EstatsCore::loadData('share/maps/'.$type[0].'/map.ini', TRUE);
		$colours = explode('|', EstatsTheme::option('MapColours'));
		$start = self::colour($colours[0]);
		$end = self::colour($colours[1]);
		$coloursStep = array();

		for ($i = 0; $i < 3; ++$i)
		{
			$coloursStep[$i] = (($start[$i] - $end[$i]) / 100);
		}

		$image = imagecreatefrompng('./share/maps/'.$type[0].'/map.png');

		if ($data['max'])
		{
			$border = imagecolorat($image, 0, 0);

			if ($continents)
			{
				$countryToContinent = EstatsCore::loadData('share/data/country-to-continent.ini');
			}

			foreach ($map['Divisions'] as $key => $value)
			{
				$key = trim($key, '\\');

				if ($type[0] != 'world')
				{
					$key = $type[0].'-'.$key;
				}

				if ($continents)
				{
					$key = $countryToContinent[$key];
				}

				if (isset($data[$continents?'continents':'data'][$key]) && $data[$continents?'continents':'data'][$key])
				{
					$percent = (($data[$continents?'continents':'data'][$key] / $data['max']) * 100);
					$colour = imagecolorallocate($image, (floor($coloursStep[0] * $percent) + $end[0]), (floor($coloursStep[1] * $percent) + $end[1]), (floor($coloursStep[2] * $percent) + $end[2]));
					$value = explode(',', $value);

					for ($i = 0, $c = count($value); $i < $c; $i += 2)
					{
						imagefilltoborder($image, $value[$i], $value[$i + 1], $border, $colour);
					}
				}

				if (isset($map['Flags'][$key]))
				{
					$imageFile = EstatsGUI::iconPath($key, 'countries');

					if (is_file('./'.$imageFile))
					{
						$coordinates = explode(',', $map['Flags'][$key]);
						$tmpImage = imagecreatefrompng('./'.$imageFile);

						imagecopyresampled($image, $tmpImage, $coordinates[0], $coordinates[1], 0, 0, 16, 16, 16, 16);
						imagedestroy($tmpImage);
					}
				}
			}

			if (isset($map['Cities']))
			{
				$background = imagecolorallocate($image, 250, 250, 250);

				foreach ($data['cities']['data'] as $key => $value)
				{
					$key = number_format(round($value['latitude'], 2), 2, '.', '').','.number_format(round($value['longitude'], 2), 2, '.', '');

					if (!isset($map['Cities'][$key]))
					{
						continue;
					}

					$percent = (($value['amount_current'] / $data['max']) * 100);
					$colour = imagecolorallocate($image, (floor($coloursStep[0] * $percent) + $end[0]), (floor($coloursStep[1] * $percent) + $end[1]), (floor($coloursStep[2] * $percent) + $end[2]));
					$value = explode(',', $map['Cities'][$key]);

					imagefilledellipse($image, $value[0], $value[1], 7, 7, $background);
					imagefilledellipse($image, $value[0], $value[1], 5, 5, $colour);
				}
			}

			$legend = explode(',', $map['Options']['LegendLocation']);
			$border = imagecolorallocate($image, $start[0], $start[1], $start[2]);

			imagefilledrectangle($image, $legend[0], $legend[1], ($legend[0] + 7), ($legend[1] + 52), $border);

			for ($i = 100; $i >= 0; $i -= 2)
			{
				imageline($image, ($legend[0] + 1), ($legend[1] -($i / 2) + 51), ($legend[0] + 6), ($legend[1] -($i / 2) + 51), imagecolorallocate($image, (floor($coloursStep[0] * $i) + $end[0]), (floor($coloursStep[1] * $i) + $end[1]), (floor($coloursStep[2] * $i) + $end[2])));
			}

			self::drawText($image, ($legend[0] + 10), ($legend[1] + 8), 8, $data['max'].' ('.round((($data['max'] / $data['sum_current']) * 100), 2).'%)');
			self::drawText($image, ($legend[0] + 10), ($legend[1] + 52), 8, '0');
		}

		self::saveImage($iD, $image);
	}
}
?>