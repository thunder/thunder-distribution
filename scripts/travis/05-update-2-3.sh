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

mv composer.json composer1.json
cat composer1.json| jq '.extra.patches += {"drupal/video_embed_field":{"Include upgrade path from video_embed_field":"https://www.drupal.org/files/issues/2018-12-11/2997799-22.patch"}}' > composer.json
COMPOSER_MEMORY_LIMIT=-1 composer require "drupal/media_entity:^2.0" "drupal/video_embed_field:^2.0" "drupal/media_entity_image"

cd ${TEST_DIR}/docroot
drush cr
drush updb -y

