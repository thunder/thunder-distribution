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
  protected $screenshotDirectory = '/tmp/thunder-travis-ci';

  /**
   * Default user login role used for testing.
   *
   * @var string
   */
  protected static $defaultUserRole = 'editor';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->logWithRole(static::$defaultUserRole);

    $instagram = $this->config('media_entity_instagram.settings');
    $instagram->set('facebook_app_id', 123)
      ->set('facebook_app_secret', 123)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage() {
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
   * Wait for images to load.
   *
   * This functionality is sometimes need, because positions of elements can be
   * changed in middle of execution and make problems with execution of clicks
   * or other position depending actions. Image property complete is used.
   *
   * @param string $cssSelector
   *   Css selector, but without single quotes.
   * @param int $total
   *   Total number of images that should selected with provided css selector.
   * @param int $time
   *   Waiting time, by default 10sec.
   */
  public function waitForImages($cssSelector, $total, $time = 10000) {
    $this->getSession()
      ->wait($time, "jQuery('{$cssSelector}').filter(function(){return jQuery(this).prop('complete');}).length === {$total}");
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
   * Fill CKEditor field.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param string $text
   *   Text that will be filled into CKEditor.
   */
  public function fillCkEditor($ckEditorCssSelector, $text) {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);

    $this->getSession()
      ->getDriver()
      ->executeScript("CKEDITOR.instances[\"$ckEditorId\"].insertHtml(\"$text\");");
  }

  /**
   * Select CKEditor element.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param int $childIndex
   *   The child index under the node.
   */
  public function selectCkEditorElement($ckEditorCssSelector, $childIndex) {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);

    $this->getSession()
      ->getDriver()
      ->executeScript("let selection = CKEDITOR.instances[\"$ckEditorId\"].getSelection(); selection.selectElement(selection.root.getChild($childIndex)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
  }

  /**
   * Assert that CKEditor instance contains correct data.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param string $expectedContent
   *   The expected content.
   */
  public function assertCkEditorContent($ckEditorCssSelector, $expectedContent) {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);
    $ckEditorContent = $this->getSession()
      ->getDriver()
      ->evaluateScript("return CKEDITOR.instances[\"$ckEditorId\"].getData();");

    static::assertEquals($expectedContent, $ckEditorContent);
  }

  /**
   * Click article save.
   */
  protected function clickSave() {
    $driver = $this->getSession()->getDriver();

    $driver->click('//div[@data-drupal-selector="edit-actions"]/input[@id="edit-submit"]');
  }

  /**
   * Get CKEditor id from css selector.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   *
   * @return string
   *   CKEditor ID.
   */
  protected function getCkEditorId($ckEditorCssSelector) {
    // Since CKEditor requires some time to initialize, we are going to wait for
    // CKEditor instance to be ready before we continue and return ID.
    $this->getSession()->wait(10000, "(waitForCk = CKEDITOR.instances[jQuery(\"{$ckEditorCssSelector}\").attr('id')]) && waitForCk.instanceReady");

    $ckEditor = $this->getSession()->getPage()->find(
      'css',
      $ckEditorCssSelector
    );

    return $ckEditor->getAttribute('id');
  }

}
