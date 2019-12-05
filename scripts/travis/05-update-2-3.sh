#!/usr/bin/env bash

# Update paragraphs to version 1.3 first.
cd ${TEST_DIR}
composer require drupal/paragraphs:1.3 --no-update

composer update drupal/paragraphs

cd ${TEST_DIR}/docroot
drush updb -y

# Update paragraphs to version required by thunder.
cd ${TEST_DIR}
composer remove drupal/paragraphs --no-update
composer require burdamagazinorg/thunder:~8.2.51 --no-update
composer update

cd ${TEST_DIR}/docroot
drush updb -y

cd ${TEST_DIR}
composer config repositories.thunder path ${THUNDER_DIST_DIR}

composer remove burdamagazinorg/thunder
composer require "thunder/thunder-distribution:*" --no-update

composer update

composer require "thunder/thunder_testing_demo:3.x-dev" "drupal/media_entity:^2.0-beta4" "drupal/video_embed_field:^2.2" "drupal/media_entity_image" "drupal/riddle_marketplace:^3.0-beta2"

cd ${TEST_DIR}/docroot
drush updb -y
drush en vem_migrate_oembed -y
drush vem:migrate_oembed
drush cr
