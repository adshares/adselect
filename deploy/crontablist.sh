#!/usr/bin/env bash
set -eu

SERVICE_DIR=${SERVICE_DIR:-$(dirname "$(dirname "$(readlink -f "$0")")")}

echo -n "11 * * * * "
echo -n "nice php ${SERVICE_DIR}/bin/console ops:es:update-stats -t 4"
echo ""

echo -n "*/5 * * * * "
echo -n "nice php ${SERVICE_DIR}/bin/console ops:es:update-exp"
echo ""

echo -n "19 3 * * * "
echo -n "php ${SERVICE_DIR}/bin/console ops:es:clean-events"
echo ""
