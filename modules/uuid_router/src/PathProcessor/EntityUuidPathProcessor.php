<?php

namespace Drupal\uuid_router\PathProcessor;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to transpose entity UUID paths to serialize IDs.
 *
 * Incoming paths matching /{entity_type_id}/{uuid} and /{entity_type_id}/{uuid}/edit
 * are routed to their correct path.
 *
 * @see https://drupal.org/i/2353611
 */
class EntityUuidPathProcessor implements InboundPathProcessorInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PathProcessorEntityUuid object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $matches = [];
    if (preg_match('/^\/([a-z_]+)\/(' . Uuid::VALID_PATTERN . ')$/i', $path, $matches)) {
      $path = $this->getInternalPath($matches[1], $matches[2], $path, 'canonical');
    }
    else if (preg_match('/^\/([a-z_]+)\/(' . Uuid::VALID_PATTERN . ')\/edit\$/i', $path, $matches)) {
      $path = $this->getInternalPath($matches[1], $matches[2], $path, 'edit-form');
    }
    return $path;
  }

  /**
   * @param string $entity_type_id
   * @param string $uuid
   * @param string $path
   * @param string $relationship
   *
   * @return string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getInternalPath(string $entity_type_id, string $uuid, string $path, string $relationship): string {
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return $path;
    }

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    if ($entity_type->hasLinkTemplate($relationship) && $entities = $storage->loadByProperties(['uuid' => $uuid])) {
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = reset($entities);
      $path = '/' . ltrim($entity->toUrl($relationship)->getInternalPath(), '/');
    }

    return $path;
  }

}
