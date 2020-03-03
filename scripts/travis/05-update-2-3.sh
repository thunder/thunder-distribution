#!/usr/bin/env bash

# Update paragraphs to version 1.10 first.
cd ${TEST_DIR}
composer require drupal/paragraphs:1.10 drupal/core:~8.7.0 --no-update
composer update

# Get custom update_helper branch
rm -rf ${TEST_DIR}/docroot/modules/contrib/update_helper
git clone --depth 1 --single-branch --branch "fix/3089823-delete-action-is-not-performed" https://github.com/BurdaMagazinOrg/module-update_helper.git ${TEST_DIR}/docroot/modules/contrib/update_helper

cd ${TEST_DIR}/docroot
drush updb -y

# Remove paragraphs dependency.
cd ${TEST_DIR}
composer remove drupal/paragraphs --no-update
composer update

# Update to the latest version of thunder.
cd ${TEST_DIR}
composer require burdamagazinorg/thunder:~8.2.51 --no-update
composer update

# Get custom update_helper branch
rm -rf ${TEST_DIR}/docroot/modules/contrib/update_helper
git clone --depth 1 --single-branch --branch "fix/3089823-delete-action-is-not-performed" https://github.com/BurdaMagazinOrg/module-update_helper.git ${TEST_DIR}/docroot/modules/contrib/update_helper

cd ${TEST_DIR}/docroot
drush updb -y

cd ${TEST_DIR}
composer config repositories.thunder path ${THUNDER_DIST_DIR}

composer remove burdamagazinorg/thunder
composer require "thunder/thunder-distribution:*" drupal/core:~8.8.0 --no-update

composer update

composer require "thunder/thunder_testing_demo:3.x-dev" "drupal/media_entity:^2.0-beta4" "drupal/video_embed_field:^2.2" "drupal/media_entity_image" "drupal/riddle_marketplace:^3.0-beta2"

# Get custom update_helper branch
rm -rf ${TEST_DIR}/docroot/modules/contrib/update_helper
git clone --depth 1 --single-branch --branch "fix/3089823-delete-action-is-not-performed" https://github.com/BurdaMagazinOrg/module-update_helper.git ${TEST_DIR}/docroot/modules/contrib/update_helper

cd ${TEST_DIR}/docroot
drush updb -y
drush en vem_migrate_oembed -y
drush vem:migrate_oembed
drush cr
