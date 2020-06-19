#!/usr/bin/env bash
#
# Download stored project artifact on S3 and pack it into Thunder performance docker image

# Get Thunder performance Docker project for packaging
THUNDER_PERFORMANCE="${TEST_DIR}/../docker-thunder-performance"
git clone https://github.com/thunder/docker-thunder-performance.git "${THUNDER_PERFORMANCE}"

# Download project artifact from S3
aws s3 cp "s3://thunder-builds/${PROJECT_ARTIFACT_FILE_NAME}" "${PROJECT_ARTIFACT_FILE}"

# Extract files to www directory for Docker image packaging
mkdir -p "${THUNDER_PERFORMANCE}/www"
tar -zxf "${PROJECT_ARTIFACT_FILE}" -C "${THUNDER_PERFORMANCE}/www"

# Build Docker image
cd "${THUNDER_PERFORMANCE}"

# Build Docker image and tag it
DOCKER_SANITIZED_BRANCH_NAME=$(echo "${BRANCH_NAME}" | sed 's/\//_/g')
DOCKER_IMAGE_TAG="burda/thunder-performance:${DOCKER_SANITIZED_BRANCH_NAME}-${TRAVIS_JOB_ID}"
./build.sh --tag "${DOCKER_IMAGE_TAG}"

# list Docker images
docker images

# Login to Docker HUB
echo "${DOCKER_PASSWORD_BASE64}" | base64 -d | docker login -u "${DOCKER_USERNAME}" --password-stdin

# Push to Docker HUB
docker push "${DOCKER_IMAGE_TAG}"

# Start Thunde performance testing task for created image
API_CALL_HTTP_CODE=$(curl \
  --request POST \
  --insecure \
  "https://${THUNDER_PTM_HOST}:3000/warmers" \
  --header "Authorization: Bearer ${THUNDER_PTM_TOKEN}" \
  --header "Content-Type: application/json" \
  --data "{\"branchTag\":\"${DOCKER_SANITIZED_BRANCH_NAME}\",\"imageTag\":\"${DOCKER_SANITIZED_BRANCH_NAME}-${TRAVIS_JOB_ID}\",\"composeType\":\"default\"}" \
  --output /dev/stderr \
  --write-out "%{http_code}" \
)

if [[ "${API_CALL_HTTP_CODE}" != "200" ]]; then
    exit 1
fi
