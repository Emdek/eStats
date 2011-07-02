#!/usr/bin/env python
import csv, sqlite3

exists = True

try:
	database = open('geoip.sqlite')
	database.close()
except:
	exists = False

connection = sqlite3.connect('geoip.sqlite')
cursor = connection.cursor()

if exists == False:
	try:
		cursor.execute('CREATE TABLE "blocks" ("ipstart" INTEGER, "ipend" INTEGER, "location" INTEGER, PRIMARY KEY ("ipstart", "ipend", "location"))')
		cursor.execute('CREATE TABLE "locations" ("location" INTEGER PRIMARY KEY, "city" TEXT, "region" TEXT, "country_code" TEXT, "latitude" FLOAT, "longitude" FLOAT)')
		connection.commit()
	except:
		print 'Can not create database structure!'

		exit()

	print 'Database structure created...'
else:
	print 'Using existing database...'

try:
	data = open('GeoLiteCity-Blocks.csv', 'rb')
except:
	print 'Can not open file "GeoLiteCity-Blocks.csv"!'

	exit()

reader = csv.reader(data)
i = 0

for line in reader:
	i += 1

	if i < 3:
		continue

	try:
		cursor.execute('INSERT INTO "blocks" VALUES (?, ?, ?)', (int(line[0]), int(line[1]), int(line[2])))
	except:
		print 'An error occured during insertion of row ',i,' into database!'

print i,' rows inserted into table "blocks"...'

data.close()

try:
	data = open('GeoLiteCity-Location.csv', 'rb')
except:
	print 'Can not open file "GeoLiteCity-Location.csv"!'

	exit()

reader = csv.reader(data)
i = 0

for line in reader:
	i += 1

	if i < 3:
		continue

	try:
		cursor.execute('INSERT INTO "locations" VALUES (?, ?, ?, ?, ?, ?)', (int(line[0]), str(line[3]), str(line[2]), str(line[1]), float(line[5]), float(line[6])))
	except:
		print 'An error occured during insertion of row ',i,' into database!'

print i,' rows inserted into table "locations"...'

data.close()

try:
	connection.commit()

	print 'Database saved successfully.'
except:
	print 'An error occured during saving database!'

print ''
print 'Copy generated file (geoip.sqlite) into Your $DataDir directory as geoip_$DBID.sqlite (substitute variable names with their values).'