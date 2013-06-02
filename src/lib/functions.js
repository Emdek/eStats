function setCookie(name, value)
{
	var time = new Date();
	time.setTime(time.getTime() + 31536000);

	document.cookie = (name + '=' + escape(value) + '; expires=' + (value ? time.toGMTString() : -1) + '; path=' + escape('/'));
}

function levelsShowHide(identifier)
{
	if (!document.getElementById('levels_switch_' + identifier))
	{
		return;
	}

	var elements = document.getElementById('chart_' + identifier).getElementsByTagName('hr');

	for (i = 0; i < elements.length; ++i)
	{
		elements[i].style.display = (document.getElementById('levels_switch_' + identifier).checked ? 'block' : 'none');
	}

	setCookie(('estats_time_levels_chart_' + identifier), !document.getElementById('levels_switch_' + identifier).checked);
}

function highlightBars(identifier, number, type, mode)
{
	var levels;

	if (type == 'sum')
	{
		levels = document.getElementById('chart_' + identifier).getElementsByTagName('hr');
	}
	else
	{
		levels = new Array(document.getElementById('level_' + identifier + '_' + type + '_' + number));
	}

	for (i = 0; i < levels.length; ++i)
	{
		if (!levels[i])
		{
			continue;
		}

		if (type == 'sum')
		{
			var string = ('_' + number);

			if (levels[i].id.substr(levels[i].id.length - string.length) != string)
			{
				continue;
			}
		}

		if (mode)
		{
			levels[i].className += ' active';
		}
		else
		{
			levels[i].className = levels[i].className.replace(' active', '');
		}
	}

	if (type == 'average')
	{
		return;
	}

	var bars = document.getElementById('chart_' + identifier).getElementsByTagName('div');

	for (i = 0; i < bars.length; ++i)
	{
		if (!mode)
		{
			bars[i].className = bars[i].className.replace(' active', '');

			continue;
		}

		if (!bars[i].id || (type != 'sum' && bars[i].className != type))
		{
			continue;
		}

		var string = ('_' + number);

		if (bars[i].id.substr (bars[i].id.length - string.length) != string && number > -1)
		{
			continue;
		}

		bars[i].className += ' active';
	}
}

function expandRow(identifier, container)
{
	container.style.display = 'block';

	document.getElementById(identifier).className = 'expanded';
	document.getElementById(identifier).style.display = 'block';
}

function queryRows(GroupID, SID, Query, mode)
{
	var Paragraphs = document.getElementById(mode ? GroupID : SID).getElementsByTagName('p');

	for (k = 0; k < Paragraphs.length; ++k)
	{
		var ParagraphID = Paragraphs[k].id;

		if (document.getElementById('ShowModified').checked && Paragraphs[k].className != 'changed')
		{
			continue;
		}

		var Description = document.getElementById(ParagraphID).getElementsByTagName('dfn');

		if (Description.length)
		{
			SearchInString = (' ' + Description[0].innerHTML);
		}
		else
		{
			SearchInString = ' ';
		}

		var Field = document.getElementById('F' + ParagraphID.substr(1));

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

			if (!mode)
			{
				expandRow(SID, Paragraphs[k]);
			}

			expandRow(GroupID, Paragraphs[k]);
		}
	}
}

function search(query)
{
	var query = query.toLowerCase();
	var Fieldsets = document.getElementById('advanced').getElementsByTagName('Fieldset');
	var Rows = document.getElementById('advanced').getElementsByTagName('p');

	document.getElementById('ResultsAmount').innerHTML = 0;

	if (query != '')
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

		return;
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

				queryRows(GroupID, SID, query, 0);
				queryRows(GroupID, SID, query, 1);
			}
		}
	}
}

function checkDefault(field, value)
{
	var changed = false;

	if (document.getElementById('F_' + field).tagName == 'INPUT' && document.getElementById('F_' + field).getAttribute('type') == 'checkbox')
	{
		changed = (document.getElementById('F_' + field).checked != value);
	}
	else
	{
		changed = (document.getElementById('F_' + field).value != value);
	}

	document.getElementById('P_' + field).className = (changed ? 'changed' : '');
	document.getElementById('P_' + field).title = (changed ? ChangedValueString : '');
}

function setDefault(field, value)
{
	if (document.getElementById('F_' + field).tagName == 'INPUT' && document.getElementById('F_' + field).getAttribute('type') == 'checkbox')
	{
		document.getElementById('F_' + field).checked = (value == '1');
	}
	else
	{
		document.getElementById('F_' + field).value = value;
	}

	checkDefault(field, value);
}

function changeClassName(identifier)
{
	document.getElementById(identifier).className = ((document.getElementById(identifier).className == 'expanded') ? 'collapsed' : 'expanded');
	document.getElementById('ShowAll').checked = 0;
}

function resetAll()
{
	var elements = document.getElementById('advanced').getElementsByTagName('input');

	for (i = 0; i < elements.length; ++i)
	{
		if (elements[i].type == 'button')
		{
			eval(elements[i].getAttribute('onclick'));
		}
	}
}

function collapseAll()
{
	var elements = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (i = 0; i < elements.length; ++i)
	{
		elements[i].className = 'collapsed';
	}
}

function expandAll()
{
	var elements = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (i = 0; i < elements.length; ++i)
	{
		elements[i].className = (Expanded ? 'collapsed' : 'expanded');
		elements[i].style.display = 'block';
	}

	elements = document.getElementById('advanced').getElementsByTagName('p');

	for (i = 0; i < elements.length; ++i)
	{
		elements[i].style.display = 'block';
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

	var Fieldsets = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (i = 0, c = Fieldsets.length; i < c; ++i)
	{
		var modified = false;
		var Paragraphs = Fieldsets[i].getElementsByTagName('p');

		for (j = 0; j < Paragraphs.length; ++j)
		{
			if (Paragraphs[j].className == 'changed')
			{
				++document.getElementById('ResultsAmount').innerHTML;

				Paragraphs[j].style.display = 'block';

				modified = true;
			}
			else
			{
				Paragraphs[j].style.display = 'none';
			}
		}

		if (modified)
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
