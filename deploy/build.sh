#!/usr/bin/env bash
# Usage: build.sh [<location-of-functions-file-to-include> [<work-dir>]]
[[ -z ${1:-""} ]] && set -eu || source ${1}/_functions.sh --vendor
cd ${2:-"."}

composer install --no-dev

./bin/console cache:clear
./bin/console ops:es:create-index --force
