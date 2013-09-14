#!/usr/bin/env python

import sys, string

if len(sys.argv) < 2:
	print 'You must specify path to source PO file!'

	exit()

try:
	data = open(sys.argv[1], 'rb')
except:
	print 'Can not open file "' + sys.argv[1] + '"!'

	exit()

i = 0
ignore = True
source = False
message = ''
translation = ''
messages = {}

for line in data:
	i += 1

	if i < 15:
		continue

	line = line.strip()

	if line[:8] == '#, fuzzy':
		ignore = True
	elif line[:1] == '#':
		continue
	elif line[:5] == 'msgid':
		if len(message) > 0:
			messages[string.replace(string.replace(message, '\\"', '"'), '\\n', '\n')] = string.replace(string.replace(translation, '\\"', '"'), '\\n', '\n')

		if line != 'msgid ""' and ignore == False:
			message = line[7:-1]
		else:
			message = ''

		translation = ''
		source = not ignore
	elif line[:6] == 'msgstr':
		if line != 'msgstr ""':
			translation = line[8:-1]

		ignore = False
		source = False
	elif line[:1] == '"':
		if source == True and ignore == False:
			message += line[1:-1]
		elif source == False and len(message) > 0:
			translation += line[1:-1]

buffer = 'a:' + str(len(messages)) + ':{'

keys = messages.keys()
keys.sort()

for key in keys:
	buffer += 's:' + str(len(key)) + ':"' + key + '";s:' + str(len(messages[key])) + ':"' + messages[key] + '";'

buffer += '}'

print buffer
