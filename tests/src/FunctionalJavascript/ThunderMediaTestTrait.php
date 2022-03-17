<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait for handling of Media related test actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderMediaTestTrait {

  use ThunderEntityBrowserTestTrait;
  use ThunderJavascriptTrait;

  /**
   * Select Medias for field.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $entityBrowser
   *   Entity browser identifier.
   * @param array $medias
   *   List of media identifiers.
   */
  public function selectMedia(string $fieldName, string $entityBrowser, array $medias): void {
    $driver = $this->getSession()->getDriver();

    $selector = 'edit-' . str_replace(['[', ']', '_'], '-', $fieldName);
    $this->openEntityBrowser($selector, $entityBrowser);

    if ($entityBrowser === 'multiple_image_browser') {
      foreach ($medias as $media) {
        $driver->click("//div[contains(@class, 'views-row') and .//*[@name='entity_browser_select[$media]']]");
      }
    }
    else {
      $media = current($medias);
      $driver->click("//div[contains(@class, 'views-row') and .//*[@name='entity_browser_select' and @value='$media']]");
    }
    $this->assertWaitOnAjaxRequest();

    $this->submitEntityBrowser($entityBrowser);
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

    $this->selectMedia("{$fieldName}_0_inline_entity_form_field_media_images", 'multiple_image_browser', $medias);
  }

}
