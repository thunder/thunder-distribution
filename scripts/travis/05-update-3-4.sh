#!/usr/bin/env bash

# Update to Thunder with D9.
cd ${TEST_DIR}
composer config repositories.thunder path ${THUNDER_DIST_DIR}

composer require "thunder/thunder-distribution:*" --no-update

composer update

cd ${TEST_DIR}/docroot
drush updb -y
