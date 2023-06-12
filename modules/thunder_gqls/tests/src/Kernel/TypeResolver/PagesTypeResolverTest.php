<?php

namespace Drupal\Tests\thunder_gqls\Kernel\TypeResolver;

use Drupal\node\NodeInterface;
use Drupal\thunder_gqls\GraphQL\PagesTypeResolver;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

class PagesTypeResolverTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  public function setUp(): void {
    parent::setUp();
  }

  public function testResolve() {
    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->exactly(2))
      ->method('bundle')
      ->willReturnOnConsecutiveCalls('article', 'page');


    $resolver = new PagesTypeResolver(NULL);
    $this->assertEquals('Article', $resolver->__invoke($node));
    $this->assertEquals('BasicPage', $resolver->__invoke($node));
  }
}
