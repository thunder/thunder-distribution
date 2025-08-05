# Update Thunder 7 -> Thunder 8

## Prerequisites

These instructions cover how to manually upgrade your existing Thunder project to Thunder.
If you're installing Thunder for the first time, please follow the [Thunder installation guide](../setup.md#install-thunder).

Before proceeding, ensure your Thunder 7 project and all dependencies are fully updated:

In your project's docroot, run:

```bash
drush ev "print \Drupal::service('update.update_hook_registry')->getInstalledVersion('thunder') . PHP_EOL;"
```

You should see a version number **8328** or higher. If it's lower, update to the latest Thunder 7 release:

```bash
cd ..
composer update
```

This should update to Thunder 7.4 or greater.

Now run database updates:

```bash
cd docroot
drush updb
```

Confirm that the **Thunder 8328** schema update has been applied. If not, double check that the correct version of Thunder
is installed, and that `drush updb` did not throw any errors.

## Required Module Changes Before Migration

Before you start with the code and database update please add the Slick module, the Admin Toolbar, Blazy, Select2
(replaced by Tagify) and the Responsive Preview module to your own composer.json. These will no longer be bundled with
Thunder and can be removed after the update was successfully executed but may still be required for your project.

```bash
composer require drupal/blazy drupal/select2 drupal/slick drupal/responsive_preview
composer require npm-asset/blazy npm-asset/slick-carousel npm-asset/select2
```

Additionally, if you're using any of the following modules: jquery_ui, jquery_ui_draggable, default_content or
paragraphs_paste, either **uninstall** them before migrating **or** explicitly require them yourself, since they’ve
been removed from the Thunder distribution. These modules may not be compatible with Drupal 11.

To re-add them manually via composer:

```bash
composer require drupal/jquery_ui
composer require drupal/jquery_ui_draggable
composer require drupal/default_content
composer require drupal/paragraphs_paste
```

If you continue using paragraphs_paste, be sure to **disable** the thunder_paragraphs_paste sub-module before upgrading.
