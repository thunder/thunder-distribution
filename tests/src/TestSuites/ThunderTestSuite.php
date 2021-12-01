<?php

namespace Drupal\Tests\TestSuites;

use Drupal\Core\Test\TestDiscovery;
use PHPUnit\Framework\TestSuite;

/**
 * Discovers tests for the functional test suite.
 */
class ThunderTestSuite extends TestSuite {

  /**
   * Factory method which loads up a suite with all functional tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(__DIR__, 3);

    $suite = new static('thunder');

    // Extensions' tests will always be in the namespace
    // Drupal\Tests\$extension_name\$suite_namespace\ and be in the
    // $extension_path/tests/src/$suite_namespace directory. Not all extensions
    // will have all kinds of tests.
    $tests = [];
    foreach (drupal_phpunit_find_extension_directories($root) as $extension_name => $dir) {
      foreach (['Functional', 'FunctionalJavascript'] as $suite_namespace) {
        $test_path = "$dir/tests/src/$suite_namespace";
        if (is_dir($test_path)) {
          $tests += TestDiscovery::scanDirectory("Drupal\\Tests\\$extension_name\\$suite_namespace\\", $test_path);
        }
      }
    }

    if ($chunk = getenv('THUNDER_TEST_CHUNK')) {
      $chunks = array_chunk($tests, ceil(count($tests) / 4));
      $suite->addTestFiles($chunks[$chunk]);
    }
    else {
      $suite->addTestFiles($tests);
    }

    return $suite;
  }

}
