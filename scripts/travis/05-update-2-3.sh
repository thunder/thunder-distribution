#!/usr/bin/env bash

cd ${TEST_DIR}
composer update

cd ${TEST_DIR}/docroot
drush updb -y

cd ${TEST_DIR}
composer config repositories.thunder path ${THUNDER_DIST_DIR}

composer remove burdamagazinorg/thunder --no-update
composer require "thunder/thunder-distribution:*" --no-update

composer update || true
composer update

composer require "drupal/media_entity:^2.0-beta4" "drupal/video_embed_field:^2.2" "drupal/media_entity_image" "drupal/riddle_marketplace:^3.0-beta2"

cd ${TEST_DIR}/docroot
drush updb -y
drush en vem_migrate_oembed -y
drush vem:migrate_oembed
drush cr
