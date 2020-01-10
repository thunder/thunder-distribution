#!/usr/bin/env bash

# Run Drupal tests
cd ${TEST_DIR}/docroot

phpunit --verbose --debug --configuration core --group ThunderConfig ${ADDITIONAL_PHPUNIT_PARAMETERS} $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests || exit 1
