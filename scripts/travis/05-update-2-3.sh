#!/usr/bin/env bash

cd ${TEST_DIR}

composer remove burdamagazinorg/thunder --no-update
composer require thunder/thunder-distribution --no-update
composer update
