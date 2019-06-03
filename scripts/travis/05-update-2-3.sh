#!/usr/bin/env bash

composer update
cd ${TEST_DIR}/docroot
drush cr
drush updb -y
drush entup -y

cd ${TEST_DIR}
composer config repositories.thunder path ${THUNDER_DIST_DIR}

composer remove burdamagazinorg/thunder --no-update
composer require thunder/thunder-distribution:* --no-update
composer update

cd ${TEST_DIR}/docroot
drush updb -y

