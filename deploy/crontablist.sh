#!/usr/bin/env bash
set -eu

SERVICE_DIR=${SERVICE_DIR:-$(dirname "$(dirname "$(readlink -f "$0")")")}

echo -n "11 * * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:es:update-stats"
echo ""

echo -n "19 3 * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:es:clean-events"
echo ""
