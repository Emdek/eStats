#!/bin/bash

if [ $# -lt 1 ]
then
	echo 'You must specify path to sources directory!'

	exit
fi

if [ ! -d $1 ]
then
	echo 'Sources directory does not exists!'

	exit
fi

function checkErrors
{
	if [ $? -ne 0 ]
	then
		echo 'Aborting...'

		exit
	fi
}

sourceDirectory=`readlink -f $1`

mkdir "export"
cd "export"

exportDirectory=`pwd`

echo 'Copying files...'

cp -r $sourceDirectory "$exportDirectory/estats" > /dev/null

checkErrors

if [ $# -gt 1 ]
then
	echo 'Appending additional files...'

	cp -r $2 "$exportDirectory/estats"
fi

cd $exportDirectory

echo 'Creating ZIP package...'

zip -r estats.zip estats > /dev/null

checkErrors

echo 'Creating TAR.BZ2 package...'

tar cjf estats.tar.bz2 estats > /dev/null

checkErrors

echo 'Cleaning up...'

rm -r "$exportDirectory/estats"

echo 'Packages created successfully.'
