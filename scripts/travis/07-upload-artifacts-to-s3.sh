#!/usr/bin/env bash
#
# Package and upload built Thunder project and database

# Install AWS CLI
pip install --user awscli

# Package database
gzip "${DEPLOYMENT_DUMP_FILE}"
DB_ASSET_FILE="db-dump-${TRAVIS_BUILD_ID}.gz"
mv "${DEPLOYMENT_DUMP_FILE}.gz" "${DB_ASSET_FILE}"

# Package project

cd "${TEST_DIR}"

# Cleanup project
composer install --no-dev
rm -rf "${TEST_DIR}/docroot/sites/default/files/*"
find "${TEST_DIR}" -type d -name ".git" | xargs rm -rf
find "${THUNDER_DIST_DIR}" -type d -name ".git" | xargs rm -rf

# Make zip for package
PROJECT_ASSET_FILE="thunder-${TRAVIS_BUILD_ID}.tar.gz"
tar -czhf "${PROJECT_ASSET_FILE}" "${TEST_DIR}"

# Upload files to S3
# TODO: implement it!
exit 1
