#!/usr/bin/env python

import os, re, sys
from datetime import datetime

INIFiles = ['share/data/continents.ini', 'share/data/countries.ini', 'share/data/events.ini', 'share/data/languages.ini']

if len(sys.argv) < 2:
	print 'You must specify path to sources directory!'

	exit()

messages = {}

def addMessage(message, location):
	global messages

	if message in messages:
		messages[message].append(location)
	else:
		messages[message] = [location]

def processLine(line):
	return re.sub('(?<!\\\)"', '\\"', re.sub('\\\\\'', '\'', line))

expression = 'EstatsLocale::translate\(\'(.+?)\'\)'
buffer = ''
start = 0

for root, dirs, files in os.walk(sys.argv[1]):
	for name in files:
		if name[-4:] == '.php':
			try:
				file = open(os.path.join(root, name), 'rb')
			except:
				continue

			i = 0

			for line in file:
				i += 1

				if len(buffer) > 0:
					result = re.search('(.+?)\'(?:, .+?)?\)', line)

					if result != None:
						addMessage(buffer + '"' + processLine(result.group(1)), root[len(sys.argv[1]):] + ('' if sys.argv[1] == root else '/') + name + ':' + str(start))

						buffer = ''
					else:
						buffer += '"' + processLine(line.strip('\r\n')) + '\\n"\n'

						continue

				results = re.findall(expression, line)

				if len(results) > 0:
					for message in results:
						addMessage(processLine(message), root[len(sys.argv[1]):] + ('' if sys.argv[1] == root else '/') + name + ':' + str(i))

					line = re.sub(expression, '', line)

				result = re.search('EstatsLocale::translate\(\'(.+?)$', line)

				if result != None:
					buffer = '"\n"' + processLine(result.group(1)) + '\\n"\n'
					start = i

for INI in INIFiles:
	i = 0

	try:
		file = open(os.path.join(sys.argv[1], INI), 'rb')
	except:
		continue

	for line in file:
		i += 1

		line = line.strip()
		parts = line.split('=', 1)

		if len(parts) > 1:
			addMessage(parts[1].strip()[1:-1], INI + ':' + str(i))

print '#, fuzzy\nmsgid ""\nmsgstr ""\n"Project-Id-Version: estats\\n"\n"POT-Creation-Date: ' + datetime.now().strftime('%Y-%m-%d %H:%M') + '+0100\\n"\n"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"\n"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"\n"Language-Team: LANGUAGE\\n"\n"MIME-Version: 1.0\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Content-Transfer-Encoding: 8bit\\n"\n'

keys = messages.keys()
keys.sort()

for key in keys:
	for location in messages[key]:
		print '#: ' + location

	print 'msgid "' + key + '"\nmsgstr ""\n'
