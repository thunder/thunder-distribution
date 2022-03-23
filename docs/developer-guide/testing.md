# Testing

Thunder comes with an extensive set of tests and base classes, and you can benefit from that.

## Write tests

Thunder provides two base classes, `ThunderTestBase.php` for functional browser tests and
`ThunderJavascriptTestBase.php` for javascript tests. If you want to write tests for your project, you should use these
classes.

There are also some traits, that provides useful functionalities:

- `ThunderTestTrait.php`
- `ThunderJavascriptTrait.php`
- `ThunderArticleTestTrait.php`
- `ThunderCkEditorTestTrait.php`
- `ThunderEntityBrowserTestTrait.php`
- `ThunderFormFieldTestTrait.php`
- `ThunderMediaTestTrait.php`
- `ThunderMetaTagTrait.php`
- `ThunderParagraphsTestTrait.php`
- `ThunderGqlsTestTrait.php`

### Use thunder_test_mock_request for external requests

With the help of the `thunder_test_mock_request` test module, it's easy to mock external requests and make your tests
more stable and reliable.

You just have to define the response for a request URL.

<!-- markdownlint-disable MD013 -->

```php
Drupal\thunder_test_mock_request\MockHttpClientMiddleware::addUrlResponse('https://oembed.com/providers.json', '/path/to/myresponse.json', ['Content-Type' => 'application/json']);
```

<!-- markdownlint-enable MD013 -->

**Note:** If `thunder_test_mock_request` is enabled, all external requests have to be mocked.

## Run tests

Running tests for a project isn't that easy. Since all your tests have the `$profile` variable set to 'thunder', the
test runs against a plain Thunder installation. So these test sites, wouldn't contain any configuration changes from
your project.

To prevent that, `ThunderTestTrait.php` can receive a database dump that is installed before the test
runs.

### Create database dump

Before you create the database dump, you should install your site from configuration to have an empty site without any
content.

```bash
drush si --existing-config
```

After that create the database dump:

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
