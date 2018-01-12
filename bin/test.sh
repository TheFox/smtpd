#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")


set -e
cd "${SCRIPT_BASEDIR}/.."

mkdir -p tmp

# PHP Code Sniffer
./vendor/bin/phpcs --config-set ignore_warnings_on_exit 1
./vendor/bin/phpcs --config-show
./vendor/bin/phpcs

# PHPUnit
vendor/bin/phpunit

# PHPStan
vendor/bin/phpstan analyse --no-progress --level 5 src tests
