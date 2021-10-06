<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait for handling of Media related test actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderMediaTestTrait {

  /**
   * Select Medias for field.
   *
   * @param string $fieldName
   *   Field name.
   * @param array $medias
   *   List of media identifiers.
   */
  public function selectMedia($fieldName, array $medias) {

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $button_selector = '[data-drupal-selector="edit-' . str_replace('_', '-', $fieldName) . '"] .media-library-open-button';
    $this->scrollElementInView($button_selector);
    $page->find('css', $button_selector)->press();

    $this->assertWaitOnAjaxRequest();

    foreach ($medias as $media) {
      $page->find('css', "div.media-library-views-form__rows input[value='$media']")->click();
    }

    $page->find('css', '.media-library-widget-modal .form-actions button')->click();

    $this->assertWaitOnAjaxRequest();
    $this->waitUntilVisible('div[data-drupal-selector="edit-' . str_replace('_', '-', $fieldName) . '-wrapper"] img');
  }

  /**
   * Create gallery with selected medias.
   *
   * @param string $name
   *   Name of gallery.
   * @param string $fieldName
   *   Field name.
   * @param array $medias
   *   List of media identifiers.
   */
  public function createGallery($name, $fieldName, array $medias) {

    $page = $this->getSession()->getPage();

    $selector = "input[data-drupal-selector='edit-" . str_replace('_', '-', $fieldName) . "-0-inline-entity-form-name-0-value']";
    $this->assertSession()->elementExists('css', $selector);

    $nameField = $page->find('css', $selector);
    $nameField->setValue($name);

    $this->selectMedia("{$fieldName}_0_inline_entity_form_field_media_images", $medias);
  }

}
