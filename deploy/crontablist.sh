#!/usr/bin/env bash
set -eu

SERVICE_DIR=${SERVICE_DIR:-$(dirname "$(dirname "$(readlink -f "$0")")")}

echo -n "19 4 * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:es:clean-user-history"
echo ""

echo -n "19 3 * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:es:clean-events"
echo ""
