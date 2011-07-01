#!/bin/sh

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

mkdir -p export/estats
cd export/estats

exportDirectory=`pwd`

cd $sourceDirectory

echo 'Exporting files...'

svn export --force ./ $exportDirectory > /dev/null

checkErrors

if [ $# -gt 1 ]
then
	echo 'Appending additional files...'

	cp -R $2 $exportDirectory
fi

cd $exportDirectory
cd ../

echo 'Creating ZIP package...'

zip -r estats.zip estats > /dev/null

checkErrors

echo 'Creating TAR.BZ2 package...'

tar cjf estats.tar.bz2 estats > /dev/null

checkErrors

echo 'Cleaning up...'

rm -r $exportDirectory

echo 'Packages created successfully.'
