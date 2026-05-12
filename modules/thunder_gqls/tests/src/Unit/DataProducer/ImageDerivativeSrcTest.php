<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\Tests\UnitTestCase;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ImageDerivativeSrc;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ImageDerivativeSrc
 * @group Thunder
 */
class ImageDerivativeSrcTest extends UnitTestCase {

  /**
   * The data producer under test.
   *
   * @var \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ImageDerivativeSrc
   */
  protected ImageDerivativeSrc $producer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->producer = new ImageDerivativeSrc([], 'image_derivative_src', []);
  }

  /**
   * @covers ::resolve
   */
  public function testAddsSrcAlias(): void {
    $result = $this->producer->resolve(['url' => 'https://example.com/image.jpg', 'width' => 100]);
    $this->assertSame('https://example.com/image.jpg', $result['src']);
    $this->assertSame('https://example.com/image.jpg', $result['url']);
    $this->assertSame(100, $result['width']);
  }

  /**
   * @covers ::resolve
   */
  public function testPassesThroughWithoutUrl(): void {
    $input = ['width' => 100, 'height' => 200];
    $result = $this->producer->resolve($input);
    $this->assertSame($input, $result);
    $this->assertArrayNotHasKey('src', $result);
  }

  /**
   * @covers ::resolve
   */
  public function testPassesThroughNull(): void {
    $this->assertNull($this->producer->resolve(NULL));
  }

  /**
   * @covers ::resolve
   */
  public function testPassesThroughEmptyArray(): void {
    $this->assertSame([], $this->producer->resolve([]));
  }

}
