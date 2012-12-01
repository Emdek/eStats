[start:visits]{cacheinformation}<form action="{selfpath}" method="post">
<p>
<label>
<span>
<input type="hidden" name="ChangeRobots" value="1" />
<input type="checkbox" name="ShowRobots" value="1" {robotscheckbox} />
<input type="submit" value="{lang_change}" tabindex="{robotsformindex}" />
</span>
{lang_showrobots}:
</label>
</p>
</form>
<table cellpadding="2" cellspacing="0" id="visits">
<thead>
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

[start:visits-row]<tr class="{class}">
<td>
<!--start:details-{id}--><a href="{path}visits/visit/{id}/1{suffix}" title="{lang_details}" tabindex="{tabindex}">
<!--end:details-{id}--><strong><em>{id}</em></strong>
<!--start:details-{id}--></a>
<!--end:details-{id}--></td>
<td>
{first}
</td>
<td>
{last}
</td>
<td>
{visits}</td>
<td>
{referrer}
</td>
<td>
{host}
</td>
<td title="{useragent}">
{configuration}</td>
</tr>
[/end]

[start:visits-legend]<h4>{lang_legend}:</h4>
<p>
<small class="user">{lang_yourvisits}</small>
</p>
<p>
<small class="online">{lang_onlinevisitors}</small>
</p>
<p>
<small class="returns">{lang_returnsvisitors}</small>
</p>
<p>
<small class="robot">{lang_robots}</small>
</p>
[/end]

[start:visits-none]<tr>
<td colspan="7">
<strong>{lang_none}</strong>
</td>
</tr>
[/end]

[start:details]<table cellpadding="0" cellspacing="0" id="details" class="{class}">
<thead>
<tr class="detailsheader">
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
<tr class="detailsheader">
<th>
#
</th>
<th>
{lang_date}
</th>
<th>
{lang_site}
</th>
<td rowspan="{rowspan}">
{referrer}
</td>
<td rowspan="{rowspan}">
{keywords}
</td>
<td rowspan="{rowspan}">
{host}
</td>
<td rowspan="{rowspan}" title="{useragent}">
{configuration}</td>
</tr>
{rows}<tr>
<td colspan="3">
{links}
</td>
</tr>
</tbody>
</table>
<!--start:other-visits--><h3>
{lang_othervisits}
</h3>
<table cellpadding="0" cellspacing="0" id="othervisits">
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
<td>
<em>{num}.</em>
</td>
<td>
{date}
</td>
<td title="{title}">
{link}
</td>
</tr>
[/end]

[start:other-visits-row]<tr>
<td>
<!--start:details-{id}--><a href="{path}visits/visit/{id}/1" title="{lang_details}" tabindex="{tabindex}">
<!--end:details-{id}--><strong><em>{id}</em></strong>
<!--start:details-{id}--></a>
<!--end:details-{id}--></td>
<td>
{first}
</td>
<td>
{last}
</td>
<td>
{amount}
</td>
</tr>
[/end]