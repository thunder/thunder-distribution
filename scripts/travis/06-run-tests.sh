#!/usr/bin/env bash

source ${THUNDER_DIST_DIR}/scripts/travis/05-setup-tests.sh

cd ${TEST_DIR}/docroot

# Silently pull docker image
docker pull selenium/standalone-chrome:3.14.0-iron > /dev/null
docker run -d -p 4444:4444 -v $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests:/tests -v /dev/shm:/dev/shm --net=host selenium/standalone-chrome:3.14.0-iron
docker ps -a

# Make simple export import
if [[ "${TEST_UPDATE}" != "true" ]]; then
    drush -y cex sync

    # We have to use "2>&1" because drush outputs everything to stderr
    DRUSH_CIM_RESULT=$(drush -y cim sync 2>&1)
    if [[ "${DRUSH_CIM_RESULT}" != *"There are no changes to import."* ]]; then
        exit 1
    fi
fi

# execute Drupal tests
thunderDumpFile=thunder.php phpunit --verbose --debug --configuration core --group Thunder ${ADDITIONAL_PHPUNIT_PARAMETERS} $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests || exit 1

if [[ ${TEST_UPDATE} == "true" ]]; then
  thunderDumpFile=thunder.php phpunit --verbose --debug --configuration core --group ThunderInstaller ${ADDITIONAL_PHPUNIT_PARAMETERS} $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests || exit 1
fi
