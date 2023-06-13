<?php

namespace Drupal\Tests\thunder_gqls\Kernel\TypeResolver;

use Drupal\node\NodeInterface;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\thunder_gqls\GraphQL\PagesTypeResolver;

/**
 *
 */
class PagesTypeResolverTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
  }

  /**
   * Test the resolve method.
   */
  public function testResolve(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->exactly(2))
      ->method('bundle')
      ->willReturnOnConsecutiveCalls('article', 'page');

    $resolver = new PagesTypeResolver(NULL);
    $this->assertEquals('Article', $resolver->__invoke($node));
  }

}