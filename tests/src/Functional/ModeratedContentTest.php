<?php

namespace Drupal\Tests\thunder\Functional;

use Drupal\Core\Url;

/**
 * Tests the media system.
 *
 * @group Thunder
 */
class ModeratedContentTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_test_mock_request',
  ];

  /**
   * Test Creation of Article without content moderation.
   */
  public function testCreateArticleWithNoModeration(): void {
    // Delete all the articles so we can disable content moderation.
    foreach (\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'article']) as $node) {
      $node->delete();
    }
    \Drupal::service('module_installer')->uninstall(['thunder_workflow']);

    $this->logWithRole('editor');

    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    // Try to create an article.
    $this->drupalGet('node/add/article');
    $this->submitForm([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test article',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text',
    ], 'Save');

    $this->assertSession()->titleEquals('Massive gaining seo traffic text');
    $this->assertSession()->pageTextContains('Test article');
  }

  /**
   * Tests draft creation and that reverting to the default revision works.
   */
  public function testModerationWorkflow(): void {
    $this->logWithRole('editor');

    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->drupalGet('node/add/article');
    $this->submitForm([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test workflow article',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text',
      'moderation_state[0]' => 'published',
    ], 'Save');
    $this->assertSession()->titleEquals('Massive gaining seo traffic text');

    $node = $this->getNodeByTitle('Test workflow article');

    $this->drupalGet($node->toUrl('edit-form'));

    $this->submitForm([
      'moderation_state[0]' => 'unpublished',
    ], 'Preview');

    $this->clickLink('Back to content editing');
    $this->assertSession()->pageTextNotContains('An illegal choice has been detected. Please contact the site administrator.');

    $this->submitForm([
      'title[0][value]' => 'Test workflow article in draft',
      'field_seo_title[0][value]' => 'Massive gaining even more seo traffic text',
      'moderation_state[0]' => 'draft',

    ], 'Save');

    $this->drupalGet($node->toUrl('edit-form'));

    // Test, that hook_form_alter successfully moved moderation_state field.
    $this->assertSession()->elementExists('css', '[data-drupal-selector="edit-gin-sticky-actions"] > [data-drupal-selector="edit-moderation-state-wrapper"]');

    $this->submitForm([
      'title[0][value]' => 'Test workflow article in draft 2',
      'field_seo_title[0][value]' => 'Massive gaining even more and more seo traffic text',
      'moderation_state[0]' => 'draft',
    ], 'Save');

    $this->assertSession()->titleEquals('Massive gaining even more and more seo traffic text');

    $this->drupalGet($node->toUrl('edit-form'));

    // Test that info texts from hook_form_alter are shown.
    $this->assertSession()->elementTextContains('css', '.messages--warning > .messages__content', 'has unpublished changes from user');
    $this->assertSession()->elementTextContains('css', '#edit-meta-published', 'Draft of published article');

    // Test that the revert link from hook_form_alter is shown in edit-meta
    // section.
    $this->assertSession()->elementTextContains('css', '[data-drupal-selector="edit-meta"] > [data-drupal-selector="edit-meta-revert-to-default"]', 'Revert unpublished changes');

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $revert_url = Url::fromRoute('node.revision_revert_default_confirm', [
      'node' => $node->id(),
      'node_revision' => $node_storage->getLatestRevisionId($node->id()),
    ]);
    $this->drupalGet($revert_url);
    $this->submitForm([], 'Revert');

    $this->drupalGet($node->toUrl());
    $this->assertSession()->titleEquals('Massive gaining seo traffic text');

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_seo_title[0][value]', 'Massive gaining seo traffic text');
  }

}
