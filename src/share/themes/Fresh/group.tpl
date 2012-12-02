[start:group-page]{dateform}<div id="group" class="group">
{group}<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
{chart}[/end]

[start:group]<table cellspacing="0" cellpadding="0">
<tr>
<th<!--start:group_chart--> colspan="2"<!--end:group_chart--> class="title">
<span class="icon_wrapper">
<img src="{datapath}/share/icons/pages/{id}.png" alt="" class="icon">
</span>
{title}
</th>
</tr>
<tr>
<td>
<table cellspacing="0" cellpadding="0">
{information}{rows}{summary}</table>
{links}</td>
<!--start:group_chart--><td class="pie">
<p>
<img src="{path}image{suffix}{separator}id={chartidpie}" alt="">
</p>
</td>
<!--end:group_chart--></tr>
</table>
[/end]

[start:group-row]<tr>
<td class="number">
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
<td class="bar">
<p>
<span>
<img src="{datapath}share/themes/Fresh/images/background_bar.png" alt="" style="height:10px;width:{bar}%;background:{colour} !important;">
</span>
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