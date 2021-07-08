---
sidebar: auto
---
# Contribute to Thunder

Thunder is a "Drupal" based distribution.

We are working on GitHub and drupal.org. Issues are managed on drupal.org. That gives us the possibility to better interact with related issues. Code improvements will be managed over GitHub's PR system.

If you found an issue with Thunder, please open a [ticket](https://www.drupal.org/project/issues/thunder?categories=All) on drupal.org.
Please have in mind that Thunder is a collection of Drupal modules, a set of configurations, and very little custom code.

So, if you can track down an issue to a specific module, please open the ticket in the corresponding issue queue on drupal.org.

If you want to open a PR for the Thunder distribution, please make sure you created a corresponding issue on d.o. before. All created pull requests should contain a d.o. issue number in its title.

Please also note the pull request template to create better quality pull requests.

## Automated code checks

All Thunder pull requests are executed on [GitHub actions](https://github.com/thunder/thunder-distribution/actions). On every pull request tests will be executed (or when new commits are pushed into the pull request branch). All code will be checked against coding style.

We support some test execution options. They can be provided by pull request labels. Here is a list of supported labels:
- *test-upgrade* - this option will execute a custom test path, where an update (including the execution of update hooks) from Thunder 2 will be tested. This option should be used in case of a pull request with update hooks or module updates.
- *test-min* - this option installs the pull request version of Thunder with the oldest possible dependencies and executes the test suite.
- *test-performance* - this option pushed the codebase to our performance testing infrastructure. A successful test-max build is required for this.

## How-To's

### Update profile configuration

The Thunder distribution ships the config_profile module as a dev
dependency for easier config updates. The workflow for updating config
files that are shipped in the distribution should be:
* Install the latest dev version of Thunder
* Enable the Config Profile module
  ```
  drush en config_profile
  ```
* Apply all your changes in the UI
* Export your configuration
  ```
  drush cex
  ```
  The configuration is exported to the chosen config_directory and simultaneously to your profile folder.
* config_profile has now copied all the config changes to the profile
  folder
* Put all new config files to the desired folder and add track it in git
* Remove all untracked files
  ```
  git clean -fd
  ```

### Writing update hooks

To support the creation of update hooks, Thunder integrated the `update_helper` module. That contains several methods to e.g. update the existing configuration or enabling modules.

Outputting results of update hook is highly recommended for that we have provided UpdateLogger, it handles the output of result properly for `drush` or  UI (`update.php`) update workflow.
That's why every update hook that changes something should log what is changed and was it successful or it has failed. And the last line in the update hook should be returning of UpdateLogger output.
UpdateLogger service is also used by Thunder Updater and it can be retrieved from it. Here are two examples of how to get and use UpdateLogger.
All text logged as INFO, will be outputted as success in `drush` output.

```php
  // Get service directly.
  /** @var \Drupal\update_helper\UpdateLogger $updateLogger */
  $updateLogger = \Drupal::service('update_helper.logger');

  // Log change success or failures.
  if (...) {
    $updateLogger->info('Change is successful.');
  }
  else {
    $updateLogger->warning('Change has failed.');
  }

  // At end of update hook return result of UpdateLogger::output().
  return $updateLogger->output();
```

Other way to get UpdateLogger is from Update Helper Updater service.
```php
  // Get service from Thunder Updater service.
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');
  $updateLogger = $updater->logger();

  ...

  // At end of update hook return result of UpdateLogger::output().
  return $updateLogger->output();
```

#### Importing new configurations

You have to create configuration update definition file with global `import_configs` action. For example:
```yaml
__global:
  import_configs:
    - config.to.import
    - config.to.import-no2
```

After that you just have to execute configuration update. For example:
```php
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');
  $updater->executeUpdate('thunder_article', 'thunder_update_8101');

  return $updater->logger()->output();
```
This update hook will import configurations, that are in a module or profile config directory.

#### Updating existing configuration (with manually defined configuration changes)

Before Drupal\update_helper\Updater::updateConfig() updates existing configuration, it could check the current values of that config. That helps to leave the modified, existing configuration in a valid state.

```php
  // List of configurations that should be checked for existence.
  $expectedConfig['content']['field_url'] = [
    'type' => 'instagram_embed',
    'weight' => 0,
    'label' => 'hidden',
    'settings' => [
      'width' => 241,
      'height' => 313,
    ],
    'third_party_settings' => [],
  ];

  // New configuration that should be applied.
  $newConfig['content']['thumbnail'] = [
    'type' => 'image',
    'weight' => 0,
    'region' => 'content',
    'label' => 'hidden',
    'settings' => [
      'image_style' => 'media_thumbnail',
      'image_link' => '',
    ],
    'third_party_settings' => [],
  ];

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');
  $updater->updateConfig('core.entity_view_display.media.instagram.thumbnail', $newConfig, $expectedConfig);
```

#### Updating existing configuration (with using of generated configuration changes)

With the Thunder Updater module, we have provided Drupal Console command that will generate update configuration changes (it's called configuration update definition or CUD). Configuration update definition (CUD) will be stored in `config/update` directory of the module and it can be easily executed with Thunder Updater.

Workflow to generate Thunder configuration update is following:
1. Make a clean install of the previous version of Thunder (version for which one you want to create configuration update). For example, if you are merging changes to `develop` branch, then you should install Thunder for that branch
2. When Thunder is installed, make code update (with code update also configuration files will be updated, but not active configuration in the database)
3. Execute update hooks if it's necessary (e.g. in a case when you have a module and/or core updates in your branch)
4. Now is a moment to generate Thunder configuration update code. For that, we have provided the following drupal console command: `drupal generate:configuration:update`. That command should be executed and there is some information that has to be filled, like module name where all generated data will be saved (CUD file, checklist `update.yml` and update hook function). Then also information for checklist entry, like title, success message, and failure message. Command will generate CUD file and save it in `config/update` folder of the module, it will add an entry in `update.yml` file for the checklist and it will create an update hook function in `<module_name>.install` file.
5. After the command has finished it will display what files are modified and generated. It's always good to make an additional check of generated code.

Additional information about command options is provided with `drupal generate:configuration:update --help` and it's also possible to provide all information directly in the command line without using the wizard.

### Write automated tests

Thunder distribution comes with a set of Drupal tests. They can be used to validate Thunder installation or to use provided traits for your own project Drupal tests.

### How to run the tests

Please see the official [Drupal documentation](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal)

To speed up the test execution time, you can provide a database dump to Thunder tests:
```bash
cd docroot
php ./core/scripts/db-tools.php dump-database-d8-mysql | gzip > thunder.sql.gz
export thunderDumpFile=/path/to/thunder.sql.gz
```

### How to run the NightwatchJS performance tests <Badge type="danger" text="Not reviewed" vertical="bottom" />

1. You need to install [Yarn](https://yarnpkg.com). Please check the installation documentation for it.
2. You have to install `thunder/thunder_performance_measurement` package. To do that, execute the following command in your project root directory: `composer require thunder/thunder_performance_measurement:dev-master --dev` and enable the module by executing: `drush en thunder_performance_measurement` in your `docroot` directory.
3. You need to install [Elastic APM Node.js Agent](https://www.npmjs.com/package/elastic-apm-node) in Drupal Core node packages. To do that go to your `docroot/core` directory and execute the following command: `yarn add elastic-apm-node --dev`
4. You have to adjust your `.env` file inside `docroot/core` directory. You can copy the `.env.example` to `.env` and edit it accordingly. Thunder tests require the following environment variables: `DRUPAL_TEST_BASE_URL`, `THUNDER_BRANCH`, `THUNDER_SITE_HOSTNAME` and `THUNDER_APM_URL`. The `THUNDER_BRANCH` is the branch name where tests are executing, for example, `8.x-4.x`. The `THUNDER_SITE_HOSTNAME` is the hostname where tests are executing, for example, `thunder.dev`. The `THUNDER_APM_URL` is URL to Elastic APM Server, for example, `http://localhost:8200`.
5. After that, you can run NightwatchJS tests by executing the following command inside `docroot/core` directory: `yarn test:nightwatch <path to JS Test file>`. Here is an example: `yarn test:nightwatch ../profiles/contrib/thunder/tests/src/Nightwatch/Tests/CreateMostUsedContent.js`

**If you have a problem with outdated chromedriver**

Drupal core does not update javascript dependencies so fast and chromedriver may be outdated and unable to work with chrome installed on the system. You can provide chrome that can be used by chromedriver inside a docker container. You can do it with the following command:
```bash
docker run --name selenium_chrome -d -P -p 6000:5900 -p 4444:4444 --shm-size 256m --add-host="thunder.dd:172.16.123.1" selenium/standalone-chrome-debug:3.141.59-selenium
```

You have to find what is correct docker image tag for the chrome version you need. To do that you have to take a look at [selenium docker releases](https://github.com/SeleniumHQ/docker-selenium/releases).
This workflow is similar to PHP JavaScript tests and for additional information, you can take a look at **How to run the tests** section.

After you have running chrome in docker, you have also to change environment variables in `.env` file. The following environment variable should be set:
```bash
DRUPAL_TEST_WEBDRIVER_PORT=4444
DRUPAL_TEST_WEBDRIVER_PATH_PREFIX=/wd/hub
DRUPAL_TEST_CHROMEDRIVER_AUTOSTART=false
```
You can copy/paste this section to the bottom of your `.env` file.


## Documentation

To help with the documentation, please run:
  ```bash
  git clone git@github.com:thunder/thunder-distribution.git
  cd thunder-distribution
  npm install
  npm run docs:dev
  ```
This will serve the docs server at [http://localhost:8080](http://localhost:8080).
