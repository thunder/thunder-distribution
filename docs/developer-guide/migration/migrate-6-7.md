# Update Thunder 6 -> Thunder 7

## Prerequisites

These are the instructions to manually update your existing Thunder 6 installation to Thunder 7. If
you want to do a fresh installation of thunder please visit [install Thunder](../setup.md#install-thunder).

**The most important changes are that we switched from our own "Thunder Admin" theme to the Gin theme and also replaced
the Entity Browser module by the Media Library that is provided by Drupal core.**

You have to make sure that your Thunder 6 project and all its dependencies are fully updated to the most current
versions. Run the following command in your docroot:

```bash
drush ev "print drupal_get_installed_schema_version('thunder') . PHP_EOL;"
```

@TODO This should print the number 8323 or greater. If that is not the case, update your project.

```bash
cd ..
composer update
```

This should update to Thunder 6.3 or greater.

Now run database updates:

```bash
cd docroot
drush updb
```

@TODO You should at least see the Thunder 8323 schema update. If not, double check that the correct version of Thunder
is installed, and that `drush updb` did not throw any errors.

Before you start with the code and database update please add the Entity Browser module and the Thunder Admin theme to
your own composer.json. Both are no longer part of Thunder and can be removed after the update was successfully
executed.

```bash
composer require drupal/entity_browser drupal/thunder_admin
```

Also, if you are using the liveblog, better_normalizers, google_analytics or adsense module, you have to require them
own your own, since Thunder removed them from the distribution.

```bash
composer require drupal/liveblog
composer require drupal/better_normalizers
composer require drupal/adsense
composer require drupal/google_analytics
```

## Execute the update

All you need to do now is:

```bash
composer require thunder/thunder-distribution:~7.0@STABLE --no-update
composer update

drush updb
drush cr
```

After the update was executed successfully, you can remove the outdated extensions.

```bash
composer remove drupal/entity_browser drupal/thunder_admin
```
