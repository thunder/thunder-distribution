<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait with support for handling Entity Browser actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderEntityBrowserTestTrait {

  use ThunderJavascriptTrait;

  /**
   * Open modal entity browser.
   *
   * @param string $fieldName
   *   Field name.
   */
  public function openEntityBrowser(string $fieldName): void {
    $button_selector = '[data-drupal-selector="edit-' . str_replace('_', '-', $fieldName) . '"] .media-library-open-button';
    $this->clickCssSelector($button_selector);
  }

  /**
   * Submit changes in modal entity browser.
   */
  public function submitEntityBrowser(): void {
    $this->clickCssSelector('.media-library-widget-modal .form-actions button');

    $this->assertWaitOnAjaxRequest();
  }

  /**
   * Upload file inside entity browser.
   *
   * NOTE: It will search for first tab with upload widget and file will be
   * uploaded there. Upload is done over input file field and it has to be
   * visible for selenium to work.
   *
   * @param string $filePath
   *   Path to file that should be uploaded.
   *
   * @throws \Exception
   */
  public function uploadFile(string $filePath): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $fileFieldSelector = "input[type='file'].dz-hidden-input";
    $fileField = $page->find('css', $fileFieldSelector);

    if (empty($fileField)) {
      throw new \Exception(
        sprintf(
          'The drop-down file field was not found on the page %s',
          $this->getSession()->getCurrentUrl()
        )
      );
    }

    // Make file field visible and isolate possible problems with "multiple".
    $this->getSession()
      ->executeScript('jQuery("' . $fileFieldSelector . '").show(0).css("visibility","visible").width(200).height(30).removeAttr("multiple");');

    $f = '/Users/d430774/Sites/thunder/thunder-develop/docroot/profiles/contrib/thunder/tests';
    $fileField->attachFile($f . $filePath);

    $this->assertWaitOnAjaxRequest();

    // Wait up to 10 sec that "Use selected" button is active.
    $this->getSession()->wait(
      10000,
      '(typeof jQuery === "undefined" || !jQuery(\'input[name="op"]\').is(":disabled"))'
    );

    $this->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');

    $this->assertWaitOnAjaxRequest();
  }

}
