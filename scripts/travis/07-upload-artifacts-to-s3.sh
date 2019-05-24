#!/usr/bin/env bash
#
# Package and upload built Thunder project and database

# Install AWS CLI
pip install --user awscli

# Package database
DB_ARTIFACT_FILE_NAME="${TRAVIS_BUILD_ID}-db-dump.gz"
DB_ARTIFACT_FILE="${THUNDER_DIST_DIR}/../${DB_ARTIFACT_FILE_NAME}"
gzip < "${DEPLOYMENT_DUMP_FILE}" > "${DB_ARTIFACT_FILE}"

# Cleanup project
cd "${TEST_DIR}"
composer install --no-dev
rm -rf "${TEST_DIR}/docroot/sites/default/files/*"
find "${TEST_DIR}" -type d -name ".git" | xargs rm -rf
find "${THUNDER_DIST_DIR}" -type d -name ".git" | xargs rm -rf

# Make zip for package
PROJECT_ARTIFACT_FILE_NAME="${TRAVIS_BUILD_ID}-thunder.tar.gz"
PROJECT_ARTIFACT_FILE="${THUNDER_DIST_DIR}/../${PROJECT_ARTIFACT_FILE_NAME}"
cd "${TEST_DIR}" && tar -czhf "${PROJECT_ARTIFACT_FILE}" .

# Upload files to S3 bucket
AWS_ACCESS_KEY_ID="${ARTIFACTS_KEY}" AWS_SECRET_ACCESS_KEY="${ARTIFACTS_SECRET}" aws s3 cp "${DB_ARTIFACT_FILE}" "s3://thunder-builds/${DB_ARTIFACT_FILE_NAME}"
AWS_ACCESS_KEY_ID="${ARTIFACTS_KEY}" AWS_SECRET_ACCESS_KEY="${ARTIFACTS_SECRET}" aws s3 cp "${PROJECT_ARTIFACT_FILE}" "s3://thunder-builds/${PROJECT_ARTIFACT_FILE_NAME}"

exit 1
