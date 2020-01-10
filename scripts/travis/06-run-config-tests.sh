#!/usr/bin/env bash

source ${THUNDER_DIST_DIR}/scripts/travis/05-setup-tests.sh

# Run Drupal tests
cd ${TEST_DIR}/docroot

phpunit --verbose --debug --configuration core --group ThunderConfig ${ADDITIONAL_PHPUNIT_PARAMETERS} $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests || exit 1
