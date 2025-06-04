<?php

namespace Drupal\thunder_gqls\GraphQL\Buffers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\graphql\GraphQL\Buffers\BufferBase;

/**
 * GraphQL Buffer for SearchApi Results.
 */
class SearchApiResultBuffer extends BufferBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * EntityBuffer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Add an item to the buffer.
   *
   * @param string|int|null $index
   *   The entity type of the given entity ids.
   * @param array|int $id
   *   The entity id(s) to load.
   *
   * @return \Closure
   *   The callback to invoke to load the result for this buffer item.
   */
  public function add($index, $id) {
    $item = new \ArrayObject([
      'index' => $index,
      'id' => $id,
    ]);

    return $this->createBufferResolver($item);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBufferId(\ArrayObject $item): string {
    return $item['index'];
  }

  /**
   * {@inheritdoc}
   */
  public function resolveBufferArray(array $buffer): array {
    $index = reset($buffer)['index'];
    $ids = array_map(function (\ArrayObject $item) {
      return (array) $item['id'];
    }, $buffer);

    $ids = call_user_func_array('array_merge', $ids);
    $ids = array_values(array_unique($ids));

    // Load the buffered entities.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load($index);

    $resultSet = $index->loadItemsMultiple($ids);
    $entities = [];

    foreach ($resultSet as $key => $resultItem) {
      if ($resultItem instanceof EntityAdapter) {
        $entities[$key] = $resultItem->getEntity();
      }
    }

    return array_map(function ($item) use ($entities) {
      if (is_array($item['id'])) {
        return array_reduce($item['id'], static function ($carry, $current) use ($entities) {
          if (!empty($entities[$current])) {
            $carry[] = $entities[$current];
            return $carry;
          }

          return $carry;
        }, []);
      }

      return $entities[$item['id']] ?? NULL;
    }, $buffer);
  }

}
