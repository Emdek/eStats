[start:chart]{cacheinformation}<table cellspacing="0" cellpadding="0" width="100%" border="1">
<tr>
<th colspan="{colspan}">
<a href="{link}">
{title}
</a>
</th>
</tr>
{chart}</table><br>
{summary}[/end]

[start:chart-bars-container]<td valign="bottom" height="150" align="center">
<table cellspacing="0" cellpadding="0" align="center" width="100%">
<tr>
{bars}</tr>
</table>
</td>
[/end]

[start:chart-bar]<td valign="bottom" width="30%">
<table cellspacing="0" cellpadding="0" height="{height}" width="80%" bgcolor="gray" title="{title}" border="1">
<tr>
<td height="{height}" width="100%">
{simplebar}<img src="" alt="" height="{height}">
</td>
</tr>
</table>
</td>
[/end]

[start:chart-summary]<table cellpadding="3" cellspacing="0" width="100%" border="1">
<tr>
<th colspan="5">{lang_summary}</th>
</tr>
<tr>
<th>
&nbsp;
</th>
<th>
{lang_sum}
</th>
<th>
{lang_most}
</th>
<th>
{lang_average}
</th>
<th>
{lang_least}
</th>
</tr>
{rows}</table>
[/end]

[start:chart-summary-row]<tr>
<th>
{text}
</th>
<td align="center">
{sum}
</td>
<td align="center">
{max}
</td>
<td align="center">
{average}
</td>
<td align="center">
{min}
</td>
</tr>
[/end]