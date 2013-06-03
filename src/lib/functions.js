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

	for (var i = 0; i < elements.length; ++i)
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

	for (var i = 0; i < levels.length; ++i)
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

	for (var i = 0; i < bars.length; ++i)
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

function queryRows(groupIdentifier, sectionIdentifier, query, mode)
{
	var elements = document.getElementById(mode ? groupIdentifier : sectionIdentifier).getElementsByTagName('p');

	for (var i = 0; i < elements.length; ++i)
	{
		var identifier = elements[i].id;

		if (document.getElementById('ShowModified').checked && elements[i].className != 'changed')
		{
			continue;
		}

		var description = document.getElementById(identifier).getElementsByTagName('dfn');
		var searchInString = ' ';

		if (description.length > 0)
		{
			searchInString = (' ' + description[0].innerHTML);
		}

		var field = document.getElementById('F' + identifier.substr(1));

		if (field.tagName == 'TEXTAREA' || (field.tagName == 'INPUT' && field.getAttribute('type') == ''))
		{
			searchInString += field.value + ' ';
		}

		searchInString += (identifier.substr(2) + ' ');
		searchInString = searchInString.toLowerCase();

		if (searchInString.split(query).length > 1)
		{
			if (elements[i].style.display != 'block')
			{
				++document.getElementById('ResultsAmount').innerHTML;
			}

			if (!mode)
			{
				expandRow(sectionIdentifier, elements[i]);
			}

			expandRow(groupIdentifier, elements[i]);
		}
	}
}

function search(query)
{
	var query = query.toLowerCase();
	var fieldsets = document.getElementById('advanced').getElementsByTagName('Fieldset');
	var rows = document.getElementById('advanced').getElementsByTagName('p');

	document.getElementById('ResultsAmount').innerHTML = 0;

	if (query != '')
	{
		for (var i = 0; i < fieldsets.length; ++i)
		{
			fieldsets[i].style.display = 'none';
		}

		for (var i = 0; i < rows.length; ++i)
		{
			rows[i].style.display = 'none';
		}
	}
	else
	{
		for (var i = 0; i < fieldsets.length; ++i)
		{
			fieldsets[i].className = 'collapsed';
			fieldsets[i].style.display = 'block';
		}

		for (var i = 0; i < rows.length; ++i)
		{
			rows[i].style.display = 'block';
		}

		document.getElementById('ResultsAmount').innerHTML = ResultsAmount;

		return;
	}

	for (var i = 0; i < fieldsets.length; i++)
	{
		if (fieldsets[i].id.split('.').length == 1)
		{
			var groupIdentifier = fieldsets[i].id;
			var groups = document.getElementById(groupIdentifier).getElementsByTagName('fieldset');

			for (var j = 0; j < groups.length; ++j)
			{
				var sectionIdentifier = groups[j].id;

				queryRows(groupIdentifier, sectionIdentifier, query, 0);
				queryRows(groupIdentifier, sectionIdentifier, query, 1);
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

	for (var i = 0; i < elements.length; ++i)
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

	for (var i = 0; i < elements.length; ++i)
	{
		elements[i].className = 'collapsed';
	}
}

function expandAll()
{
	var elements = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (var i = 0; i < elements.length; ++i)
	{
		elements[i].className = (expanded ? 'collapsed' : 'expanded');
		elements[i].style.display = 'block';
	}

	elements = document.getElementById('advanced').getElementsByTagName('p');

	for (var i = 0; i < elements.length; ++i)
	{
		elements[i].style.display = 'block';
	}

	document.getElementById('ResultsAmount').innerHTML = ResultsAmount;
}

function showAll()
{
	expanded = !document.getElementById('ShowAll').checked;

	document.getElementById('ShowModified').checked = 0;
	document.getElementById('AdvancedSearch').style.color = 'gray';
	document.getElementById('AdvancedSearch').value = searchString;

	expandAll();
}

function showModified()
{
	var searchValue = document.getElementById('AdvancedSearch').value;

	expanded = !document.getElementById('ShowModified').checked;

	if (expanded)
	{
		expandAll();

		if (searchValue != searchString)
		{
			search(searchValue);

			document.getElementById('AdvancedSearch').value = searchValue;
		}

		return;
	}

	document.getElementById('ShowAll').checked = 0;
	document.getElementById('ResultsAmount').innerHTML = 0;

	var fieldsets = document.getElementById('advanced').getElementsByTagName('fieldset');

	for (var i = 0, c = fieldsets.length; i < c; ++i)
	{
		var modified = false;
		var elements = fieldsets[i].getElementsByTagName('p');

		for (var j = 0; j < elements.length; ++j)
		{
			if (elements[j].className == 'changed')
			{
				++document.getElementById('ResultsAmount').innerHTML;

				elements[j].style.display = 'block';

				modified = true;
			}
			else
			{
				elements[j].style.display = 'none';
			}
		}

		if (modified)
		{
			fieldsets[i].className = 'expanded';
			fieldsets[i].style.display = 'block';
		}
		else
		{
			fieldsets[i].className = 'collapsed';
			fieldsets[i].style.display = 'none';
		}
	}

	if (searchValue != searchString)
	{
		search(searchValue);

		document.getElementById('AdvancedSearch').value = searchValue;
	}
}
