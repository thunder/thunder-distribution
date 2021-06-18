<?php

namespace Drupal\Tests\thunder\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\thunder\Traits\ThunderTestTrait;

/**
 * The base class for all functional Thunder tests.
 */
abstract class ThunderTestBase extends BrowserTestBase {

  use ThunderTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'thunder';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_test_mock_request',
  ];

}
