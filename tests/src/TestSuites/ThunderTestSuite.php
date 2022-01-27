<?php

namespace Drupal\Tests\TestSuites;

use Drupal\Core\Test\TestDiscovery;
use PHPUnit\Framework\TestSuite;

/**
 * Discovers tests for the Thunder test suite.
 *
 * @phpstan-ignore-next-line
 */
class ThunderTestSuite extends TestSuite {

  /**
   * Factory method which loads up a suite with all Thunder tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite(): self {
    $root = dirname(__DIR__, 3);

    $suite = new static('thunder');

    // Extensions' tests will always be in the namespace
    // Drupal\Tests\$extension_name\$suite_namespace\ and be in the
    // $extension_path/tests/src/$suite_namespace directory. Not all extensions
    // will have all kinds of tests.
    $tests = [];
    foreach (drupal_phpunit_find_extension_directories($root) as $extension_name => $dir) {
      foreach (['Functional', 'FunctionalJavascript', 'Kernel'] as $suite_namespace) {
        $test_path = "$dir/tests/src/$suite_namespace";
        if (is_dir($test_path)) {
          $tests += TestDiscovery::scanDirectory("Drupal\\Tests\\$extension_name\\$suite_namespace\\", $test_path);
        }
      }
    }

    if ($chunk = (int) getenv('THUNDER_TEST_CHUNK')) {
      $chunks = array_chunk($tests, (int) ceil(count($tests) / 3));
      var_dump($chunks[$chunk - 1]);
      $suite->addTestFiles($chunks[$chunk - 1]);
    }
    else {
      $suite->addTestFiles($tests);
    }

    return $suite;
  }

}
