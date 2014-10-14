#!/bin/bash

RM="rm -rf"
MKDIR="mkdir -p"
MV="mv -i"
CP="cp -rp"

SCRIPT_BASEDIR=$(dirname $0)
RELEASE_NAME=$(./application.php info --name_lc)
RELEASE_VERSION=$(./application.php info --version_number)


cd $SCRIPT_BASEDIR
$MKDIR releases $RELEASE_NAME-$RELEASE_VERSION

$CP application.php $RELEASE_NAME-$RELEASE_VERSION
$CP bootstrap.php $RELEASE_NAME-$RELEASE_VERSION
$CP composer.json $RELEASE_NAME-$RELEASE_VERSION
$CP functions.php $RELEASE_NAME-$RELEASE_VERSION
$CP README.md $RELEASE_NAME-$RELEASE_VERSION
$CP src $RELEASE_NAME-$RELEASE_VERSION

cd $RELEASE_NAME-$RELEASE_VERSION
curl -sS https://getcomposer.org/installer | php
./composer.phar install --no-dev
$RM ./composer.*
cd ..

find $RELEASE_NAME-$RELEASE_VERSION -name .DS_Store -exec rm -v {} \;
tar -cpzf $RELEASE_NAME-$RELEASE_VERSION.tar.gz $RELEASE_NAME-$RELEASE_VERSION
$MV $RELEASE_NAME-$RELEASE_VERSION.tar.gz releases
chmod -R 777 $RELEASE_NAME-$RELEASE_VERSION
$RM $RELEASE_NAME-$RELEASE_VERSION

echo "release '$RELEASE_NAME-$RELEASE_VERSION' done"
