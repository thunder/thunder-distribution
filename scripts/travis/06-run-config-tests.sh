#!/usr/bin/env bash

source ${THUNDER_DIST_DIR}/scripts/travis/05-setup-tests.sh

# Run Drupal tests
cd ${TEST_DIR}/docroot

thunderDumpFile=thunder.php phpunit --verbose --debug --configuration ${TEST_DIR}/docroot/core --group ThunderConfig ${THUNDER_DIST_DIR} || exit 1
