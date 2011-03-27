#!/usr/bin/env python

import os, sys, string

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
buffer = '<?php\nerror_reporting(0);\n\n$Array = array(\n'

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
			buffer += '\t"' + message + '" => "' + translation + '",\n'

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

buffer = buffer[:-2]
buffer += '\n);\n\nksort($Array);\necho serialize($Array);\n?>'

try:
	output = open('tmp.php', 'w')
except:
	print 'Can not create temporary file!'

	exit()

output.write(buffer)
output.close()

print string.join(os.popen('php ./tmp.php').readlines())

os.remove('tmp.php')