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

This should print the number 8326 or greater. If that is not the case, update your project.

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

You should at least see the Thunder 8326 schema update. If not, double check that the correct version of Thunder
is installed, and that `drush updb` did not throw any errors.

Before you start with the code and database update please add the Entity Browser module, the Shariff module, and the
Thunder Admin theme to your own composer.json. Both are no longer part of Thunder and can be removed after the update
was successfully executed.
Also the ckeditor module and the seven theme are needed for the update process.

```bash
composer require drupal/entity_browser drupal/thunder_admin drupal/shariff drupal/ckeditor drupal/seven
```

Also, if you have the liveblog, better_normalizers, google_analytics, ctools or adsense module enabled, you have to
require them own your own, since Thunder removed them from the distribution.

```bash
composer require drupal/liveblog
composer require drupal/better_normalizers
composer require drupal/adsense
composer require drupal/google_analytics
composer require drupal/ctools
```

Thunder's new default frontend theme is Olivero, and we removed Thunder Base which was based on the Bartik theme, which
was removed from Drupal 10 as well. In case you use Thunder Base as your theme, you have to switch to a different one
before updating. We recommend switching to Olivero as well. If you use the breakpoint settings, that the Thunder Base
Theme provided, you have to configure them in another theme under your control and adjust your configuration
accordingly. After switching to the new theme, you have to disable the Thunder Base theme.

```bash
drush config-set system.theme default olivero
drush pm-uninstall thunder_base
```

In Thunder 7 we also upgraded the simple_sitemap module from version 3 to version 4. If your project has custom plugins,
you have to update them to the new version. See the [simple_sitemap documentation](https://gbyte.dev/blog/simple-xml-sitemap-4-0-has-been-released?language_content_entity=und).

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
