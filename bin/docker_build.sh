#!/usr/bin/env bash

# Builds the Docker image.

DATE=$(date +"%Y%m%d_%H%M%S")
SCRIPT_BASEDIR=$(dirname "$0")


set -e
which docker &> /dev/null || { echo 'ERROR: docker not found in PATH'; exit 1; }

cd "${SCRIPT_BASEDIR}/.."
source ./.env

docker build --tag ${IMAGE_NAME}:${DATE} .
docker tag ${IMAGE_NAME}:${DATE} ${IMAGE_NAME}:latest
