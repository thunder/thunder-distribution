<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests content moderation and scheduling.
 *
 * @group Thunder
 */
class ArticleSchedulerIntegrationTest extends ThunderJavascriptTestBase {

  use ThunderArticleTestTrait;
  use CronRunTrait;

  /**
   * Test that restricted editors are not allowed to edit scheduled articles.
   *
   * @dataProvider providerContentTypes
   */
  public function testRestrictedEditorSchedulerAccess(string $contentType, string $contentTypeDisplayName): void {
    $this->logWithRole('restricted_editor');
    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->nodeFillNew([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Scheduler integration testing',
      'field_seo_title[0][value]' => 'Scheduler integration testing seo title',
    ], $contentType);
    $this->assertSession()->elementNotExists('xpath', '//*[@data-drupal-selector="edit-publish-on-wrapper"]');

    $this->clickSave();

    $node = $this->getNodeByTitle('Scheduler integration testing');
    $edit_url = $node->toUrl('edit-form');

    // Add schedule data using editor.
    $this->logWithRole('editor');

    $this->drupalGet($edit_url);
    $this->expandAllTabs();
    $publish_timestamp = strtotime('-1 days');
    $this->setFieldValues([
      'publish_on[0][value][date]' => date('Y-m-d', $publish_timestamp),
      'publish_on[0][value][time]' => date('H:i:s', $publish_timestamp),
      'publish_state[0]' => 'published',
    ]);
    $this->clickSave();

    // Test restricted editor access.
    $this->logWithRole('restricted_editor');
    $this->drupalGet($edit_url);
    $this->assertCount(1, $this->xpath('//h1[contains(@class, "page-title") and text() = "403"]'));

    $this->cronRun();

    $this->drupalGet($edit_url);
    $this->assertCount(1, $this->xpath('//h1[contains(@class, "page-title") and text() = "Scheduler integration testing"]'));
  }

}
