[start:time]<form action="{selfpath}" method="post">
<p>
<label for="year">{lang_showdatafor}</label>:<br>
{dateprevious}{selectday}{selectmonth}{selectyear}{datenext}<input type="submit" value="{lang_show}">
</p>
<p>
<label for="TimeView">{lang_chartsview}</label>:<br>
<select name="TimeView[]" multiple="multiple" size="3" id="TimeView">
{selectview}</select>
</p>
</form>
{24hours}{month}{year}{years}{hours}{weekdays}[/end]