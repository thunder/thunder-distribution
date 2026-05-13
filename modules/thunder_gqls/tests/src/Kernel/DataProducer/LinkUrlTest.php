<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\LinkUrl
 * @group Thunder
 */
class LinkUrlTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * @covers ::resolve
   */
  public function testInternalUri(): void {
    $result = $this->executeDataProducer('link_url', [
      'link' => ['uri' => 'internal:/foo/bar'],
    ]);
    $this->assertSame('/foo/bar', $result);
  }

  /**
   * @covers ::resolve
   */
  public function testExternalUri(): void {
    $result = $this->executeDataProducer('link_url', [
      'link' => ['uri' => 'https://example.com/page'],
    ]);
    $this->assertSame('https://example.com/page', $result);
  }

  /**
   * @covers ::resolve
   */
  public function testEmptyLinkReturnsEmptyString(): void {
    $result = $this->executeDataProducer('link_url', [
      'link' => [],
    ]);
    $this->assertSame('', $result);
  }

}
