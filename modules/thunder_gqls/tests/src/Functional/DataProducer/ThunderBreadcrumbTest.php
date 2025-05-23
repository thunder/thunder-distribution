<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Tests\graphql\Traits\DataProducerExecutionTrait;
use Drupal\redirect\Entity\Redirect;

/**
 * Test the schema.
 *
 * @group Thunder
 */
class ThunderBreadcrumbTest extends ThunderGqlsTestBase {

  use DataProducerExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'redirect',
  ];

  /**
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ThunderBreadCrumb::resolve
   */
  public function testThunderBreadCrumb(): void {
    // Load node "Come to DrupalCon New Orleans".
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');

    // Test with canonical url.
    $result = $this->executeDataProducer('thunder_breadcrumb', [
      'path' => $node->toUrl()->toString(),
    ]);

    $this->assertBreadcrumb($result);

    // Test with alias url.
    $result = $this->executeDataProducer('thunder_breadcrumb', [
      'path' => '/come-drupalcon-new-orleans',
    ]);

    $this->assertBreadcrumb($result);

    // Test redirect url.
    $redirect = Redirect::create();
    $redirect->setSource('redirected-url');
    $redirect->setRedirect($node->toUrl()->toString());
    $redirect->setStatusCode(301);
    $redirect->setLanguage($node->language()->getId());
    $redirect->save();

    $result = $this->executeDataProducer('thunder_breadcrumb', [
      'path' => '/redirected-url',
    ]);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
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
