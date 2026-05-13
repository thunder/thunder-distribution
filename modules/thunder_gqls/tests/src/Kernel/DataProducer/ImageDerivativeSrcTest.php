<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ImageDerivativeSrc
 * @group Thunder
 */
class ImageDerivativeSrcTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * @covers ::resolve
   */
  public function testAddsSrcAlias(): void {
    $result = $this->executeDataProducer('image_derivative_src', [
      'derivative' => ['url' => 'https://example.com/image.jpg', 'width' => 100],
    ]);
    $this->assertSame('https://example.com/image.jpg', $result['src']);
    $this->assertSame('https://example.com/image.jpg', $result['url']);
    $this->assertSame(100, $result['width']);
  }

  /**
   * @covers ::resolve
   */
  public function testPassesThroughWithoutUrl(): void {
    $input = ['width' => 100, 'height' => 200];
    $result = $this->executeDataProducer('image_derivative_src', ['derivative' => $input]);
    $this->assertSame($input, $result);
    $this->assertArrayNotHasKey('src', $result);
  }

  /**
   * @covers ::resolve
   */
  public function testPassesThroughEmptyArray(): void {
    $this->assertSame([], $this->executeDataProducer('image_derivative_src', ['derivative' => []]));
  }

}
