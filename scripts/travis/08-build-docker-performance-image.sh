#!/usr/bin/env bash
#
# Download stored project artifact on S3 and pack it into Thunder performance docker image

# Get Thunder performance Docker project for packaging
THUNDER_PERFORMANCE="${TEST_DIR}/../docker-thunder-performance"
git clone https://github.com/thunder/docker-thunder-performance.git "${THUNDER_PERFORMANCE}"

# Download project artifact from S3
AWS_ACCESS_KEY_ID="${ARTIFACTS_KEY}" AWS_SECRET_ACCESS_KEY="${ARTIFACTS_SECRET}" aws s3 cp "s3://thunder-builds/${PROJECT_ARTIFACT_FILE_NAME}" "${PROJECT_ARTIFACT_FILE}"

# Extract files to www directory for Docker image packaging
mkdir -p "${THUNDER_PERFORMANCE}/www"
tar -zxf "${PROJECT_ARTIFACT_FILE}" -C "${THUNDER_PERFORMANCE}/www"

# Build Docker image
cd "${THUNDER_PERFORMANCE}"

# Build Docker image and tag it
./build.sh --tag "thunder-performance:${BRANCH_NAME}"

# list Docker images
docker images
