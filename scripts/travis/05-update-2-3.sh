#!/usr/bin/env bash

cd ${TEST_DIR}
composer update

cd ${TEST_DIR}/docroot
drush updb -y

cd ${TEST_DIR}
composer config repositories.thunder path ${THUNDER_DIST_DIR}

composer remove burdamagazinorg/thunder --no-update
composer require "thunder/thunder-distribution:*" --no-update
composer remove drupal/dropzonejs --no-update

composer update

COMPOSER_MEMORY_LIMIT=-1 composer require "drupal/media_entity:^2.0" "drupal/video_embed_field:^2.0"

cd ${TEST_DIR}/docroot
drush updb -y

