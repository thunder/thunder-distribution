<?php

namespace Drupal\Tests\thunder_gqls\Kernel\TypeResolver;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\node\NodeInterface;
use Drupal\thunder_gqls\GraphQL\DecoratableTypeResolver;

/**
 * Test the pages type resolver.
 */
class DecoratableTypeResolverTest extends GraphQLTestBase {

  /**
   * The type resolver.
   *
   * @var \Drupal\thunder_gqls\GraphQL\DecoratableTypeResolver
   */
  protected DecoratableTypeResolver $resolver;

  /**
   * The decorated type resolver.
   *
   * @var \Drupal\thunder_gqls\GraphQL\DecoratableTypeResolver
   */
  protected DecoratableTypeResolver $decoratedResolver;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->resolver = $this->getMockBuilder(DecoratableTypeResolver::class)
      ->setConstructorArgs([NULL])
      ->onlyMethods(['resolve'])
      ->getMock();
    $this->resolver->method('resolve')
      ->willReturnCallback(function ($object) {
        return ucfirst($object->bundle());
      });

    $this->decoratedResolver = $this->getMockBuilder(DecoratableTypeResolver::class)
      ->setConstructorArgs([$this->resolver])
      ->onlyMethods(['resolve'])
      ->getMock();
    $this->decoratedResolver->method('resolve')
      ->willReturnCallback(function ($object) {
        if ($object->bundle(
          ) === 'article') {
            return 'DecoratedArticle';
        }
        return NULL;
      });

  }

  /**
   * Test the decoration.
   */
  public function testDecoration(): void {
    $newsNode = $this->createMock(NodeInterface::class);
    $newsNode->method('bundle')
      ->willReturn('news');

    $articleNode = $this->createMock(NodeInterface::class);
    $articleNode->method('bundle')
      ->willReturn('article');

    $this->assertEquals('News', $this->resolver->__invoke($newsNode));
    $this->assertEquals('Article', $this->resolver->__invoke($articleNode));

    $this->assertEquals('News', $this->decoratedResolver->__invoke($newsNode));
    $this->assertEquals('DecoratedArticle', $this->decoratedResolver->__invoke($articleNode));
  }

}
