[start:visits]<form action="{selfpath}" method="post">
<p>
<label>
<span>
<input type="checkbox" name="ShowRobots" value="1" {robotscheckbox} />
<input type="submit" value="{lang_change}" tabindex="{robotsformindex}" />
<input type="hidden" name="ChangeRobots" value="1" />
</span>
{lang_showrobots}:
</label>
</p>
</form>
{cacheinformation}<div class="group">
<table cellpadding="2" cellspacing="0" id="visits">
<tr>
<th class="corner_left_top">
<img src="{datapath}/share/icons/pages/visits.png" alt="" class="icon" />
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
{lang_visitstime}
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
{rows}</table>
<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
{links}{visits-legend}[/end]

[start:visits-row]<tr class="{class}">
<td colspan="2">
<p>
<!--start:details-{id}--><a href="{path}visits/visit/{id}/1{suffix}" title="{lang_details}" tabindex="{tabindex}">
<!--end:details-{id}--><strong><em>{id}</em></strong>
<!--start:details-{id}--></a>
<!--end:details-{id}--></p>
</td>
<td>
<p>
{first}
</p>
</td>
<td>
<p>
{last}
</p>
</td>
<td>
<p>
{visits}
</p>
</td>
<td>
<p>
{time}
</p>
</td>
<td>
<p>
{referrer}
</p>
</td>
<td>
<p>
{keywords}
</p>
</td>
<td>
<p>
{host}<!--start:administrator--><br />
{ip}<!--end:administrator-->
</p>
</td>
<td class="configuration" title="{useragent}">
<p>
{configuration}</p>
</td>
</tr>
[/end]

[start:visits-legend]<h4>
{lang_legend}:
</h4>
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
<td colspan="10">
<strong>{lang_none}</strong>
</td>
</tr>
[/end]

[start:details]<table cellpadding="0" cellspacing="0">
<tr>
<td>
<div class="group">
<table cellpadding="0" cellspacing="0" id="details">
<tr>
<th colspan="2" class="title">
<span class="corner_left_top">
<img src="{datapath}/share/icons/pages/visits.png" alt="" class="icon" />
</span>
{title}
</th>
</tr>
<tr>
<th>
<p>
{lang_firstvisit}:
</p>
</th>
<td>
<p>
{first}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_visitsamount}:
</p>
</th>
<td>
<p>
{visits}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_visitstime}:
</p>
</th>
<td>
<p>
{time}
</p>
</td>
</tr>
<!--start:administrator--><tr>
<th>
<p>
{lang_ip}:
</p>
</th>
<td>
<p>
{ip}
</p>
</td>
</tr>
<!--end:administrator--><tr>
<th>
<p>
{lang_host}:
</p>
</th>
<td>
<p>
{host}
</p>
</td>
</tr>
<!--start:!robot--><!--start:referrer--><tr>
<th>
<p>
{lang_referrer}:
</p>
</th>
<td>
<p>
{referrer}
</p>
</td>
</tr>
<!--end:referrer--><!--start:keywords--><tr>
<th>
<p>
{lang_keywords}:
</p>
</th>
<td>
<p>
{keywords}
</p>
</td>
</tr>
<!--end:keywords--><tr>
<th>
<p>
{lang_language}:
</p>
</th>
<td>
<p>
{language_icon}{language}
</p>
</td>
</tr>
<!--end:!robot--><!--start:geoip--><!--start:location--><tr>
<th>
<p>
{lang_location}:
</p>
</th>
<td>
<p>
{country_icon}{location}
</p>
</td>
</tr>
<!--end:location--><!--end:geoip--><!--start:!robot--><tr>
<th>
<p>
{lang_os}:
</p>
</th>
<td>
<p>
{operatingsystem_icon}{operatingsystem}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_browser}:
</p>
</th>
<td>
<p>
{browser_icon}{browser}
</p>
</td>
</tr>
<!--start:technical--><tr>
<th>
<p>
{lang_screen}:
</p>
</th>
<td>
<p>
{screen_icon}{screen}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_flash}:
</p>
</th>
<td>
<p>
{flash}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_java}:
</p>
</th>
<td>
<p>
{java}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_javascript}:
</p>
</th>
<td>
<p>
{javascript}
</p>
</td>
</tr>
<tr>
<th>
<p>
{lang_cookies}:
</p>
</th>
<td>
<p>
{cookies}
</p>
</td>
</tr>
<!--end:technical--><!--end:!robot--><!--start:robot--><tr>
<th>
<p>
{lang_robot}:
</p>
</th>
<td>
<p>
{robot_icon}{robot}
</p>
</td>
</tr>
<!--end:robot--></table>
<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
</td>
<td>
<div class="group">
<table cellpadding="0" cellspacing="0" id="visitedpages">
<tr>
<th>
<span class="icon_wrapper">
<img src="{datapath}/share/icons/pages/sites.png" alt="" class="icon" />
</span>
</th>
<th>
{lang_visitedpages}
</th>
<th>
&nbsp;
</th>
</tr>
{rows}<tr>
<td colspan="3">
{links}
</td>
</tr>
</table>
<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
</td>
</tr>
</table>
<!--start:other-visits--><h3>
<span class="header_left">
<img src="{datapath}/share/icons/pages/visits.png" alt="" class="icon" />
</span>
<span class="header_right"></span>
{lang_othervisits}
</h3>
<div class="group">
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
<div class="border_bottom">
<span class="corner_left"></span>
<span class="corner_right"></span>
</div>
</div>
<!--end:other-visits-->[/end]

[start:details-row]<tr>
<td>
<p>
<em>{num}.</em>
</p>
</td>
<td>
<p>
{date}
</p>
</td>
<td title="{title}">
<p>
{link}
</p>
</td>
</tr>
[/end]

[start:other-visits-row]<tr>
<td>
<p>
<!--start:details-{id}--><a href="{path}visits/visit/{id}/1" title="{lang_details}" tabindex="{tabindex}">
<!--end:details-{id}--><strong><em>{id}</em></strong>
<!--start:details-{id}--></a>
<!--end:details-{id}--></p>
</td>
<td>
<p>
{first}
</p>
</td>
<td>
<p>
{last}
</p>
</td>
<td>
<p>
{amount}
</p>
</td>
</tr>
[/end]