<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_blueprint\BlueprintSchemaBuilderInterface;
use Drupal\entity_blueprint\BlueprintSerializerInterface;

/**
 * Builds the system prompt sent when testing an AI prompt.
 */
final class EntityContextPromptBuilder implements EntityContextPromptBuilderInterface {

  use AutowireTrait;

  public function __construct(
    private readonly BlueprintSchemaBuilderInterface $schemaBuilder,
    private readonly BlueprintSerializerInterface $serializer,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly Token $token,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(string $prompt, ?ContentEntityInterface $entity = NULL): string {
    if ($entity === NULL) {
      return $this->token->replacePlain($prompt);
    }

    // Mirrors AgentDraftContext's block plus LoadCurrentDraft's result.
    $schema = $this->filteredSchema($entity->getEntityTypeId(), $entity->bundle());
    $header = sprintf('The editor has a %s/%s open for editing. Its schema:', $entity->getEntityTypeId(), $entity->bundle());

    // Replace tokens first, so bracketed field values can't corrupt the JSON.
    $prompt = $this->token->replacePlain($prompt, [$entity->getEntityTypeId() => $entity]);
    $prompt .= "\n\n" . $header . "\n" . Json::encode($schema);
    $prompt .= "\n\nIts current content:\n" . Json::encode($this->serializer->serialize($entity));

    return $prompt;
  }

  /**
   * Gets the entity's bundle schema, with base fields dropped throughout.
   *
   * Ported from AgentDraftContext::filteredSchema(), minus the exception catch.
   *
   * @return array<string, mixed>
   *   The schema.
   *
   * @throws \Drupal\entity_blueprint\Exception\BlueprintException
   *   If the bundle is unknown, or the current user may not create it.
   */
  private function filteredSchema(string $entityType, string $bundle): array {
    $schema = $this->schemaBuilder->getSchema($entityType, $bundle);
    $fields = $schema['fields'] ?? [];
    if (!is_array($fields)) {
      $fields = [];
    }
    $schema['fields'] = $this->filterFields($entityType, $bundle, $fields);
    return $schema;
  }

  /**
   * Recursively drops base-field entries from a getSchema() "fields" map.
   *
   * @param string $entityType
   *   The entity type ID the fields belong to.
   * @param string $bundle
   *   The bundle the fields belong to.
   * @param array<mixed> $fields
   *   The schema's "fields" map, keyed by field name.
   *
   * @return array<mixed>
   *   The map with base-field entries removed, at every nesting level.
   */
  private function filterFields(string $entityType, string $bundle, array $fields): array {
    $fields = $this->withoutBaseFields($entityType, $bundle, $fields);
    foreach ($fields as &$field) {
      if (!is_array($field)) {
        continue;
      }
      if (($field['type'] ?? NULL) !== 'entity_reference_revisions' || !isset($field['target_bundles'])) {
        continue;
      }
      $targetType = $field['target_type'] ?? NULL;
      if (!is_string($targetType) || !is_array($field['target_bundles'])) {
        continue;
      }
      foreach ($field['target_bundles'] as $childBundle => &$childSchema) {
        if (!is_array($childSchema)) {
          continue;
        }
        $childFields = $childSchema['fields'] ?? [];
        $childSchema['fields'] = $this->filterFields($targetType, (string) $childBundle, is_array($childFields) ? $childFields : []);
      }
      unset($childSchema);
    }
    unset($field);
    return $fields;
  }

  /**
   * Drops entries for base fields from a getSchema() "fields" map.
   *
   * @param string $entityType
   *   The entity type ID the fields belong to.
   * @param string $bundle
   *   The bundle the fields belong to.
   * @param array<mixed> $fields
   *   The schema's "fields" map, keyed by field name.
   *
   * @return array<mixed>
   *   The map with base-field entries removed.
   */
  private function withoutBaseFields(string $entityType, string $bundle, array $fields): array {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    foreach (array_keys($fields) as $fieldName) {
      $definition = $definitions[$fieldName] ?? NULL;
      if ($definition === NULL || $definition->getFieldStorageDefinition()->isBaseField()) {
        unset($fields[$fieldName]);
      }
    }
    return $fields;
  }

}
