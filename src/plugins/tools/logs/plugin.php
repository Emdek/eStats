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

$entriesSearch = array();

if (isset($_GET['search']) && !isset($_POST['search']))
{
	$search = $_POST = $_GET;
}

if (isset($_POST['search']))
{
	$search = $_POST;

	foreach ($_POST as $key => $value)
	{
		if (is_array($value))
		{
			for ($i = 0, $c = count($value); $i < $c; ++$i)
			{
				$entriesSearch[] = $key.'[]='.urlencode($value[$i]);
			}
		}
		else
		{
			$entriesSearch[] = $key.'='.urlencode($value);
		}
	}
}
else
{
	$search = NULL;
}

if (!isset($path[3]))
{
	$path[3] = 0;
}

$entriesPerPage = 50;

if (EstatsCookie::get('logsPerPage'))
{
	$entriesPerPage = EstatsCookie::get('logsPerPage');
}

if (isset($_POST['amount']))
{
	$entriesPerPage = (int) $_POST['amount'];

	EstatsCookie::set('logsPerPage', $entriesPerPage);
}

if (isset($_POST['export']))
{
	$entriesPerPage = 0;
	$page = 0;
}
else
{
	$page = (int) $path[3];
}

if ($search)
{
	$where = EstatsCore::timeClause('time', strtotime($search['from']), strtotime($search['to']));

	if (isset($search['filter']))
	{
		if ($where)
		{
			$where[] = EstatsDriver::OPERATOR_AND;
		}

		$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('log', EstatsDriver::OPERATOR_IN, $search['filter']));
	}

	if (!empty($search['search']))
	{
		if ($where)
		{
			$where[] = EstatsDriver::OPERATOR_AND;
		}

		$where[] = EstatsDriver::OPERATOR_GROUPING_START;
		$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('log', EstatsDriver::OPERATOR_LIKE, '%'.$search['search'].'%'));
		$where[] = EstatsDriver::OPERATOR_OR;
		$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('time', EstatsDriver::OPERATOR_LIKE, '%'.$search['search'].'%'));
		$where[] = EstatsDriver::OPERATOR_OR;
		$where[] = array(EstatsDriver::ELEMENT_OPERATION, array('info', EstatsDriver::OPERATOR_LIKE, '%'.$search['search'].'%'));
		$where[] = EstatsDriver::OPERATOR_GROUPING_END;
	}
}
else
{
	$where = array();
}

$entriesAmount = EstatsCore::driver()->selectAmount('logs');
$entriesFilteredAmount = ($where?EstatsCore::driver()->selectAmount('logs', $where):$entriesAmount);

if (!$page && $entriesPerPage)
{
	$page = ceil($entriesFilteredAmount / $entriesPerPage);
}

$from = ($entriesPerPage * ($page - 1));

if ($from > $entriesFilteredAmount)
{
	$from = 0;
	$page = 1;
}

if ($entriesFilteredAmount)
{
	$entries = EstatsCore::driver()->selectData(array('logs'), NULL, $where, $entriesPerPage, $from, array('time' => TRUE));
}
else
{
	$entries = array();
}

$eventStrings = EstatsCore::loadData('share/data/events.ini');

if (isset($_POST['export']))
{
	$export = 'eStats v'.ESTATS_VERSIONSTRING.' logs backup
Creation date: '.date('m.d.Y H:i:s').'

';

	for ($i = 0, $c = count($entries); $i < $c; ++$i)
	{
		$export.= '
'.$entries[$i]['time'].' - '.(isset($eventStrings[$entries[$i]['log']])?EstatsLocale::translate($eventStrings[$entries[$i]['log']]):htmlspecialchars($entries[$i]['log'])).($entries[$i]['info']?'('.$entries[$i]['info'].')':'');
	}

	header('Content-Type: application/force-download');
	header('Content-Disposition: attachment; filename=eStats_'.date('Y-m-d').'.log.bak');
	die(trim($export));
}

$amount = count($entries);
$filters = array();

foreach ($eventStrings as $key => $value)
{
	$filters[] = array($key, EstatsLocale::translate($value));
}

EstatsTheme::add('page', '<form action="{selfpath}" method="post">
<h3>
{heading-start}'.EstatsLocale::translate('Search').'{heading-end}
</h3>
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Find entry (search in all fields)'), '', 'search', (isset($_POST['search'])?stripslashes($_POST['search']):'')).EstatsGUI::optionRowWidget(EstatsLocale::translate('Results per page'), '', 'amount', $entriesPerPage).EstatsGUI::optionRowWidget(EstatsLocale::translate('Filter'), '', 'filter[]', (isset($_POST['filter'])?$_POST['filter']:array()), EstatsGUI::FIELD_SELECT, $filters).EstatsGUI::optionRowWidget(EstatsLocale::translate('In period'), '', '', EstatsLocale::translate('From').' <input name="from" value="'.(isset($_POST['from'])?$_POST['from']:date('Y-m-d H:00:00', eStats)).'">
'.EstatsLocale::translate('To').' <input name="to" value="'.(isset($_POST['to'])?$_POST['to']:date('Y-m-d H:00:00', strtotime('next hour'))).'">
', EstatsGUI::FIELD_CUSTOM).'<div class="buttons">
<input type="submit" value="'.EstatsLocale::translate('Show').'">
<input type="submit" name="export" value="'.EstatsLocale::translate('Export').'">
<input type="reset" value="'.EstatsLocale::translate('Reset').'">
</div>
<h3>
{heading-start}'.EstatsLocale::translate('Browse').'{heading-end}
</h3>
<p>
'.EstatsLocale::translate('Entries amount').': '.$entriesAmount.'. '.EstatsLocale::translate('Meeting conditions').': '.$entriesFilteredAmount.'. '.EstatsLocale::translate('Showed').': '.$amount.'.
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

for ($i = 0; $i < $amount; ++$i)
{
	EstatsTheme::append('page', '<tr>
<td>
<p>
<em>'.($i + 1 + (($page - 1) * $entriesPerPage)).'</em>.
</p>
</td>
<td>
<p>
'.date('d.m.Y H:i:s', (is_numeric($entries[$i]['time'])?$entries[$i]['time']:strtotime($entries[$i]['time']))).'
</p>
</td>
<td>
<p>
'.(isset($eventStrings[$entries[$i]['log']])?EstatsLocale::translate($eventStrings[$entries[$i]['log']]):htmlspecialchars($entries[$i]['log'])).'
</p>
</td>
<td>
<p>
'.($entries[$i]['info']?$entries[$i]['info']:' ').'
</p>
</td>
</tr>
');
}

if (!$amount)
{
	EstatsTheme::append('page', '<td colspan="4">
<strong>'.EstatsLocale::translate('None').'</strong>
</td>
');
}

EstatsTheme::append('page', '</table>
{table-end}</form>
'.EstatsGUI::linksWIdget($page, ceil($entriesFilteredAmount / $entriesPerPage), '{path}tools/logs/{page}{suffix}'.($entriesSearch?'{separator}'.implode('&amp;', $entriesSearch):'')));
?>