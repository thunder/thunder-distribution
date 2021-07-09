# Setup Thunder

## Install Thunder

### System requirements

You have to install `PHP`, `composer`, and `git` on your computer before you can install Thunder. The `composer`
requires `git` command for proper functioning.

To install `PHP` please take a look at the
official [Installation and Configuration](https://www.php.net/manual/install.php) for `PHP`. On top of `PHP`, you need
to install the required libraries. The Thunder installation requires at least all the libraries Drupal requires.
Extensions used by Drupal core are defined in Core's composer.json file - see for example
the [file for Drupal 9.1.x](https://git.drupalcode.org/project/drupal/blob/9.1.x/core/composer.json). Look at the
"require" section and the keys starting with "ext-".

The installation of `PHP` extensions can differ between operating systems, that's why you should check for detailed
instructions on `PHP` [Installation and Configuration](https://www.php.net/manual/install.php).

To install `composer`, you can check the `composer` [installation instructions](https://getcomposer.org/download) and
for `git` you can find installation instructions [here](https://git-scm.com/downloads).

### Project setup

To set up a new project, run this in your console to install Thunder from the command line:

```bash
composer create-project thunder/thunder-project thunder --no-interaction --no-install
cd thunder
composer install
```

### Quick start

To have a quick start run the following commands:

```bash
cd docroot
php core/scripts/drupal quick-start thunder
```

**NOTE:** This command is only useful to try Thunder locally, but not to run it in production nor to start a new project
based on Thunder.

### Beyond quick install

To develop your own website based on Thunder, you should have `mysql` installed on your computer.

Then you can install Thunder with:

```bash
drush si thunder
```

As a next step, it's recommended to export your config files to a location outside the docroot. To do this change the
location of `config_sync_directory` in `docroot/sites/default/settings.php`.

```php
$settings['config_sync_directory'] = '../config/sync';
```

and export the config files:

```bash
drush cex
```

Now you should initialize a git repository for your project and commit all the files:

```bash
git init . -b development
git add .
git commit -m"Initial commit"
```

From that point, you are ready to develop your new website based on Thunder.

For any further information on how to run and maintain your installation please refer to
[the Drupal User Guide](https://www.drupal.org/docs/user_guide/en/index.html).

## Update

Updating Thunder consists of three parts. First needs the code to be updated, and after that, it's required to update
the database and then export the changes.

### Code update

To update Thunder or any module to the newest version, constrained by the specified version in `composer.json`, use
composer. The following command will check every dependency for a new version, downloads it, and updates it accordingly.

```bash
composer update
```

### Database update

#### Update over UI

After you have updated your code with `composer` command, you can go to your site page `/update.php` and follow
instructions to update your site database.

#### Update with the command line

To update the database in the command line, you need to have [drush](http://docs.drush.org/en/master/install) installed.

You can run `drush` command in the `docroot` folder of your site to update the database of your site like this:

```bash
drush updb
```

### Configuration export

After the database was updated, it's necessary to export the changes to your configuration files.

```bash
drush cex
```

## Extend

### Find extensions

You can find extensions on [Drupal.org](https://www.drupal.org).
On [the following page](https://www.drupal.org/project/project_module?f%5B3%5D=drupal_core%3A7234), you can search
for `Modules` and on [this page](https://www.drupal.org/project/project_theme?f%5B2%5D=drupal_core%3A7234) for `Themes`.
You can find further information on extensions
in [the Drupal User Guide - Extending and Customizing Your Site](https://www.drupal.org/docs/user_guide/en/extend-chapter.html)
.

If you know the name of the extension you are looking for, the fastest way is to search it using Google or any other
search engine, adding `drupal` to the search. For example: `drupal webform`

### Add extension

Using 'composer', you can also manage the dependencies of your Thunder site and extensions.

To add an extension to your project, go to the root of your site (there should be a `composer.json` file) and add
modules by typing

```bash
composer require drupal/[short name of the extension]
```

into the command line.

For example:

```bash
composer require drupal/webform
```

### Install extension

You can install extensions via the UI or the command line.

#### Install over UI

You can install modules by going to your site page `admin/modules`, or by clicking on `Extend` in the menu at the top.
Here you can search for the module already added to your project by entering the name in the filter box at the top. To
install a module, select the checkbox next to it, scroll to the bottom, and click `Install`. You might be warned
that another module needs to be enabled because it is required for the module of your interest. By clicking
on `continue`, Thunder will take care of that.

You can install themes by going to your site page `admin/appearance`, or by clicking on `Appearance` in the menu at the
top. Here you can scroll to the theme you would like to install and click on `Install and set as default` to directly
use the theme for your site.

#### Install with the command line

To install a theme or module at the command line, you need to have [drush](http://docs.drush.org/en/master/install)
installed.

You can run `drush` command in the `docroot` folder of your site to install a module like this:

```bash
drush en [module]
```

And to install a theme, you can run `drush` command like this:

```bash
drush en [theme]
```

To use a theme, you still have to navigate to `Appearance` (admin/appearance) and set it as default.

### Uninstall extension

To uninstall an extension, you have to uninstall it first and then remove the code. You can uninstall extensions via the
UI or the command line, but to remove the code from your project, you have to use `composer`.

#### Uninstall in UI

You can uninstall modules by going to your site page `admin/modules`, or by clicking on `Extend` in the menu at the top
and then by clicking on `Uninstall` tab. Here you can search for the module by entering the name in the filter box at
the top. To uninstall a module, select the checkbox next to it, scroll to the bottom, and click `Uninstall`. You might be
warned that another module needs to be uninstalled because it depends on the module that you want to remove. By clicking
on `continue`, Thunder will take care of that.

You can uninstall themes by going to your site page `admin/appearance`, or by clicking on `Appearance` in the menu at
the top. Here you can scroll to the theme you would like to uninstall. If your site is using that theme as default, you
have to select another default theme before you can uninstall it. When your site is not using that theme as default, you
can click `Uninstall` next to it to uninstall it.

#### Uninstall with command line

To uninstall a theme or module at the command line, you need to have [drush](http://docs.drush.org/en/master/install)
installed.

After that, you can run `drush` command in the `docroot` folder of your site to uninstall a module like this:

```bash
drush pm:uninstall [module]
```

To uninstall a theme, you still have to select another theme as default. It's explained in `Uninstall in UI` part.

And then you to uninstall a theme, you can run `drush` command like this:

```bash
drush theme:uninstall [theme]
```

### Remove extension

After you have uninstalled an extension from your site, you can also remove the code from your project.

If you want to remove a module, that was provided by Thunder, you will have to add it to your composer.json file in the
replace block. Modules, that you added yourself by the above commands, do not have to be placed there.

```json
"replace": {
  "drupal/google_analytics": "*"
}
```

For more information on using the composer replace check the
official [composer documentation](https://getcomposer.org/doc/04-schema.md#replace)

To remove code you can execute a command like this:

```bash
composer remove drupal/[short name of the extension]
```
