#!/usr/bin/env bash

cd ${THUNDER_DIST_DIR}
composer create-project BurdaMagazinOrg/thunder-project:2.x ${TEST_DIR} --stability dev --no-interaction --no-install

cd ${TEST_DIR}

composer require --dev "webflo/drupal-core-require-dev:^8.4"  --no-update
composer require "BurdaMagazinOrg/thunder:^8.2.10" "drupal/checklistapi:^1.4" "drupal/simple_sitemap:^2.7" "drupal/dropzonejs:^1.0-alpha3" "drupal/liveblog:^1.0-alpha2" "cweagans/composer-patches:^1.6.6" "drupal-composer/drupal-scaffold:^2.5.3" "zaporylie/composer-drupal-optimizations:^1.1.0" --no-progress --no-update
cp ${THUNDER_DIST_DIR}/tests/fixtures/thunder2.composer.lock composer.lock
composer install
composer drupal-scaffold
