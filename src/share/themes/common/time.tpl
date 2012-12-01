[start:time]<form action="{selfpath}" method="post" id="dateform">
<p>
<label>
<span>
{selecthour}{selectday}{selectmonth}{selectyear}{selectmap}<input type="submit" value="{lang_show}" tabindex="{dateformindex}"  />{dateprevious}{datenext}
</span>
{lang_showdatafor}:
</label>
</p>
<p>
<label>
<span>
<select name="TimeView[]" multiple="multiple" size="3" tabindex="{selectviewindex}">
{selectview}</select>
</span>
{lang_chartsview}:
</label>
</p>
<p>
<label>
<span>
<input type="checkbox" name="TimeCompare" value="1"{checkboxcomparechecked} tabindex="{checkboxcompareindex}" />
</span>
{lang_compareprevious}:
</label>
</p>
</form>
{24hours}{month}{year}{years}{hours}{weekdays}[/end]