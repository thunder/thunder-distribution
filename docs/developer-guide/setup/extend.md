# Extend

## Find extensions

You can find extensions on [Drupal.org](https://www.drupal.org). On [the following page](https://www.drupal.org/project/project_module?f%5B3%5D=drupal_core%3A7234), you can search for `Modules` and on [this page](https://www.drupal.org/project/project_theme?f%5B2%5D=drupal_core%3A7234) for `Themes`. You can find further information on extensions in [the Drupal User Guide - Extending and Customizing Your Site](https://www.drupal.org/docs/user_guide/en/extend-chapter.html).

If you know the name of the extension you are looking for, the fastest way is to search it using Google or any other search engine, adding `drupal` to the search. For example: `drupal webform`

## Add extension

Using 'composer', you can also manage the dependencies of your Thunder site and extensions.

To add an extension to your project, go to the root of your site (there should be a `composer.json` file) and add modules by typing
```
$ composer require drupal/[short name of the extension]
```
into the command line.

For example:

```
$ composer require drupal/webform
```

## Install extension

You can install extensions via the UI or the command line.

**UI**

You can install modules by going to your site page `admin/modules`, or by clicking on `Extend` in the menu at the top. Here you can search for the module already added to your project by entering the name in the filter box at the top. To actually install a module, select the checkbox next to it, scroll to the bottom and click `Install`. You might be warned that another module needs to be enabled because it is required for the module of your interest. By clicking on `continue`, Thunder will take care of that.

You can install themes by going to your site page `admin/appearance`, or by clicking on `Appearance` in the menu at the top. Here you can scroll to the theme you would like to install and click on `Install and set as default` to directly use the theme for your site.

**Command line**

To install a theme or module at the command line, you need to have [drush](http://docs.drush.org/en/master/install) installed.

You can run `drush` command in the `docroot` folder of your site to install a module like this:
```
$ drush en [module]
```

And to install a theme, you can run `drush` command like this:
```
$ drush then [theme]
```

To use a theme, you still have to navigate to `Appearance` (admin/appearance) and set it as default.


## Uninstall extension

To uninstall an extension, you have to uninstall it first and then remove the code. You can uninstall extensions via the UI or the command line, but to remove the code from your project, you have to use `composer`.

**Uninstall in UI**

You can uninstall modules by going to your site page `admin/modules`, or by clicking on `Extend` in the menu at the top and then by clicking on `Uninstall` tab. Here you can search for the module by entering the name in the filter box at the top. To uninstall a module, select the checkbox next to it, scroll to the bottom and click `Uninstall`. You might be warned that another module needs to be uninstalled because it depends on the module that you want to remove. By clicking on `continue`, Thunder will take care of that.

You can uninstall themes by going to your site page `admin/appearance`, or by clicking on `Appearance` in the menu at the top. Here you can scroll to the theme you would like to uninstall. If your site is using that theme as default, you have to select another default theme before you can uninstall it. When your site is not using that theme as default, you can click `Uninstall` next to it to uninstall it.

**Uninstall with command line**

To uninstall a theme or module at the command line, you need to have [drush](http://docs.drush.org/en/master/install) installed.

After that, you can run `drush` command in the `docroot` folder of your site to uninstall a module like this:

```
$ drush pm:uninstall [module]
```

To uninstall a theme, you still have to select another theme as default. It's explained in `Uninstall in UI` part.

And then you to uninstall a theme, you can run `drush` command like this:
```
$ drush theme:uninstall [theme]
```


## Remove extension

After you have uninstalled an extension from your site, you can also remove the code from your project.

If you want to remove a module, that was provided by Thunder, you will have to add it to your composer.json file in the
replace block. Modules, that you added yourself by the above commands, do not have to be placed there.

```
 "replace": {
     "drupal/google_analytics": "*"
 }
```

For more information on using the composer replace check the official [composer documentation](https://getcomposer.org/doc/04-schema.md#replace)

To remove code you can execute a command like this:

```
$ composer remove drupal/[short name of the extension]
```
