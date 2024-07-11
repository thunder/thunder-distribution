# Update Thunder 7 -> Thunder 8

## Prerequisites

These are the instructions to manually update your existing Thunder 7 installation to Thunder 8. If
you want to do a fresh installation of thunder please visit [install Thunder](../setup.md#install-thunder).

You have to make sure that your Thunder 7 project and all its dependencies are fully updated to the most current
versions. Run the following command in your docroot:

```bash
drush ev "print drupal_get_installed_schema_version('thunder') . PHP_EOL;"
```

This should print the number XXXX or greater. If that is not the case, update your project.

```bash
cd ..
composer update
```

This should update to Thunder 7.3 or greater.

Now run database updates:

```bash
cd docroot
drush updb
```

You should at least see the Thunder XXXX schema update. If not, double check that the correct version of Thunder
is installed, and that `drush updb` did not throw any errors.

In this release, we have removed a couple of modules from the Thunder distribution. If you use one or more of these
modules you have to require them manually. The following modules have been removed:

- [jQuery UI Draggable](https://www.drupal.org/project/jquery_ui_draggable)
- [Default content](https://www.drupal.org/project/default_content)

To require these modules, run the following commands:

```bash
composer require drupal/jquery_ui_draggable
composer require drupal/default_content
```
