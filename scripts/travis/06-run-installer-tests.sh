#!/usr/bin/env bash

phpunit --verbose --debug --configuration core --group ThunderInstaller ${ADDITIONAL_PHPUNIT_PARAMETERS} $(pwd)/$(drush eval "echo drupal_get_path('profile', 'thunder');")/tests || exit 1
