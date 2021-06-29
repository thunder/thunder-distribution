# Update Thunder 3 -> Thunder 6

## Prerequisites

These are the instructions to manually update your existing Thunder 3 installation to Thunder 6 including Drupal 9. If you want to do a fresh installation of thunder please visit [install Thunder](https://thunder.github.io/thunder-documentation/quick-install).

**The most important change is that we removed the support for AMP and Facebook Instant Articles, because none of them support Drupal 9 currently. If you rely on one of these modules, we recommend that you stay on Thunder 3.5 for now.**

You have to make sure that your Thunder 3 project and all its dependencies
are fully updated to the most current versions. Run the following command in your docroot:

```
drush ev "print drupal_get_installed_schema_version('thunder') . PHP_EOL;"
```
This should print the number 8309 or greater. If that is not the case, update your project.

```
cd ..
composer update
```
This should update to Thunder 8.3.5 or greater.

Now run database updates:
```
cd docroot
drush updb
```
You should at least see the Thunder 8308 schema update. If not, double check that the correct version of Thunder is installed, and that `drush updb` did not throw any errors.

Before you start with the code and database update please disable the libraries module. The libraries module is not Drupal 9 compatible and
its most used functionality is now part of Drupal core. It is therefore not needed anymore. If you use other contrib modules that require the libraries module, please check if these modules support the new core functionality.
See [https://www.drupal.org/node/3099614](https://www.drupal.org/node/3099614)

## Drupal 9 compatibility

Because Thunder 6 is based on Drupal 9, check your compatibility before your run the upgrade.

Drupal.org provides a good documentation for that: [https://www.drupal.org/docs/upgrading-drupal/how-to-prepare-your-drupal-7-or-8-site-for-drupal-9](https://www.drupal.org/docs/upgrading-drupal/how-to-prepare-your-drupal-7-or-8-site-for-drupal-9)


### Check compatibility by running tests

If your test suite is based on Drupal tests, e.g. Functional or FunctionalJavascript, you can configure your tests to fail
when calling deprecated code.

Add the following line to your phpunit.xml file and run your tests.

```
    <env name="SYMFONY_DEPRECATIONS_HELPER" value="strict"/>
```

If your test suite is based on Behat, [https://github.com/caciobanu/behat-deprecation-extension](https://github.com/caciobanu/behat-deprecation-extension) might be interesting for finding deprecated code.

## Composer adjustments

We removed some modules from our codebase. If you are using one of
below mentioned modules, please require them manually for your project.

```
composer require drupal/entity --no-update

```

## Execute the update

All you need to do now is:

```
composer require thunder/thunder-distribution:~6.0@STABLE --no-update
composer update

drush updb
drush cr
```


