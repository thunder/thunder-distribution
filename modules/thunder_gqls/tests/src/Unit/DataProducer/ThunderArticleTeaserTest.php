<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\media\MediaInterface;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ThunderArticleTeaser;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ThunderArticleTeaser
 * @group Thunder
 */
class ThunderArticleTeaserTest extends UnitTestCase {

  /**
   * The data producer under test.
   */
  protected ThunderArticleTeaser $producer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->producer = new ThunderArticleTeaser([], 'thunder_article_teaser', []);
  }

  /**
   * Builds a mock entity with the given media entity and teaser text.
   */
  protected function buildEntity(?MediaInterface $media, string $text = ''): ContentEntityInterface {
    $mediaField = new \stdClass();
    $mediaField->entity = $media;

    $textField = new \stdClass();
    $textField->value = $text;

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')->willReturnCallback(
      fn(string $field) => match ($field) {
        'field_teaser_media' => $mediaField,
        'field_teaser_text' => $textField,
        default => throw new \InvalidArgumentException("Unexpected field: $field"),
      }
    );

    return $entity;
  }

  /**
   * @covers ::resolve
   */
  public function testMediaIsReturned(): void {
    $media = $this->createMock(MediaInterface::class);

    $result = $this->producer->resolve($this->buildEntity($media, 'Teaser text'));

    $this->assertSame($media, $result['image']);
    $this->assertSame('Teaser text', $result['text']);
  }

  /**
   * @covers ::resolve
   */
  public function testMissingMediaReturnsNull(): void {
    $result = $this->producer->resolve($this->buildEntity(NULL));

    $this->assertNull($result['image']);
  }

}
