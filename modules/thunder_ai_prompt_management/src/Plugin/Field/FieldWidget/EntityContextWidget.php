<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'entity_context' widget.
 *
 * @FieldWidget(
 *   id = "entity_context",
 *   label = @Translation("Entity context"),
 *   field_types = {
 *     "string"
 *   },
 *   multiple_values = TRUE
 * )
 */
final class EntityContextWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $selected = $this->currentSelections($items);

    // A fieldset, so that #title and #description are rendered.
    $element['#type'] = 'fieldset';
    $element['#attributes']['class'][] = 'entity-context-widget';

    foreach ($this->editorialEntityTypes() as $type_id => $type) {
      $options = ['*' => $this->t('All bundles')];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($type_id) as $bundle_id => $bundle) {
        $options[$bundle_id] = $bundle['label'];
      }

      $row = &$element[$type_id];
      $row = [
        '#type' => 'container',
        '#attributes' => ['class' => ['entity-context-widget__row']],
      ];
      $row['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['entity-context-widget__type']],
        '#value' => $type->getLabel(),
      ];
      $row['options'] = [
        '#type' => 'checkboxes',
        '#title' => $type->getLabel(),
        '#title_display' => 'invisible',
        '#options' => $options,
        '#default_value' => $selected[$type_id] ?? [],
      ];

      // Checking "All bundles" disables the individual bundles of that row.
      $all_class = 'js-entity-context-all--' . $type_id;
      $row['options']['*']['#attributes']['class'][] = $all_class;
      foreach (array_keys($options) as $bundle_id) {
        if ($bundle_id !== '*') {
          $row['options'][$bundle_id]['#states']['disabled'] = [
            '.' . $all_class => ['checked' => TRUE],
          ];
        }
      }
      unset($row);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $items = [];
    foreach ($values as $type_id => $group) {
      $checked = array_filter($group['options'] ?? []);
      foreach ($checked as $bundle_id) {
        $items[] = ['value' => $type_id . '.' . $bundle_id];
      }
    }
    return $items;
  }

  /**
   * Parses the stored field values into a type -> selected bundle keys map.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   *
   * @return array<string, string[]>
   *   Map of entity type ID to selected bundle keys ('*' for all bundles).
   */
  protected function currentSelections(FieldItemListInterface $items): array {
    $selected = [];
    foreach ($items as $item) {
      [$type_id, $bundle_id] = self::decodeContext((string) $item->getValue()['value']);
      $selected[$type_id][] = $bundle_id;
    }
    return $selected;
  }

  /**
   * Splits a stored "type.bundle" value into its parts.
   *
   * @param string $value
   *   A value in the format produced by massageFormValues(): "type.bundle",
   *   with "*" for the bundle meaning every bundle of that entity type.
   *
   * @return array{0: string, 1: string}
   *   The entity type ID and bundle key.
   */
  public static function decodeContext(string $value): array {
    return array_pad(explode('.', $value, 2), 2, '*');
  }

  /**
   * Returns all content entity types implementing EditorialContentEntityBase.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The entity types, keyed by ID, sorted by label.
   */
  protected function editorialEntityTypes(): array {
    $types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $type_id => $type) {
      if ($type_id === 'ai_prompt_content' || !$type->entityClassImplements(EditorialContentEntityBase::class)) {
        continue;
      }
      $types[$type_id] = $type;
    }
    uasort($types, fn ($a, $b) => strnatcasecmp((string) $a->getLabel(), (string) $b->getLabel()));
    return $types;
  }

}
