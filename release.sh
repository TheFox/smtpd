#!/usr/bin/env bash

RM="rm -rf"
MKDIR="mkdir -p"
MV="mv -i"
CP="cp -rp"
COMPOSER_PREFER_SOURCE=--prefer-source
COMPOSER_PREFER_SOURCE=

SCRIPT_BASEDIR=$(dirname $0)
RELEASE_NAME=$($SCRIPT_BASEDIR/application.php info --name_lc)
RELEASE_VERSION=$($SCRIPT_BASEDIR/application.php info --version_number)
DST="$RELEASE_NAME-$RELEASE_VERSION"


set -e
export COMPOSER_PREFER_SOURCE

cd $SCRIPT_BASEDIR
$MKDIR releases/$DST

for file in application.php bootstrap.php composer.json Makefile README.md src; do
	$CP $file releases/$DST
done

cd releases/$DST
make install_release
make clean_release
cd ..
#exit

find $DST -name .DS_Store -exec rm -vf {} \;
tar -vcpzf $DST.tar.gz $DST
chmod -R 777 $DST
$RM $DST

echo "release '$DST' done"
