<?php

namespace Drupal\Tests\thunder_gqls\Functional\DataProducer;

use Drupal\Tests\graphql\Traits\DataProducerExecutionTrait;
use Drupal\Tests\thunder_gqls\Functional\ThunderGqlsTestBase;

/**
 * Test ThunderSearchApi data producer.
 *
 * @group Thunder
 */
class ThunderSearchApiTest extends ThunderGqlsTestBase {

  use DataProducerExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_search',
    'search_api',
    'search_api_db',
  ];

  /**
   * Test ThunderSearchApi data producer.
   */
  public function testThunderSearchApi(): void {
    $this->logWithRole('administrator');

    $this->drupalGet('admin/config/search/search-api/index/content');
    $this->submitForm([], 'Index now');
    $this->assertSession()->statusCodeEquals(200);
    $this->checkForMetaRefresh();

    $result = $this->executeDataProducer('thunder_search_api', [
      'index' => 'content',
      'search' => 'Drupal',
      'limit' => 10,
      'offset' => 0,
    ]);

    $this->assertEquals(2, $result->total());

    /** @var \GraphQL\Deferred $items */
    $items = $result->items();
    $items->runQueue();
    $this->assertCount(2, $items->result);
    $this->assertEquals('Come to DrupalCon New Orleans', $items->result[0]->getTitle());
  }

}
