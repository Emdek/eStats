<?php
/**
 * Users management GUI for eStats
 * @author Emdek <http://emdek.pl>
 * @version 5.0.00
 */

if (!defined('eStats'))
{
	die();
}

if (isset($_POST['AddUser']))
{
	if (empty($_POST['UserPassword']) || empty($_POST['RepeatPassword']) || empty($_POST['Email']))
	{
		EstatsGUI::notify(EstatsLocale::translate('You must fill all fields!'), 'error');
	}
	else if ($_POST['UserPassword'] != $_POST['RepeatPassword'])
	{
		EstatsGUI::notify(EstatsLocale::translate('Given passwords are not the same!'), 'error');
	}
	else if (!preg_match('#\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b#i', $_POST['Email']))
	{
		EstatsGUI::notify(EstatsLocale::translate('Invalid email address!'), 'error');
	}
	else
	{
		if (strlen($_POST['UserPassword']) < 5)
		{
			EstatsGUI::notify(EstatsLocale::translate('Password has less than five characters, you should choose longer password for greater security.'), 'warning');
		}

		if (EstatsCore::driver()->insertData('users', array('email' => $_POST['Email'], 'password' => md5($_POST['UserPassword']), 'level' => (isset($_POST['AdministratorRights'])?3:2))))
		{
			EstatsGUI::notify(EstatsLocale::translate('User added successfully'), 'success');
		}
		else
		{
			EstatsGUI::notify(EstatsLocale::translate('An error occured while adding user!'), 'error');
		}
	}
}

EstatsTheme::add('page', '<h3>
{heading-start}'.EstatsLocale::translate('Add user').'{heading-end}
</h3>
<form action="{selfpath}" method="post">
'.EstatsGUI::optionRowWidget(EstatsLocale::translate('Email'), '', 'Email').EstatsGUI::optionRowWidget(EstatsLocale::translate('Password'), '', 'UserPassword').EstatsGUI::optionRowWidget(EstatsLocale::translate('Repeat password'), '', 'RepeatPassword').EstatsGUI::optionRowWidget(EstatsLocale::translate('Administrator rights'), '', 'AdministratorRights', FALSE, EstatsGUI::FIELD_BOOLEAN).'<div class="buttons">
<input type="submit" name="AddUser" value="'.EstatsLocale::translate('Add user').'" tabindex="'.EstatsGUI::tabindex().'" />
</div>
</form>
<h3>
{heading-start}'.EstatsLocale::translate('Existing users').'{heading-end}
</h3>
{table-start}<table cellspacing="0" cellpadding="1">
<tr>
<th>
#
</th>
<th>
'.EstatsLocale::translate('Email').'
</th>
<th>
'.EstatsLocale::translate('Statistics').'
</th>
<th>
'.EstatsLocale::translate('Actions').'
</th>
</tr>
');

$Users = EstatsCore::driver()->selectData(array('users'));

for ($i = 0, $c = count($Users); $i < $c; ++$i)
{
	EstatsTheme::append('page', '<tr>
<td>
<p>
<em>'.(int) $Users[$i]['level'].'</em>.
</p>
</td>
<td>
<p>
<a href="mailto:'.htmlspecialchars($Users[$i]['email']).'" tabindex='.EstatsGUI::tabindex().'>'.htmlspecialchars($Users[$i]['email']).'</a>
</p>
</td>
<td>
<p>

</p>
</td>
<td>
<p>

</p>
</td>
</tr>
');
}

EstatsTheme::append('page', '</table>
{table-end}');
?>