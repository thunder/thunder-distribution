<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Tests\graphql\Traits\DataProducerExecutionTrait;

/**
 * Test the schema.
 *
 * @group Thunder
 */
class ThunderBreadcrumbTest extends ThunderGqlsTestBase {

  use DataProducerExecutionTrait;

  /**
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ThunderBreadCrumb::resolve
   */
  public function testThunderBreadCrumb(): void {
    // Load node "Come to DrupalCon New Orleans".
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');

    // Test with canonical url.
    $result = $this->executeDataProducer('thunder_breadcrumb', [
      'url' => $node->toUrl(),
    ]);

    $this->assertBreadcrumb($result);
  }

  /**
   * Assert the breadcromb for the test node.
   *
   * @param array $result
   *   The data producer result.
   */
  protected function assertBreadcrumb(array $result): void {
    $this->assertNotNull($result[0]);
    $this->assertEquals('route:<front>', $result[0]['uri']);
    $this->assertEquals('Home', $result[0]['title']);

    $this->assertNotNull($result[1]);
    $this->assertStringStartsWith('route:entity.taxonomy_term.canonical;taxonomy_term=', $result[1]['uri']);
    $this->assertEquals('Events', $result[1]['title']);
  }

}
