<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\thunder\Traits\ThunderTestTrait;

/**
 * Base class for Thunder Javascript functional tests.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
abstract class ThunderJavascriptTestBase extends WebDriverTestBase {

  use ThunderJavascriptTrait;
  use ThunderTestTrait;
  use StringTranslationTrait;

  /**
   * Keep CSS animations enabled for JavaScript tests.
   *
   * @var bool
   */
  protected $disableCssAnimations = FALSE;

  /**
   * Modules to enable.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. It is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\BrowserTestBase::installDrupal()
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_news_article',
    'thunder_test_mock_request',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'thunder';

  /**
   * Directory path for saving screenshots.
   *
   * @var string
   */
  protected string $screenshotDirectory = '/tmp/thunder-screenshots';

  /**
   * Default user login role used for testing.
   *
   * @var string
   */
  protected static string $defaultUserRole = 'editor';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logWithRole(static::$defaultUserRole);

    $instagram = $this->config('media_entity_instagram.settings');
    $instagram->set('facebook_app_id', 123)
      ->set('facebook_app_secret', 123)
      ->save();

    $autosave_form = $this->config('autosave_form.settings');
    $autosave_form->set('notification.active', FALSE)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage(): void {
    parent::initFrontPage();
    // Set a standard window size so that all javascript tests start with the
    // same viewport.
    $windowSize = $this->getWindowSize();
    $this->getSession()->resizeWindow($windowSize['width'], $windowSize['height']);
  }

  /**
   * Get base window size.
   *
   * @return array
   *   Return
   */
  protected function getWindowSize() {
    return [
      'width' => 1280,
      'height' => 768,
    ];
  }

  /**
   * Get directory for saving of screenshots.
   *
   * Directory will be created if it does not already exist.
   *
   * @return string
   *   Return directory path to store screenshots.
   *
   * @throws \Exception
   */
  protected function getScreenshotFolder() {
    $dir = $this->screenshotDirectory;

    if (!is_dir($dir)) {
      if (mkdir($dir, 0777, TRUE) === FALSE) {
        throw new \Exception('Unable to create directory: ' . $dir);
      }
    }

    return realpath($dir);
  }

  /**
   * Content type provider for node tests.
   *
   * @return array
   *   Return array of content types arrays. The first element is the content
   *   type, the second argument is the display name of the content type.
   */
  public static function providerContentTypes(): array {
    return [
      'Content type "Article"' => ['article', 'Article'],
      'Content type "News Article"' => ['news_article', 'News Article'],
    ];
  }

}
