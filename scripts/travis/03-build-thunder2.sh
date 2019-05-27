#!/usr/bin/env bash

cd ${THUNDER_DIST_DIR}
composer create-project BurdaMagazinOrg/thunder-project:2.x ${TEST_DIR} --stability dev --no-interaction --no-install

cd ${TEST_DIR}

composer config repositories.thunder path ${THUNDER_DIST_DIR}
COMPOSER_MEMORY_LIMIT=-1 composer require "BurdaMagazinOrg/thunder:*" "mglaman/phpstan-drupal" "phpstan/phpstan:0.11.6" "nette/di:*@stable" "phpstan/phpstan-deprecation-rules" --no-progress --prefer-lowest
