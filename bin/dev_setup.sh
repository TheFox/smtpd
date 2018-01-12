#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")
COMPOSER_OPTS=(
    --no-suggest
    --no-progress
    --no-interaction
)

set -e
cd "${SCRIPT_BASEDIR}/.."

which php &> /dev/null || { echo 'ERROR: php not found in PATH'; exit 1; }
which curl &> /dev/null || { echo 'ERROR: curl not found in PATH'; exit 1; }

if which composer &> /dev/null; then
    composer install ${COMPOSER_OPTS[@]}
else
    if [[ ! -f composer.phar ]] ; then
        curl -sS https://getcomposer.org/installer | php
        chmod u=rwx,go=rx composer.phar
    fi
    
    php composer.phar install ${COMPOSER_OPTS[@]}
fi
