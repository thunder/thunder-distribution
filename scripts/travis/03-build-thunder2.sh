#!/usr/bin/env bash

cd ${THUNDER_DIST_DIR}
composer create-project BurdaMagazinOrg/thunder-project:2.x ${TEST_DIR} --stability dev --no-interaction --no-install

cd ${TEST_DIR}
composer require "drupal/liveblog:^1.0-alpha2" "drush/drush:^9.6.2" "cweagans/composer-patches:^1.6.6" --no-progress --no-update
COMPOSER_MEMORY_LIMIT=-1 composer update --no-progress --prefer-lowest
