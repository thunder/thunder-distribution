<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\Tests\UnitTestCase;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ArrayValue;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ArrayValue
 * @group Thunder
 */
class ArrayValueTest extends UnitTestCase {

  /**
   * The data producer under test.
   */
  protected ArrayValue $producer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->producer = new ArrayValue([], 'array_value', []);
  }

  /**
   * @covers ::resolve
   */
  public function testExplicitKeyReturnsValue(): void {
    $context = $this->createMock(FieldContext::class);
    $this->assertSame('bar', $this->producer->resolve(['foo' => 'bar'], 'foo', $context));
  }

  /**
   * @covers ::resolve
   */
  public function testExplicitKeyMissingReturnsNull(): void {
    $context = $this->createMock(FieldContext::class);
    $this->assertNull($this->producer->resolve(['foo' => 'bar'], 'missing', $context));
  }

  /**
   * @covers ::resolve
   */
  public function testNoKeyUsesFieldName(): void {
    $context = $this->createMock(FieldContext::class);
    $context->method('getFieldName')->willReturn('foo');
    $this->assertSame('bar', $this->producer->resolve(['foo' => 'bar'], NULL, $context));
  }

  /**
   * @covers ::resolve
   */
  public function testNoKeyFieldNameMissingReturnsNull(): void {
    $context = $this->createMock(FieldContext::class);
    $context->method('getFieldName')->willReturn('missing');
    $this->assertNull($this->producer->resolve(['foo' => 'bar'], NULL, $context));
  }

  /**
   * @covers ::resolve
   */
  public function testNonArrayInputReturnsNull(): void {
    $context = $this->createMock(FieldContext::class);
    $this->assertNull($this->producer->resolve('not-an-array', 'foo', $context));
    $this->assertNull($this->producer->resolve(NULL, 'foo', $context));
    $this->assertNull($this->producer->resolve(42, 'foo', $context));
  }

}
