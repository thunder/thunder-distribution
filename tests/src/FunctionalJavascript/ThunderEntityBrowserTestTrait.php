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
   * Open modal entity browser and switch into iframe from it.
   *
   * @param string $drupalSelector
   *   Drupal selector.
   * @param string $entityBrowser
   *   Entity browser name.
   */
  public function openEntityBrowser(string $drupalSelector, string $entityBrowser): void {
    $this->clickDrupalSelector($drupalSelector . '-entity-browser-entity-browser-open-modal');
    $this->assertWaitOnAjaxRequest();

    $this->getSession()
      ->switchToIFrame('entity_browser_iframe_' . $entityBrowser);

    // Wait that iframe is loaded and jQuery is available.
    $this->getSession()->wait(10000, '(typeof jQuery !== "undefined")');

    $this->assertWaitOnAjaxRequest();
  }

  /**
   * Submit changes in modal entity browser.
   *
   * @param string $entityBrowser
   *   Entity browser name.
   */
  public function submitEntityBrowser(string $entityBrowser): void {
    $page = $this->getSession()->getPage();
    if ($entityBrowser == 'multiple_image_browser') {
      $this->getSession()->wait(200);
      $this->assertWaitOnAjaxRequest();

      $page->pressButton('Use selected');
    }
    else {
      $page->pressButton('edit-submit');
    }

    $this->getSession()->switchToIFrame();
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

    // Click all tabs until we find upload Tab.
    $tabLinks = $page->findAll('css', '.eb-tabs a');
    if (empty($tabLinks)) {
      throw new \Exception(
        sprintf(
          'Unable to find tabs in entity browser iframe on page %s',
          $this->getSession()->getCurrentUrl()
        )
      );
    }

    // Click all tabs until input file field for upload is found.
    $fileFieldSelector = "input[type='file'].dz-hidden-input";
    $fileField = NULL;
    foreach ($tabLinks as $tabLink) {
      /** @var \Behat\Mink\Element\NodeElement $tabLink */
      $tabLink->click();
      $this->assertWaitOnAjaxRequest();

      $fileField = $page->find('css', $fileFieldSelector);

      if (!empty($fileField)) {
        break;
      }
    }

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

    $fileField->attachFile($filePath);

    $this->assertWaitOnAjaxRequest();

    // Wait up to 10 sec that "Use selected" button is active.
    $this->getSession()->wait(
      10000,
      '(typeof jQuery === "undefined" || !jQuery(\'input[name="op"]\').is(":disabled"))'
    );

    $this->assertWaitOnAjaxRequest();

    // In case of gallery image upload we should wait additionally so that all
    // command for auto selection are executed.
    $this->getSession()->wait(200);
    $this->assertWaitOnAjaxRequest();
  }

}
