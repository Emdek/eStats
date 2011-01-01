[start:group-page]{dateform}<table cellspacing="0" cellpadding="0" id="group">
<tr>
<th>
{lang_fulllist}
</th>
</tr>
<tr>
<td>
{group}</td>
</tr>
<!--start:group_chart--><tr>
<th>
{lang_chart}
</th>
</tr>
<tr>
<td>
<img src="{path}image{suffix}{separator}id={chartidpie}" alt="" />
</td>
</tr><!--end:group_chart--></table>
[/end]

[start:group]<table cellspacing="0" cellpadding="0">
<tbody>
{information}{rows}{summary}</tbody>
</table>
{links}[/end]

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
<td class="bar">
<div>
<div style="width:{bar}%;"></div>
</div>
{percent}
</td>
<!--start:group_difference--><td>
<em class="{class}">{difference}</em>
</td>
<!--end:group_difference--></tr>
[/end]