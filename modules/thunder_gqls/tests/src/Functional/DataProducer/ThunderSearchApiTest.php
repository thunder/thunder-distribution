<?php

namespace Drupal\Tests\thunder_gqls\Functional\DataProducer;

use Drupal\Tests\graphql\Traits\DataProducerExecutionTrait;
use Drupal\Tests\thunder_gqls\Functional\ThunderGqlsTestBase;
use Drupal\search_api\Query\QueryInterface;

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

    $options = [
      'index' => 'content',
      'search' => 'the',
      'limit' => 10,
      'offset' => 0,
    ];

    $result = $this->executeDataProducer('thunder_search_api', $options);
    $this->assertEquals(3, $result->total());

    $items = $result->items();
    $items->runQueue();
    $this->assertEquals('Burda Launches Open-Source CMS Thunder', $items->result[0]->getTitle());

    // Change sort order.
    $options['sortBy'] = [
      [
        'field' => 'search_api_relevance',
        'direction' => QueryInterface::SORT_ASC,
      ],
    ];

    $this->container->get('kernel')->rebuildContainer();
    $result = $this->executeDataProducer('thunder_search_api', $options);

    $items = $result->items();
    $items->runQueue();
    $this->assertEquals('Legal notice', $items->result[0]->getTitle());

    // Get articles only.
    $options['conditions'] = [
      [
        'field' => 'type',
        'value' => 'article',
        'operator' => '=',
      ],
    ];

    $this->container->get('kernel')->rebuildContainer();
    $result = $this->executeDataProducer('thunder_search_api', $options);

    $items = $result->items();
    $items->runQueue();
    $this->assertEquals('Come to DrupalCon New Orleans', $items->result[0]->getTitle());

  }

}
