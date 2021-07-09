# Testing

Thunder comes with an extensive set of tests and base classes, and you can benefit from that.

## Write tests

Thunder provides two base classes, `ThunderTestBase.php` for functional browser tests and
`ThunderJavascriptTestBase.php` for javascript tests. If you want to write tests for your project, you should use these
classes.

There are also some traits, that provides useful functionalities:

- `ThunderTestTrait.php`
- `ThunderArticleTestTrait.php`
- `ThunderEntityBrowserTestTrait.php`
- `ThunderFormFieldTestTrait.php`
- `ThunderMediaTestTrait.php`
- `ThunderMetaTagTrait.php`
- `ThunderParagraphsTestTrait.php`

## Run tests

Running tests for a project isn't that easy. Drupal installs a site, where the test runs onto before every test run.
Because all your tests are having the `$profile` variable set the 'thunder', Drupal would install a fresh Thunder before
every test run. So these test sites, wouldn't contain any of your configuration changes.

To prevent that, `ThunderTestTrait.php` can receive a database dump that is installed before the test
runs.

### Create database dump

Before you create the database dump, you should install your site from configuration to have an empty site without any
content.

```bash
drush si --existing-config
```

Then you can create the database dump:

```bash
cd docroot
php ./core/scripts/db-tools.php dump-database-d8-mysql | gzip > thunder.sql.gz
```

### Execute tests

Before you execute your test, you have to set the `thunderDumpFile` env variable.

```bash
export thunderDumpFile=/path/to/thunder.sql.gz
```

Now you can execute your test, as described in the
official [Drupal documentation](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal)
