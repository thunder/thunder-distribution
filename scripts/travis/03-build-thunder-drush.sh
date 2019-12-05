#!/usr/bin/env bash

cd ${THUNDER_DIST_DIR}

# Build drupal + thunder from makefile
drush make --concurrency=5 drupal-org-core.make ${TEST_DIR}/docroot -y
mkdir ${TEST_DIR}/docroot/profiles/thunder
shopt -s extglob
rsync -a . ${TEST_DIR}/docroot/profiles/thunder --exclude docroot

drush make -y --no-core ${TEST_DIR}/docroot/profiles/thunder/drupal-org.make ${TEST_DIR}/docroot/profiles/thunder

# Get development branch of Thunder Admin theme (to use same admin theme as for composer build)
rm -rf ${TEST_DIR}/docroot/profiles/thunder/themes/thunder_admin
git clone --depth 1 --single-branch --branch ${THUNDER_ADMIN_BRANCH} https://github.com/BurdaMagazinOrg/theme-thunder-admin.git ${TEST_DIR}/docroot/profiles/thunder/themes/thunder_admin

composer install --working-dir=${TEST_DIR}/docroot

# require development packages needed for testing
composer config minimum-stability dev --working-dir ${TEST_DIR}/docroot
COMPOSER_ROOT_VERSION=8.8.x-dev composer require drupal/core-dev "thunder/thunder_testing_demo:3.x-dev" "lullabot/amp" "pusher/pusher-php-server:^3.0.0" --no-progress --working-dir ${TEST_DIR}/docroot
