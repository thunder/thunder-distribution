#!/usr/bin/env bash

source ${THUNDER_DIST_DIR}/scripts/travis/05-setup-tests.sh

# Run Drupal tests
cd ${TEST_DIR}/docroot

PHPUNIT=${TEST_DIR}/vendor/bin/phpunit

phpunit --version

thunderDumpFile=thunder.php php ${PHPUNIT} --verbose --debug --configuration core --group ThunderConfig $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests || exit 1
