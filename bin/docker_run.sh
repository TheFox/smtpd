#!/usr/bin/env bash

# Starts the Docker container.

SCRIPT_BASEDIR=$(dirname "$0")


set -e
which docker &> /dev/null || { echo 'ERROR: docker not found in PATH'; exit 1; }

cd "${SCRIPT_BASEDIR}/.."
source ./.env

docker run \
    --rm \
    --tty \
    --interactive \
    --name ${IMAGE_NAME_SHORT} \
    --hostname ${IMAGE_NAME_SHORT} \
    --volume "$PWD":/app \
    --env COMPOSER_AUTH="{\"github.com\":\"$GITHUB_API_TOKEN\"}" \
    ${IMAGE_NAME}
