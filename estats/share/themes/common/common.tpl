[start:menu-entry]<li id="menu_entry_{id}">
<a href="{link}" tabindex="{tabindex}" accesskey="{accesskey}" class="{class}">
<!--start:submenu-{id}-->&#8595;&nbsp;<!--end:submenu-{id}--><span>{text}</span>
</a>
<!--start:submenu-{id}--><ul id="submenu_{id}">
{submenu}</ul>
<!--end:submenu-{id}--></li>
[/end]

[start:submenu-entry]<li id="menu_entry_{id}">
<a href="{link}" tabindex="{tabindex}" accesskey="{accesskey}" class="{class}">
<span>{text}</span>
</a>
</li>
[/end]

[start:announcement]<div class="{class}" title="{type}">
{content}
</div>
[/end]

[start:dateform]<form action="{selfpath}" method="post" id="dateform">
<p>
<label>
<span>
{selecthour}{selectday}{selectmonth}{selectyear}{selectmap}<input type="submit" value="{lang_show}" tabindex="{dateformindex}"  />{dateprevious}{datenext}</span>
{lang_showdatafor}:
</label>
</p>
</form>
[/end]

[start:option-row]<p id="P_{id}"{changed}>
<label>
<span>
{form}
</span>
{option}:{description}
</label>
</p>
[/end]

[start:group]<table cellspacing="0" cellpadding="0" id="group_{id}" class="group">
<thead>
<tr>
<th colspan="<!--start:group_difference-->5<!--end:group_difference--><!--start:!group_difference-->4<!--end:!group_difference-->">
<a href="{link}" tabindex="{tabindex}">{title}</a>
</th>
</tr>
</thead>
<tbody>
{information}{rows}{summary}</tbody>
</table>
[/end]

[start:group-row]<tr>
<td class="auto">
<em>{number}</em>.
</td>
<td class="wide">
{icon}{value}
</td>
<td>
{amount}
</td>
<td>
<em>{percent}</em>
</td>
<!--start:group_difference--><td>
<em class="{class}">{difference}</em>
</td>
<!--end:group_difference--></tr>
[/end]

[start:group-amount]<tr>
<td colspan="2">
<strong>{lang_sum}:</strong>
</td>
<td>
<strong>{amount}</strong>
</td>
<td>
&nbsp;
</td>
<!--start:group_difference--><td>
<em class="{class}">{difference}</em>
</td>
<!--end:group_difference--></tr>
[/end]

[start:group-none]<tr>
<td colspan="<!--start:group_difference-->5<!--end:group_difference--><!--start:!group_difference-->4<!--end:!group_difference-->">
<strong>{lang_none}</strong>
</td>
</tr>
[/end]

[start:group-information]<tr>
<td colspan="<!--start:group_difference-->5<!--end:group_difference--><!--start:!group_difference-->4<!--end:!group_difference-->">
{information}</td>
</tr>
[/end]

[start:heading-start][/end]

[start:heading-end][/end]

[start:table-start][/end]

[start:table-end][/end]

[start:links]<div class="links">
{links}
</div>
[/end]