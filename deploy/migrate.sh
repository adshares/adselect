#!/usr/bin/env bash

# Usage: migrate.sh [<work-dir>]
cd ${1:-"."}

bin/console ops:es:create-index
if [ $? -ne 0 ]; then exit 1; fi
