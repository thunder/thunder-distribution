#!/usr/bin/env bash

# Check for deprecated methods.
cd ${TEST_DIR}
cp ${THUNDER_DIST_DIR}/phpstan.neon.dist phpstan.neon
phpstan analyse --memory-limit 1200M ${TEST_DIR}/docroot/modules/contrib -q || true
phpstan analyse --memory-limit 1200M ${TEST_DIR}/docroot/modules/contrib || true
