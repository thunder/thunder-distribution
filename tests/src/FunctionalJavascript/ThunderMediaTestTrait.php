<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait for handling of Media related test actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderMediaTestTrait {

  use ThunderMediaLibraryTestTrait;
  use ThunderJavascriptTrait;

  /**
   * Select Medias for field.
   *
   * @param string $fieldName
   *   Field name.
   * @param array $medias
   *   List of media identifiers.
   */
  public function selectMedia(string $fieldName, array $medias): void {
    $this->openMediaLibrary($fieldName);

    $this->toggleMedia($medias);

    $this->submitMediaLibrary();
  }

  /**
   * Toggle media items in the library.
   *
   * @param array $medias
   *   List of media identifiers.
   */
  public function toggleMedia(array $medias): void {
    foreach ($medias as $media) {
      // Checkboxes are hidden if replace_checkbox_by_order_indicator is
      // enabled, so we need to show them before every click.
      $this->getSession()
        ->executeScript("document.querySelectorAll('div.media-library-views-form__rows input').forEach(item => { item.style.display = 'block'; })");
      $this->clickCssSelector("div.media-library-views-form__rows input[value=\"$media\"]", FALSE);
    }
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
  public function createGallery(string $name, string $fieldName, array $medias): void {

    $page = $this->getSession()->getPage();

    $selector = "input[data-drupal-selector='edit-" . str_replace('_', '-', $fieldName) . "-0-inline-entity-form-name-0-value']";
    $this->assertSession()->elementExists('css', $selector);

    $nameField = $page->find('css', $selector);
    $nameField->setValue($name);

    $this->selectMedia("{$fieldName}_0_inline_entity_form_field_media_images", $medias);
  }

}
