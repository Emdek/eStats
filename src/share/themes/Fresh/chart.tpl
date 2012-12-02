[start:chart]<div class="time">
<table cellspacing="0" cellpadding="0">
<tr>
<th class="corner_left_top">
<img src="{datapath}/share/icons/pages/{id}.png" alt="" class="icon">
</th>
<th class="title">
{title}
</th>
<th class="corner_right_top">
&nbsp;
</th>
</tr>
<tr>
<td colspan="3">
{cacheinformation}<table cellspacing="0" cellpadding="0" id="chart_{id}" class="chart{class}">
<tfoot>
{footer}</tfoot>
<tbody{style}>
{chart}</tbody>
</table>
</td>
</tr>
<tr>
<td colspan="3">
<h3>
<span class="header_left"></span>
<span class="header_right"></span>
{lang_summary}
</h3>
{summary}</td>
</tr>
</table>
<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
{switch}[/end]

[start:chart-bars-container]<td id="{id}" class="{class}" style="width:{width}px;" onclick="{action}">
<div>
{bars}</div>
{tooltip}</td>
[/end]

[start:chart-bar]<div id="{id}" class="{class}" style="height:{height}px;margin-top:{margin}px;background:{colour};" title="{title}"></div>
[/end]

[start:chart-summary]<table cellpadding="0" cellspacing="0" id="summary_{id}" class="summary">
<tr>
<th>
&nbsp;
</th>
<th onmouseover="highlightBars('{id}', '-1', 'sum', 1)" onmouseout="highlightBars('{id}', '-1', 'sum', 0)">
<p>
{lang_sum}
</p>
</th>
<th onmouseover="highlightBars('{id}', '-1', 'maximum', 1)" onmouseout="highlightBars('{id}', '-1', 'maximum', 0)">
<p>
{lang_most}
</p>
</th>
<th onmouseover="highlightBars('{id}', '-1', 'average', 1)" onmouseout="highlightBars('{id}', '-1', 'average', 0)">
<p>
{lang_average}
</p>
</th>
<th onmouseover="highlightBars('{id}', '-1', 'minimum', 1)" onmouseout="highlightBars('{id}', '-1', 'minimum', 0)">
<p>
{lang_least}
</p>
</th>
</tr>
{rows}</table>
[/end]

[start:chart-summary-row]<tr>
<th onmouseover="highlightBars('{id}', '{number}', 'sum', 1)" onmouseout="highlightBars('{id}', '{number}', 'sum', 0)">
<p>
<span id="legend_{id}_{number}" class="legend" style="background:{colour};"></span>
{text}
</p>
</th>
<td onmouseover="highlightBars('{id}', '{number}', 'sum', 1)" onmouseout="highlightBars('{id}', '{number}', 'sum', 0)">
<p>
{sum}
<!--start:time_difference-->(<em class="{sum_class}">{sum_difference}</em>)
<!--end:time_difference--></p>
</td>
<td onmouseover="highlightBars('{id}', '{number}', 'maximum', 1)" onmouseout="highlightBars('{id}', '{number}', 'maximum', 0)">
<p>
{maximum}
<!--start:time_difference-->(<em class="{max_class}">{maximum_difference}</em>)
<!--end:time_difference--></p>
</td>
<td onmouseover="highlightBars('{id}', '{number}', 'average', 1)" onmouseout="highlightBars('{id}', '{number}', 'average', 0)">
<p>
{average}
<!--start:time_difference-->(<em class="{average_class}">{average_difference}</em>)
<!--end:time_difference--></p>
</td>
<td onmouseover="highlightBars('{id}', '{number}', 'minimum', 1)" onmouseout="highlightBars('{id}', '{number}', 'minimum', 0)">
<p>
{minimum}
<!--start:time_difference-->(<em class="{min_class}">{minimum_difference}</em>)
<!--end:time_difference--></p>
</td>
</tr>
[/end]