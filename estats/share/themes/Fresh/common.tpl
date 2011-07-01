[start:menu-entry]<li id="menu_entry_{id}" class="{class}<!--start:submenu-{id}--> submenu<!--end:submenu-{id}-->">
<a href="{link}" tabindex="{tabindex}" accesskey="{accesskey}" class="{class}">
<span class="menu_entry_right">
<span class="menu_entry_left"></span>
{text}
<img src="{icon}" alt="" />
</span>
</a>
<!--start:submenu-{id}--><ul id="submenu_{id}">
{submenu}</ul>
<!--end:submenu-{id}--></li>
[/end]

[start:submenu-entry]<li id="menu_entry_{id}">
<a href="{link}" tabindex="{tabindex}" accesskey="{accesskey}" class="{class}">
<span class="submenu_entry_right">
<span class="submenu_entry_left"></span>
<img src="{icon}" alt="" />
{text}
</span>
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
{selecthour}{selectday}{selectmonth}{selectyear}{selectmap}<input type="submit" value="{lang_show}" tabindex="{dateformindex}" />{dateprevious}{datenext}</span>
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

[start:group]<div class="group">
<table cellspacing="0" cellpadding="0" id="group_{id}">
<tr>
<th class="corner_left_top">
<img src="{datapath}/share/icons/pages/{id}.png" alt="" class="icon" />
</th>
<th colspan="<!--start:group_difference-->4<!--end:group_difference--><!--start:!group_difference-->3<!--end:!group_difference-->" class="title">
<a href="{link}" tabindex="{tabindex}">{title}</a>
</th>
</tr>
{information}{rows}{summary}</table>
<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
[/end]

[start:group-row]<tr>
<td>
<p>
<em>{number}</em>.
</p>
</td>
<td class="wide">
<p>
{icon}{value}
</p>
</td>
<td>
<p>
{amount}
</p>
</td>
<td>
<p>
<em>{percent}</em>
</p>
</td>
<!--start:group_difference--><td>
<p>
<em class="{class}">{difference}</em>
</p>
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

[start:heading-start]<span class="header_left"></span>
<span class="header_right"></span>
[/end]

[start:heading-end][/end]

[start:table-start]<div class="group">
[/end]

[start:table-end]<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
[/end]

[start:links]<div class="links">
{links}
</div>
[/end]