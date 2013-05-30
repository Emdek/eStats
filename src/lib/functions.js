function setCookie(Name, Value)
{
	var Time = new Date();
	Time.setTime(Time.getTime() + 31536000);

	document.cookie = (Name + '=' + escape(Value) + '; expires=' + (Value ? Time.toGMTString() : -1) + '; path=' + escape('/'));
}

function levelsShowHide(ID)
{
	if (!document.getElementById('levels_switch_' + ID))
	{
		return;
	}

	HRs = document.getElementById('chart_' + ID).getElementsByTagName('hr');

	for (i = 0, c = HRs.length; i < c; ++i)
	{
		HRs[i].style.display = (document.getElementById('levels_switch_' + ID).checked ? 'block' : 'none');
	}

	setCookie(('estats_time_levels_chart_' + ID), !document.getElementById('levels_switch_' + ID).checked);
}

function highlightBars(ID, Number, Type, Mode)
{
	if (Type == 'sum')
	{
		Levels = document.getElementById('chart_' + ID).getElementsByTagName('hr');
	}

	else
	{
		Levels = new Array(document.getElementById('level_' + ID + '_' + Type + '_' + Number));
	}

	for (i = 0; i < Levels.length; ++i)
	{
		if (!Levels[i])
		{
			continue;
		}

		if (Type == 'sum')
		{
			String = '_' + Number;

			if (Levels[i].id.substr(Levels[i].id.length - String.length) != String)
			{
				continue;
			}
		}

		if (Mode)
		{
			Levels[i].className += ' active';
		}
		else
		{
			Levels[i].className = Levels[i].className.replace(' active', '');
		}
	}

	if (Type == 'average')
	{
		return;
	}

	Bars = document.getElementById('chart_' + ID).getElementsByTagName('div');

	for (i = 0; i < Bars.length; ++i)
	{
		if (!Mode)
		{
			Bars[i].className = Bars[i].className.replace(' active', '');

			continue;
		}

		if (!Bars[i].id || (Type != 'sum' && Bars[i].className != Type))
		{
			continue;
		}

		String = ('_' + Number);

		if (Bars[i].id.substr (Bars[i].id.length - String.length) != String && Number > -1)
		{
			continue;
		}

		Bars[i].className += ' active';
	}
}

function expandRow(ID, Container)
{
	Container.style.display = 'block';

	document.getElementById(ID).className = 'expanded';
	document.getElementById(ID).style.display = 'block';
}

function queryRows(GroupID, SID, Query, Mode)
{
	Paragraphs = document.getElementById(Mode ? GroupID : SID).getElementsByTagName('p');

	for (k = 0; k < Paragraphs.length; ++k)
	{
		ParagraphID = Paragraphs[k].id;

		if (document.getElementById('ShowModified').checked && Paragraphs[k].className != 'changed')
		{
			continue;
		}

		Description = document.getElementById(ParagraphID).getElementsByTagName('dfn');

		if (Description.length)
		{
			SearchInString = (' ' + Description[0].innerHTML);
		}
		else
		{
			SearchInString = ' ';
		}

		Field = document.getElementById('F' + ParagraphID.substr(1));

		if (Field.tagName == 'TEXTAREA' || (Field.tagName == 'INPUT' && Field.getAttribute('type') == ''))
		{
			SearchInString += Field.value + ' ';
		}

		SearchInString += (ParagraphID.substr(2) + ' ');
		SearchInString = SearchInString.toLowerCase();

		if (SearchInString.split(Query).length > 1)
		{
			if (Paragraphs[k].style.display != 'block')
			{
				++document.getElementById('ResultsAmount').innerHTML;
			}

			if (!Mode)
			{
				expandRow(SID, Paragraphs[k]);
			}

			expandRow(GroupID, Paragraphs[k]);
		}
	}
}

function search(Query)
{
	Query = Query.toLowerCase();
	Fieldsets = document.getElementById('advanced').getElementsByTagName('Fieldset');
	Rows = document.getElementById('advanced').getElementsByTagName('p');

	document.getElementById('ResultsAmount').innerHTML = 0;

	if (Query != '')
	{
		for (i = 0; i < Fieldsets.length; ++i)
		{
			Fieldsets[i].style.display = 'none';
		}

		for (i = 0; i < Rows.length; ++i)
		{
			Rows[i].style.display = 'none';
		}
	}
	else
	{
		for (i = 0; i < Fieldsets.length; ++i)
		{
			Fieldsets[i].className = 'collapsed';
			Fieldsets[i].style.display = 'block';
		}

		for (i = 0; i < Rows.length; ++i)
		{
			Rows[i].style.display = 'block';
		}

		document.getElementById('ResultsAmount').innerHTML = ResultsAmount;

		return (0);
	}

	for (i = 0; i < Fieldsets.length; i++)
	{
		if (Fieldsets[i].id.split('.').length == 1)
		{
			GroupID = Fieldsets[i].id;
			Groups = document.getElementById(GroupID).getElementsByTagName('fieldset');

			for (j = 0; j < Groups.length; ++j)
			{
				SID = Groups[j].id;

				queryRows(GroupID, SID, Query, 0);
				queryRows(GroupID, SID, Query, 1);
			}
		}
	}
}

function checkDefault(Field, Value)
{
	if (document.getElementById('F_' + Field).tagName == 'INPUT' && document.getElementById('F_' + Field).getAttribute('type') == 'checkbox')
	{
		Change = (document.getElementById('F_' + Field).checked != Value);
	}
	else
	{
		Change = (document.getElementById('F_' + Field).value != Value);
	}

	document.getElementById('P_' + Field).className = (Change ? 'changed' : '');
	document.getElementById('P_' + Field).title = (Change ? ChangedValueString : '');
}

function setDefault(Field, Value)
{
	if (document.getElementById('F_' + Field).tagName == 'INPUT' && document.getElementById('F_' + Field).getAttribute('type') == 'checkbox')
	{
		document.getElementById('F_' + Field).checked = (Value == '1');
	}
	else
	{
		document.getElementById('F_' + Field).value = Value;
	}

	checkDefault(Field, Value);
}

function changeClassName(ID)
{
	document.getElementById(ID).className = ((document.getElementById(ID).className == 'expanded') ? 'collapsed' : 'expanded');
	document.getElementById('ShowAll').checked = 0;
}

function resetAll()
{
	Inputs = document.getElementById('advanced').getElementsByTagName('input');

	for (i = 0; i < Inputs.length; ++i)
	{
		if (Inputs[i].type == 'button')
		{
			eval(Inputs[i].getAttribute('onclick'));
		}
	}
}

function collapseAll()
{
	Fieldsets = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (i = 0; i < Fieldsets.length; ++i)
	{
		Fieldsets[i].className = 'collapsed';
	}
}

function expandAll()
{
	Fieldsets = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (i = 0; i < Fieldsets.length; ++i)
	{
		Fieldsets[i].className = (Expanded ? 'collapsed' : 'expanded');
		Fieldsets[i].style.display = 'block';
	}

	Paragraphs = document.getElementById('advanced').getElementsByTagName('p');

	for (i = 0; i < Paragraphs.length; ++i)
	{
		Paragraphs[i].style.display = 'block';
	}

	document.getElementById('ResultsAmount').innerHTML = ResultsAmount;
}

function showAll()
{
	Expanded = !document.getElementById('ShowAll').checked;

	document.getElementById('ShowModified').checked = 0;
	document.getElementById('AdvancedSearch').style.color = 'gray';
	document.getElementById('AdvancedSearch').value = SearchString;

	expandAll();
}

function showModified()
{
	Expanded = !document.getElementById('ShowModified').checked;
	SearchValue = document.getElementById('AdvancedSearch').value;

	if (Expanded)
	{
		expandAll();

		if (SearchValue != SearchString)
		{
			search(SearchValue);

			document.getElementById('AdvancedSearch').value = SearchValue;
		}

		return;
	}

	document.getElementById('ShowAll').checked = 0;
	document.getElementById('ResultsAmount').innerHTML = 0;

	Fieldsets = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (i = 0, c = Fieldsets.length; i < c; ++i)
	{
		Modified = 0;
		Paragraphs = Fieldsets[i].getElementsByTagName('p');

		for (j = 0; j < Paragraphs.length; ++j)
		{
			if (Paragraphs[j].className == 'changed')
			{
				++document.getElementById('ResultsAmount').innerHTML;
				Paragraphs[j].style.display = 'block';
				Modified = 1;
			}
			else
			{
				Paragraphs[j].style.display = 'none';
			}
		}

		if (Modified)
		{
			Fieldsets[i].className = 'expanded';
			Fieldsets[i].style.display = 'block';
		}
		else
		{
			Fieldsets[i].className = 'collapsed';
			Fieldsets[i].style.display = 'none';
		}
	}

	if (SearchValue != SearchString)
	{
		search(SearchValue);
		document.getElementById('AdvancedSearch').value = SearchValue;
	}
}
