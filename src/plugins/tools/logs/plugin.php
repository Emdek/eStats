<?php
/**
 * Logs viewer GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.9.50
 */

if (!defined('eStats'))
{
	die();
}

$EntriesSearch = array();

if (isset($_GET['search']) && !isset($_POST['search']))
{
	$Search = $_POST = $_GET;
}

if (isset($_POST['search']))
{
	$Search = $_POST;

	foreach ($_POST as $Key => $Value)
	{
		if (is_array($Value))
		{
			for ($i = 0, $c = count($Value); $i < $c; ++$i)
			{
				$EntriesSearch[] = $Key.'[]='.urlencode($Value[$i]);
			}
		}
		else
		{
			$EntriesSearch[] = $Key.'='.urlencode($Value);
		}
	}
}
else
{
	$Search = NULL;
}

if (!isset($Path[3]))
{
	$Path[3] = 0;
}

$EntriesPerPage = 50;

if (EstatsCookie::get('logsPerPage'))
{
	$EntriesPerPage = EstatsCookie::get('logsPerPage');
}

if (isset($_POST['amount']))
{
	$EntriesPerPage = (int) $_POST['amount'];

	EstatsCookie::set('logsPerPage', $EntriesPerPage);
}

if (isset($_POST['export']))
{
	$EntriesPerPage = 0;
	$Page = 0;
}
else
{
	$Page = (int) $Path[3];
}

if ($Search)
{
	$Where = EstatsCore::timeClause('time', strtotime($Search['from']), strtotime($Search['to']));

	if (isset($Search['filter']))
	{
		if ($Where)
		{
			$Where[] = EstatsDriver::OPERATOR_AND;
		}

		$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('log', EstatsDriver::OPERATOR_IN, $Search['filter']));
	}

	if (!empty($Search['search']))
	{
		if ($Where)
		{
			$Where[] = EstatsDriver::OPERATOR_AND;
		}

		$Where[] = EstatsDriver::OPERATOR_GROUPING_START;
		$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('log', EstatsDriver::OPERATOR_LIKE, '%'.$Search['search'].'%'));
		$Where[] = EstatsDriver::OPERATOR_OR;
		$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_LIKE, '%'.$Search['search'].'%'));
		$Where[] = EstatsDriver::OPERATOR_OR;
		$Where[] = array(EstatsDriver::ELEMENT_OPERATION, array('info', EstatsDriver::OPERATOR_LIKE, '%'.$Search['search'].'%'));
		$Where[] = EstatsDriver::OPERATOR_GROUPING_END;
	}
}
else
{
	$Where = array();
}

$EntriesAmount = EstatsCore::driver()->selectAmount('logs');
$EntriesFilteredAmount = ($Where?EstatsCore::driver()->selectAmount('logs', $Where):$EntriesAmount);

if (!$Page && $EntriesPerPage)
{
	$Page = ceil($EntriesFilteredAmount / $EntriesPerPage);
}

$From = ($EntriesPerPage * ($Page - 1));

if ($From > $EntriesFilteredAmount)
{
	$From = 0;
	$Page = 1;
}

if ($EntriesFilteredAmount)
{
	$Entries = EstatsCore::driver()->selectData(array('logs'), NULL, $Where, $EntriesPerPage, $From, array('time' => TRUE));
}
else
{
	$Entries = array();
}

$EventStrings = EstatsCore::loadData('share/data/events.ini');

if (isset($_POST['export']))
{
	$Export = 'eStats v'.ESTATS_VERSIONSTRING.' logs backup
Creation date: '.date('m.d.Y H:i:s').'

';

	for ($i = 0, $c = count($Entries); $i < $c; ++$i)
	{
		$Export.= '
'.$Entries[$i]['time'].' - '.(isset($EventStrings[$Entries[$i]['log']])?EstatsLocale::translate($EventStrings[$Entries[$i]['log']]):htmlspecialchars($Entries[$i]['log'])).($Entries[$i]['info']?'('.$Entries[$i]['info'].')':'');
	}

	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename=eStats_'.date('Y-m-d').'.log.bak');
	die(trim($Export));
}

$Amount = count($Entries);
$Filters = array();

foreach ($EventStrings as $Key => $Value)
{
	$Filters[] = array($Key, EstatsLocale::translate($Value));
}

EstatsTheme::add('page', '<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Search').'{heading-end}
</h3>
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Find entry (search in all fields)'), '', 'search', (isset($_POST['search'])?stripslashes($_POST['search']):'')).EstatsGUI::optionRowWidget(EstatsLocale::translate('Results per page'), '', 'amount', $EntriesPerPage).EstatsGUI::optionRowWidget(EstatsLocale::translate('Filter'), '', 'filter[]', (isset($_POST['filter'])?$_POST['filter']:array()), EstatsGUI::FIELD_SELECT, $Filters).EstatsGUI::optionRowWidget(EstatsLocale::translate('In period'), '', '', EstatsLocale::translate('From').' <input name="from" value="'.(isset($_POST['from'])?$_POST['from']:date('Y-m-d H:00:00', eStats)).'" />
'.EstatsLocale::translate('To').' <input name="to" value="'.(isset($_POST['to'])?$_POST['to']:date('Y-m-d H:00:00', strtotime('next hour'))).'" />
', EstatsGUI::FIELD_CUSTOM).'<div class="buttons">
<input type="submit" value="'.EstatsLocale::translate('Show').'" />
<input type="submit" name="export" value="'.EstatsLocale::translate('Export').'" />
<input type="reset" value="'.EstatsLocale::translate('Reset').'" />
</div>
<h3>
{heading-start}'.EstatsLocale::translate('Browse').'{heading-end}
</h3>
<p>
'.EstatsLocale::translate('Entries amount').': '.$EntriesAmount.'. '.EstatsLocale::translate('Meeting conditions').': '.$EntriesFilteredAmount.'. '.EstatsLocale::translate('Showed').': '.$Amount.'.
</p>
{table-start}<table cellspacing="0" cellpadding="1">
<tr>
<th>
#
</th>
<th>
'.EstatsLocale::translate('Date').'
</th>
<th>
'.EstatsLocale::translate('Log').'
</th>
<th>
'.EstatsLocale::translate('Information').'
</th>
</tr>
');

for ($i = 0; $i < $Amount; ++$i)
{
	EstatsTheme::append('page', '<tr>
<td>
<p>
<em>'.($i + 1 + (($Page - 1) * $EntriesPerPage)).'</em>.
</p>
</td>
<td>
<p>
'.date('d.m.Y H:i:s', (is_numeric($Entries[$i]['time'])?$Entries[$i]['time']:strtotime($Entries[$i]['time']))).'
</p>
</td>
<td>
<p>
'.(isset($EventStrings[$Entries[$i]['log']])?EstatsLocale::translate($EventStrings[$Entries[$i]['log']]):htmlspecialchars($Entries[$i]['log'])).'
</p>
</td>
<td>
<p>
'.($Entries[$i]['info']?$Entries[$i]['info']:' ').'
</p>
</td>
</tr>
');
}

if (!$Amount)
{
	EstatsTheme::append('page', '<td colspan="4">
<strong>'.EstatsLocale::translate('None').'</strong>
</td>
');
}

EstatsTheme::append('page', '</table>
{table-end}</form>
'.EstatsGUI::linksWIdget($Page, ceil($EntriesFilteredAmount / $EntriesPerPage), '{path}tools/logs/{page}{suffix}'.($EntriesSearch?'{separator}'.implode('&amp;', $EntriesSearch):'')));
?>