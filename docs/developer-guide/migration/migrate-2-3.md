# Update Thunder 2 -> Thunder 3

## Prerequisites

These are the instructions to manually update your existing Thunder 2 installation to Thunder 3. If you want to do a fresh installation of thunder please visit [install Thunder](../setup.md#install-thunder). The most
significant change is the migration to media in core. But we also made
some changes to our composer.json.

You have to make sure, that your Thunder 2 project and all it's dependecies,
are fully updated to the most current versions. Run the following command in your docroot:

```bash
drush ev "print drupal_get_installed_schema_version('thunder') . PHP_EOL;"
```
This should print the number 8138 or greater. If that is not the case, update your project.

```bash
cd ..
composer update
```
This should update to Thunder 8.2.47 or greater.

Now run database updates:
```bash
cd docroot
drush updb
```
You should at least see the thunder 8138 schema update. If not, double check that the correct version of Thunder is installed, and that `drush updb` did not throw any errors.

## Composer adjustments

We switched from bower-asset to npm-asset for our frontend-libraries.
In order to get the libraries downloaded to the correct location, please
replace
```json
"installer-types": ["bower-asset"],
```
by
```json
"installer-types": ["bower-asset", "npm-asset"],
```
in the composer.json of your project and add "type:npm-asset" to the "docroot/libraries/{$name}" section in installer-paths.


We moved the composer package under the thunder namespace, so remove the old package and a the new one.

```bash
composer remove burdamagazinorg/thunder
composer require "thunder/thunder-distribution:~3.3" --no-update
```

You have to update composer now.

```bash
composer update
```

We removed some modules from our codebase. In case you are using one of
below mentioned modules please require them manually for your project.

```bash
composer require drupal/views_load_more --no-update
composer require drupal/breakpoint_js_settings --no-update
composer require valiton/harbourmaster --no-update
composer require drupal/riddle_marketplace:~3.0 --no-update
composer require drupal/nexx_integration:~3.0 --no-update
composer require burdamagazinorg/infinite_module:~1.0 --no-update
composer require burdamagazinorg/infinite_theme:~1.0 --no-update
```

## Update Facebook instant articles integration

In case you are using the fb_instant_articles module, please note that the RSS feed url will change
and therefore needs to be updated in the facebook account.

When updating while fb_instant_articles is enabled, there will be an error message like `The "fiafields" plugin does not exist. Valid plugin IDs for Drupal\views\Plugin\ViewsPluginManager are: ...`
this is due to invalid configuration present in the system before the update and can safely be ignored.

## Pre-requirements for media update

First we should make sure that the latest drush version is installed.
```bash
composer require drush/drush:~10.0 --no-update
```

After that the following steps should be done for the update:

```bash
composer require drupal/media_entity:^2.0 drupal/media_entity_image drupal/video_embed_field:^2.2
```

* Make sure that you use the "Media in core" branch for all your
  media_* modules. (For the media modules in Thunder, we take care of that)
* Make sure that all your code that uses media_entity API is modified to use the core media API.

See here for more information:
* [Moved a refined version of the contributed Media entity module to core as Media module](https://www.drupal.org/node/2863992)
* [FAQ - Transition from Media Entity to Media in core](https://www.drupal.org/docs/8/core/modules/media/faq-transition-from-media-entity-to-media-in-core#upgrade-instructions-from-media-entity-contrib-to-media-in-core)

## Execute media update

All you need to do now is:

```bash
drush updb
drush cr
```

## Cleanup codebase

Now the update is done and you can remove some modules from your project.
```bash
composer remove drupal/media_entity drupal/media_entity_image
```
