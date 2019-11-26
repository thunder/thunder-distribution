#!/usr/bin/env bash

# Check for deprecated methods.
cd ${TEST_DIR}
cp ${THUNDER_DIST_DIR}/phpstan.neon.dist phpstan.neon
phpstan analyse --memory-limit 300M ${TEST_DIR}/docroot/modules/contrib
