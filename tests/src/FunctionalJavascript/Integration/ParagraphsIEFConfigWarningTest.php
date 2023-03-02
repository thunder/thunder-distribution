<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;

/**
 * Tests the config warning for ief inside paragraphs.
 *
 * @group Thunder
 */
class ParagraphsIEFConfigWarningTest extends ThunderJavascriptTestBase {

  /**
   * Find warnings and check content.
   */
  public function testConfigWarning(): void {

    $this->logWithRole('administrator');
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $page = $this->getSession()->getPage();
    $page->find('css', '[data-drupal-selector="edit-fields-field-paragraphs-settings-edit"]')->click();
    $this->assertWaitOnAjaxRequest();

    $this->assertEquals(
      'The Autocollapse option is not supported for the Thunder distribution because of potential data loss in combination with the inline_entity_form module. If you want to use it, make sure to remove all inline entity forms from your paragraph types.',
      $page->find('xpath', "//*[@data-drupal-selector='edit-fields-field-paragraphs-settings-edit-form-settings-autocollapse']/following-sibling::div[contains(@class,'messages--warning')]/div[@class='messages__content']")->getText(),
      "Warning message not equal."
    );

    $this->assertEquals(
      'The Collapse / Edit all option is not supported for the Thunder distribution because of potential data loss in combination with the inline_entity_form module. If you want to use it, make sure to remove all inline entity forms from your paragraph types.',
      $page->find('xpath', "//*[@data-drupal-selector='edit-fields-field-paragraphs-settings-edit-form-settings-features-collapse-edit-all']/parent::div/parent::div/following-sibling::div[contains(@class,'messages--warning')]/div[@class='messages__content']")->getText(),
      "Warning message not equal."
    );
  }

}
