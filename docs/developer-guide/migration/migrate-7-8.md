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

Before you start with the code and database update please add the Slick module, the Admin Toolbar, Blazy, Select2
(replaced by Tagify) and the Responsive Preview module to your own composer.json. All are no longer part of Thunder and
can be removed after the update was successfully executed if you do not need them anymore.

```bash
composer require drupal/admin_toolbar drupal/blazy drupal/select2 drupal/slick drupal/responsive_preview
composer require npm-asset/blazy npm-asset/slick-carousel npm-asset/select2
```

Also, if you have jquery_ui, jquery_ui_draggable, default_content or paragraphs_paste enabled, you have to either
uninstall them prior to the update or require them own your own, since Thunder removed them from the distribution.
We cannot guarantee, that those modules will work with Drupal 11!

Even if you continue to use paragraphs_paste, you have to disable thunder_paragrphs_paste module before the update.

```bash
composer require drupal/jquery_ui
composer require drupal/jquery_ui_draggable
composer require drupal/default_content
composer require drupal/paragraphs_paste
```
