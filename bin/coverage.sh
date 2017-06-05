#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")


set -e
cd "${SCRIPT_BASEDIR}/.."

mkdir -p tmp
vendor/bin/phpunit --coverage-html tmp/coverage
