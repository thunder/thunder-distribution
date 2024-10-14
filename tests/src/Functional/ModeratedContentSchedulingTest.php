<?php

namespace Drupal\Tests\thunder\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\node\Entity\Node;

/**
 * Tests publishing/unpublishing scheduling for moderated nodes.
 *
 * @group Thunder
 */
class ModeratedContentSchedulingTest extends ThunderTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_test_mock_request',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logWithRole('editor');
    $this->drupalGet('node/add/article');
  }

  /**
   * Tests moderated nodes publish scheduling.
   */
  public function testPublishStateSchedule(): void {
    $publish_timestamp = strtotime('yesterday');
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->submitForm([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test workflow article 1 - Published',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text 1',
      'moderation_state[0]' => 'draft',
      'publish_on[0][value][date]' => date('Y-m-d', $publish_timestamp),
      'publish_on[0][value][time]' => date('H:i:s', $publish_timestamp),
      'publish_state[0]' => 'published',
    ], 'Save');

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle('Test workflow article 1 - Published');
    $revision_id = $node->getRevisionId();
    // Make sure node is unpublished.
    $this->assertEquals(FALSE, Node::load($node->id())->isPublished());
    $this->container->get('cron')->run();

    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->loadRevision($node_storage->getLatestRevisionId($node->id()));
    // Assert node is now published.
    $this->assertEquals(TRUE, $node->isPublished());
    $this->assertEquals('published', $node->moderation_state->value);
    // Assert only one revision is created during the operation.
    $this->assertEquals($revision_id + 1, $node->getRevisionId());

    $edit_url = $node->toUrl('edit-form');
    $this->drupalGet($edit_url);
    $this->submitForm([
      'title[0][value]' => 'Test workflow article 1 - Draft',
      'moderation_state[0]' => 'draft',
      'publish_on[0][value][date]' => date('Y-m-d', $publish_timestamp),
      'publish_on[0][value][time]' => date('H:i:s', $publish_timestamp),
      'publish_state[0]' => 'published',
    ], 'Save');
    $node_storage->resetCache([$node->id()]);

    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->loadRevision($node_storage->getLatestRevisionId($node->id()));
    $this->assertEquals('Test workflow article 1 - Draft', $node->getTitle());
    $this->assertEquals('draft', $node->moderation_state->value);
    $this->cronRun();
    $node_storage->resetCache([$node->id()]);

    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->loadRevision($node_storage->getLatestRevisionId($node->id()));
    $this->assertEquals(TRUE, $node->isPublished());
    $this->assertEquals('published', $node->moderation_state->value);
    $this->assertEquals('Test workflow article 1 - Draft', $node->getTitle());

    // Test published to published.
    // See: https://www.drupal.org/project/thunder/issues/3474835
    $this->drupalGet($edit_url);
    $this->submitForm([
      'title[0][value]' => 'Test workflow article 1 - Still Published',
      'moderation_state[0]' => 'published',
      'publish_on[0][value][date]' => date('Y-m-d', $publish_timestamp),
      'publish_on[0][value][time]' => date('H:i:s', $publish_timestamp),
      'publish_state[0]' => 'published',
    ], 'Save');
    $node_storage->resetCache([$node->id()]);
    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->loadRevision($node_storage->getLatestRevisionId($node->id()));
    $this->assertEquals(TRUE, $node->isPublished());
    $this->assertEquals('published', $node->moderation_state->value);

  }

  /**
   * Tests moderated nodes unpublish scheduling.
   */
  public function testUnpublishStateSchedule(): void {
    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->submitForm([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test workflow article 2 - Published',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text 2',
      'moderation_state[0]' => 'published',
      'unpublish_on[0][value][date]' => date('Y-m-d', strtotime('tomorrow')),
      'unpublish_state[0]' => 'unpublished',
    ], 'Save');

    $node = $this->getNodeByTitle('Test workflow article 2 - Published');

    // Set date manually, unpublish cannot be in the past.
    $node->set('unpublish_on', strtotime('yesterday'));
    $node->save();

    $revision_id = $node->getRevisionId();
    // Make sure node is published.
    $this->assertEquals(TRUE, Node::load($node->id())->isPublished());
    $this->cronRun();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Assert node is now unpublished.
    $this->assertEquals(FALSE, $node_storage->loadUnchanged($node->id())->isPublished());
    // Assert only one revision is created during the operation.
    $this->assertEquals($revision_id + 1, $node_storage->load($node->id())->getRevisionId());
  }

  /**
   * Tests publish scheduling for a draft of a published node.
   */
  public function testPublishOfDraft(): void {
    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->submitForm([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test workflow article 3 - Published',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text 3',
      'moderation_state[0]' => 'published',
    ], 'Save');

    $node = $this->getNodeByTitle('Test workflow article 3 - Published');
    $nid = $node->id();

    // Assert node is published.
    $this->assertEquals('Test workflow article 3 - Published', Node::load($nid)->getTitle());

    // Create a new pending revision and validate it's not the default published
    // one.
    $node->setTitle('Test workflow article 3 - Draft');
    $node->set('publish_on', strtotime('yesterday'));
    $node->set('moderation_state', 'draft');
    $node->set('publish_state', 'published');
    $node->save();
    $revision_id = $node->getRevisionId();

    // Test latest revision is not the published one.
    $this->assertEquals('Test workflow article 3 - Published', Node::load($nid)->getTitle());

    $this->cronRun();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Test latest revision is now the published one.
    $this->assertEquals('Test workflow article 3 - Draft', $node_storage->loadUnchanged($nid)->getTitle());

    // Assert only one revision is created during the operation.
    $this->assertEquals($revision_id + 1, $node_storage->load($node->id())->getRevisionId());

  }

}
