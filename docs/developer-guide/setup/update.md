# Update

To update Thunder or any module to the newest version, constrained by the specified version in `composer.json`, execute `composer update`. This command will check every dependency for a new version, downloads it and updates it accordingly.

You should execute database updates after that.

## UI

After you have updated your code with `composer` command, you can go to your site page `/update.php` and follow instructions to update your site database.

## CLI

To update the database in the command line, you need to have [drush](http://docs.drush.org/en/master/install) installed.

You can run `drush` command in the `docroot` folder of your site to update the database of your site like this:
```
$ drush updb
```
