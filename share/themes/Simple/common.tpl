[start:menu-entry]<li id="menu_entry_{id}">
<a href="{link}" tabindex="{tabindex}" accesskey="{accesskey}">
{text}
</a>
<!--start:submenu-{id}--><ul id="submenu_{id}">
{submenu}</ul>
<!--end:submenu-{id}--></li>
[/end]

[start:announcement]<div border="1">
<h4>
{type}
</h4>
{content}
</div>
[/end]

[start:dateform]<form action="{selfpath}" method="post">
<p>
<label for="year">{lang_showdatafor}</label>:<br>
{dateprevious}{selectday}{selectmonth}{selectyear}{datenext}<input type="submit" value="{lang_show}" tabindex="{dateformindex}">
</p>
</form>
[/end]

[start:option-row]<p id="P_{id}">
<label>
{option}:{description}
<br>
{form}
</label>
</p>
[/end]

[start:group]<tr>
<th colspan="4">
<a href="{link}" tabindex="{tabindex}">{title}</a>
</th>
</tr>
{information}{rows}{summary}[/end]

[start:group-row]<tr>
<td>
<em>{number}</em>.
</td>
<td>
{icon}{value}
</td>
<td>
{amount}
</td>
<td>
<em>{percent}%</em>
</td>
</tr>
[/end]

[start:group-amount]<tr>
<td colspan="2" align="center">
<strong>{lang_sum}:</strong>
</td>
<td colspan="2">
<strong>{amount}</strong>
</td>
</tr>
[/end]

[start:group-none]<tr>
<td colspan="4" align="center">
<strong>{lang_none}</strong>
</td>
</tr>
[/end]

[start:group-information]<tr>
<td colspan="4" align="center">
{information}</td>
</tr>
[/end]

[start:heading-start][/end]

[start:heading-end][/end]

[start:table-start][/end]

[start:table-end][/end]

[start:links]<div align="center">
{links}
</div>
[/end]