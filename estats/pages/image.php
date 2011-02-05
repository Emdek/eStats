<?php
/**
 * Image display script for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

if (!isset($_GET['id']) || !isset($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]))
{
	die('No data!');
}

if (!EstatsGraphics::isAvailable())
{
	die('Graphics extension unavailable!');
}

header('Expires: '.gmdate('r', 0));
header('Last-Modified: '.gmdate('r'));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if ($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['type'] == 'map')
{
	$MapType = explode('-', $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['map']);
	$MapID = 'map-'.$MapType[0].(($MapType[0] == 'world')?'-'.$MapType[1]:'').'-'.$_SESSION[EstatsCore::session()]['locale'];

	EstatsGraphics::cacheImage($MapID, EstatsCore::option('Cache/others'));
	EstatsGraphics::map($MapType, $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['data'], $MapID);
}

if ($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['type'] == 'chart')
{
	$MapID = 'chart-'.$_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['diagram'].'-'.$_SESSION[EstatsCore::session()]['theme'].(isset($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['mode'])?'-'.$_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['mode']:'').'-'.$_SESSION[EstatsCore::session()]['locale'];

	if ($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['chart'] == 'pie')
	{
		EstatsGraphics::cacheImage($MapID, EstatsCore::option('Cache/others'));
		EstatsGraphics::chartPie($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['diagram'], $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['data'], $MapID);
	}

	if (in_array($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['chart'], array('areas', 'bars', 'lines')))
	{
		EstatsGraphics::cacheImage($MapID, $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['cache']);
		EstatsGraphics::chartTime($_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['diagram'], $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['data'], $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['summary'], $MapID, $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['chart'], $_SESSION[EstatsCore::session()]['imagedata'][$_GET['id']]['join']);
	}
}

die('Invalid data!');
?>