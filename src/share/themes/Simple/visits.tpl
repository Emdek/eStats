[start:visits]{cacheinformation}<form action="{selfpath}" method="post">
<p>
<label>
{lang_showrobots}:<br>
<input type="hidden" name="ChangeRobots" value="1">
<input type="checkbox" name="ShowRobots" value="1" {robotscheckbox}>
<input type="submit" value="{lang_change}" tabindex="{robotsformindex}">
</label>
</p>
</form>
<table cellpadding="2" cellspacing="0" border="1" width="100%">
<thead>
<tr>
<th>
&nbsp;
</th>
<th>
#
</th>
<th>
{lang_firstvisit}
</th>
<th>
{lang_lastvisit}
</th>
<th>
{lang_visitsamount}
</th>
<th>
{lang_referrer}
</th>
<th>
{lang_host}
</th>
<th>
{lang_configuration}
</th>
</tr>
</thead>
<tbody>
{rows}</tbody>
</table>
{links}{visits-legend}[/end]

[start:visits-row]<tr>
<td>
{simpletype}
</td>
<td align="center">
<!--start:details-{id}--><a href="{path}visits/visit/{id}/1{suffix}" title="{lang_details}" tabindex="{tabindex}">
<!--end:details-{id}--><strong><em>{id}</em></strong>
<!--start:details-{id}--></a>
<!--end:details-{id}--></td>
<td align="center">
{first}
</td>
<td align="center">
{last}
</td>
<td align="center">
{visits}</td>
<td align="center">
{referrer}
</td>
<td align="center">
{host}
</td>
<td title="{useragent}" align="center">
{configuration}</td>
</tr>
[/end]

[start:visits-legend]<h4>{lang_legend}:</h4>
<p>
<small>{lang_yourvisits}: <strong>!</strong></small>
</p>
<p>
<small>{lang_onlinevisitors}: <strong>+</strong></small>
</p>
<p>
<small>{lang_returnsvisitors}: <strong>^</strong></small>
</p>
<p>
<small>{lang_robots}: <strong>$</strong></small>
</p>
[/end]

[start:visits-none]<tr>
<td colspan="7" align="center">
<strong>{lang_none}</strong>
</td>
</tr>
[/end]

[start:details]<table cellpadding="0" cellspacing="0" border="1" width="100%">
<thead>
<tr>
<th colspan="3">
{lang_visitedpages} ({visits})
</th>
<th>
{lang_referrer}
</th>
<th>
{lang_keywords}
</th>
<th>
{lang_host}
</th>
<th>
{lang_configuration}
</th>
</tr>
</thead>
<tbody>
<tr>
<th>
#
</th>
<th>
{lang_date}
</th>
<th>
{lang_site}
</th>
<td rowspan="{rowspan}" align="center">
{referrer}
</td>
<td rowspan="{rowspan}" align="center">
{keywords}
</td>
<td rowspan="{rowspan}" align="center">
{host}
</td>
<td rowspan="{rowspan}" title="{useragent}" align="center">
{configuration}</td>
</tr>
{rows}<tr>
<td colspan="3">
{links}
</td>
</tr>
</tbody>
</table>
<!--start:other-visits--><h3 align="center">
{lang_othervisits}
</h3>
<table cellpadding="0" cellspacing="0" border="1" width="100%">
<tr>
<th>
#
</th>
<th>
{lang_firstvisit}
</th>
<th>
{lang_lastvisit}
</th>
<th>
{lang_visitsamount}
</th>
</tr>
{othervisits}</tr>
</table>
<!--end:other-visits-->{visits-legend}[/end]

[start:details-row]<tr>
<td align="center">
<em>{num}.</em>
</td>
<td align="center">
{date}
</td>
<td title="{title}" align="center">
{link}
</td>
</tr>
[/end]

[start:other-visits-row]<tr>
<td align="center">
<!--start:details-{id}--><a href="{path}visits/visit/{id}/1" title="{lang_details}" tabindex="{tabindex}">
<!--end:details-{id}--><strong><em>{id}</em></strong>
<!--start:details-{id}--></a>
<!--end:details-{id}--></td>
<td align="center">
{first}
</td>
<td align="center">
{last}
</td>
<td align="center">
{amount}
</td>
</tr>
[/end]