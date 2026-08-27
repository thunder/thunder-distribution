<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Encodes, decodes, and matches "entity_context" field values.
 *
 * Values are stored as "{entity_type_id}.{bundle}", or
 * "{entity_type_id}.*" to match every bundle of that entity type.
 */
final class EntityContext {

  /**
   * Extracts the raw stored values from an entity_context field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The entity_context field items.
   *
   * @return string[]
   *   The raw stored values.
   */
  public static function valuesFromField(FieldItemListInterface $items): array {
    $values = [];
    foreach ($items as $item) {
      $values[] = (string) $item->getValue()['value'];
    }
    return $values;
  }

  /**
   * Builds a stored value from an entity type and bundle.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $bundle
   *   The bundle, or "*" for every bundle of that entity type.
   *
   * @return string
   *   The encoded value, e.g. "node.article" or "node.*".
   */
  public static function encode(string $entityTypeId, string $bundle): string {
    return $entityTypeId . '.' . $bundle;
  }

  /**
   * Splits a stored value into its entity type and bundle.
   *
   * @param string $value
   *   A value produced by self::encode(). A missing bundle is padded
   *   with "*".
   *
   * @return array{0: string, 1: string}
   *   The entity type ID and bundle key.
   */
  public static function decode(string $value): array {
    return array_pad(explode('.', $value, 2), 2, '*');
  }

  /**
   * Groups stored values by entity type.
   *
   * @param iterable<string> $values
   *   The raw stored values.
   *
   * @return array<string, string[]>
   *   Map of entity type ID to bundle keys ('*' for all bundles).
   */
  public static function groupByType(iterable $values): array {
    $grouped = [];
    foreach ($values as $value) {
      [$typeId, $bundleId] = self::decode((string) $value);
      $grouped[$typeId][] = $bundleId;
    }
    return $grouped;
  }

  /**
   * Builds the candidate stored values that would match a type/bundle pair.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle, or NULL if unknown.
   *
   * @return string[]
   *   The candidate values, e.g. ["node.*", "node.article"].
   */
  public static function candidates(string $entityTypeId, ?string $bundle): array {
    $candidates = [self::encode($entityTypeId, '*')];
    if ($bundle !== NULL) {
      $candidates[] = self::encode($entityTypeId, $bundle);
    }
    return $candidates;
  }

  /**
   * Whether a set of stored values applies to a given entity type/bundle.
   *
   * A NULL type or bundle, or an empty (or all-empty) set of stored
   * values, matches everything.
   *
   * @param iterable<string> $storedValues
   *   The raw stored values.
   * @param string|null $entityType
   *   The entity type, or NULL to skip filtering.
   * @param string|null $bundle
   *   The bundle, or NULL to skip filtering.
   *
   * @return bool
   *   Whether the stored values match.
   */
  public static function matches(iterable $storedValues, ?string $entityType, ?string $bundle): bool {
    if ($entityType === NULL || $bundle === NULL) {
      return TRUE;
    }
    $contexts = [];
    foreach ($storedValues as $value) {
      $value = (string) $value;
      if ($value !== '') {
        $contexts[] = $value;
      }
    }
    if (!$contexts) {
      return TRUE;
    }
    foreach (self::candidates($entityType, $bundle) as $candidate) {
      if (in_array($candidate, $contexts, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
