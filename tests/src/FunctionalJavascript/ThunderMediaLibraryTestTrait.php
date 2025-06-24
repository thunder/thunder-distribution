<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait with support for handling media library actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderMediaLibraryTestTrait {

  use ThunderJavascriptTrait;

  /**
   * Open media library modal.
   *
   * @param string $fieldName
   *   Field name.
   */
  public function openMediaLibrary(string $fieldName): void {
    $button_selector = '[data-drupal-selector="edit-' . str_replace('_', '-', $fieldName) . '"] .media-library-open-button';
    $this->clickCssSelector($button_selector);
  }

  /**
   * Submit changes in media library modal.
   */
  public function submitMediaLibrary(): void {
    $this->clickCssSelector('.media-library-widget-modal .form-actions button');

    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Upload file inside media library.
   *
   * NOTE: It will search for first tab with upload widget and file will be
   * uploaded there. Upload is done over input file field and it has to be
   * visible for selenium to work.
   *
   * @param string $filePath
   *   Path to file that should be uploaded.
   * @param bool $skipEditForm
   *   If set to TRUE, it will skip edit form will just select uploaded files.
   *
   * @throws \Exception
   */
  public function uploadFile(string $filePath, bool $skipEditForm = FALSE): void {
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

    $expected_file_count = count($this->getSession()->getPage()->findAll('css', '.js-media-library-item')) + 1;

    // Make file field visible and isolate possible problems with "multiple".
    $this->getSession()
      ->executeScript('jQuery("' . $fileFieldSelector . '").show(0).css("visibility","visible").width(200).height(30).removeAttr("multiple");');

    $fileField->attachFile($filePath);

    $this->assertWaitOnAjaxRequest();

    // Wait up to 10 sec that "Use selected" button is active.
    $this->getSession()->wait(
      10000,
      '(typeof jQuery === "undefined" || !jQuery(\'input[name="op"]\').is(":disabled"))'
    );

    if (!$skipEditForm) {
      $this->assertSession()->waitForButton('Save and select');
      // The button is not interactable via webdriver.
      $this->getSession()->executeScript("document.querySelector('.ui-dialog-buttonset button').click()");
    }

    // Wait up to 10 sec for the item count to be updated.
    $this->getSession()->wait(
      10000,
      "document.querySelectorAll('.js-media-library-item').length === {$expected_file_count}"
    );
    $this->assertSession()->elementsCount('css', '.js-media-library-item', $expected_file_count);
  }

}
