#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"
cd "$ROOT_DIR"

docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.php
