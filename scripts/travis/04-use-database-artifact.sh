#!/usr/bin/env bash
#
# Download stored database dump from S3 and import it

# Download project artifact from S3
AWS_ACCESS_KEY_ID="${ARTIFACTS_KEY}" AWS_SECRET_ACCESS_KEY="${ARTIFACTS_SECRET}" aws s3 cp "s3://thunder-builds/${DB_ARTIFACT_FILE_NAME}" "${DB_ARTIFACT_FILE}"

# Unzip to deployment file. Deployment file should be provided for other scripts to work properly.
gunzip < "${DB_ARTIFACT_FILE}" > "${DEPLOYMENT_DUMP_FILE}"

# Adjust settings.php
SETTINGS_PHP_FILE="${TEST_DIR}/docroot/sites/default/settings.php"

if [[ ! -f "{SETTINGS_PHP_FILE}" ]]; then
    cp  "${TEST_DIR}/docroot/sites/default/default.settings.php" "${SETTINGS_PHP_FILE}"

    # Set write for the settings.php
    chmod a+w "${SETTINGS_PHP_FILE}"

    # Set hash salt
    HASH_SALT="$(openssl rand -hex 32)"
    echo -e "\$settings['hash_salt'] = '${HASH_SALT}';\n\n" >> "${SETTINGS_PHP_FILE}"

    # Set config sync folder
    mkdir -p "${TEST_DIR}/config/sync"
    echo -e "\$config_directories['sync'] = '../config/sync';\n\n" >> "${SETTINGS_PHP_FILE}"

    # Set Database connection settings
    echo -e "if (!isset(\$databases)) { \$databases = []; }" >> "${SETTINGS_PHP_FILE}"
    echo -e "\$databases['default']['default'] = [ 'host' => '127.0.0.1', 'port' => '3306', 'database' => 'drupal', 'username' => 'travis', 'password' => '', 'prefix' => '', 'namespace' => 'Drupal\\Core\\Database\\Driver\mysql', 'driver' => 'mysql' ];" >> "${SETTINGS_PHP_FILE}"

    # Remove write for settings.php
    chmod a-w "${SETTINGS_PHP_FILE}"
fi

# Import database
cd "${TEST_DIR}/docroot"
drush -y sql-create
drush -y sql-cli < "${DEPLOYMENT_DUMP_FILE}"
