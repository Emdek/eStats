[start:geolocation]{dateform}<!--start:singlecountry--><table cellpadding="0" cellspacing="0" id="geolocation">
<tr>
<td>
{cities}</td>
<td>
{regions}</td>
</tr>
</table>
<!--end:singlecountry--><!--start:!singlecountry--><table cellpadding="0" cellspacing="0" id="geolocation">
<tr>
<td rowspan="2">
{cities}</td>
<td>
{countries}</td>
</tr>
<tr>
<td>
{continents}</td>
</tr>
</table>
<!--end:!singlecountry--><!--start:map--><h3>{lang_map}</h3>
<div>
<img src="{path}image{suffix}{separator}id=geolocation-{mapid}" alt="" usemap="#geolocation_map" id="geolocationmap" />
<map id="geolocation_map" name="geolocation_map">
{maphrefs}</map>
<div id="geolocationtooltips">
{maptooltips}</div>
</div>
<div id="mapinfo">
{lang_author}:
<a href="{maplink}" tabindex="{maptabindex}"><strong>{mapauthor}</strong></a>
({maptime})
</div>
<!--end:map-->[/end]