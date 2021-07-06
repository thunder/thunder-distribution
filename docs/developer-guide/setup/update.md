# Update

Updating Thunder consists of three parts. First needs the code to be updated, and after that, it's required to update the database and then export the changes.

## Code update

To update Thunder or any module to the newest version, constrained by the specified version in `composer.json`, use composer. The following command will check every dependency for a new version, downloads it, and updates it accordingly.
```bash
composer update
```
## Database update

### UI

After you have updated your code with `composer` command, you can go to your site page `/update.php` and follow instructions to update your site database.

### CLI

To update the database in the command line, you need to have [drush](http://docs.drush.org/en/master/install) installed.

You can run `drush` command in the `docroot` folder of your site to update the database of your site like this:
```bash
drush updb
```

## Configuration export

After the database was updated, it's necessary to export the changes to your configuration files.
```bash
drush cex
```
