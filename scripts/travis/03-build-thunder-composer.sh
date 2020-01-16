#!/usr/bin/env bash

# Download latest Thunder release for update
if [[ ${TEST_UPDATE} == "true" ]]; then
    # Download latest release from drupal.org
    mkdir -p $UPDATE_BASE_PATH
    cd $UPDATE_BASE_PATH
    drush dl thunder --drupal-project-rename="docroot" -y
    composer install --working-dir=${UPDATE_BASE_PATH}/docroot
fi

cd ${THUNDER_DIST_DIR}
composer create-project thunder/thunder-project:3.x ${TEST_DIR} --stability dev --no-interaction --no-install

cd ${TEST_DIR}

if [[ ${TEST_UPDATE} == "true" ]]; then
    sed -i 's/docroot\/profiles\/contrib/docroot\/profiles/g' composer.json
fi

composer config repositories.thunder path ${THUNDER_DIST_DIR}
composer require "drupal/core-composer-scaffold:8.9.x-dev" "drupal/core-recommended:8.9.x-dev" "drush/drush:~9.0" "drupal/rules:3.x-dev" "drupal/feeds:3.x-dev" "drupal/facets:1.x-dev" "drupal/schema_metatag" "drupal/graphql" --no-update --no-progress ${ADDITIONAL_COMPOSER_PARAMETERS}
composer require "thunder/thunder-distribution:*" "thunder/thunder_testing_demo:3.x-dev" "phpunit/phpunit:^6.5" "composer/composer:^1.9.0" "mglaman/phpstan-drupal:~0.12.0" "phpstan/phpstan-deprecation-rules:~0.12.0" "drupal/riddle_marketplace:^3.0-beta2" "drupal/nexx_integration:^3.0" "valiton/harbourmaster:~8.1" --no-progress ${ADDITIONAL_COMPOSER_PARAMETERS}

 # Get custom branch of Thunder Admin theme
rm -rf ${TEST_DIR}/docroot/themes/contrib/thunder_admin
git clone --depth 1 --single-branch --branch ${THUNDER_ADMIN_BRANCH} https://github.com/BurdaMagazinOrg/theme-thunder-admin.git ${TEST_DIR}/docroot/themes/contrib/thunder_admin
