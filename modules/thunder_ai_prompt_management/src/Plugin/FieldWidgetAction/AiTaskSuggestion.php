<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Plugin\FieldWidgetAction;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field_widget_actions\Attribute\FieldWidgetAction;
use Drupal\field_widget_actions\FieldWidgetActionBase;
use Drupal\thunder_ai_prompt_management\AIPromptInterface;
use Drupal\thunder_ai_prompt_management\AIPromptRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Suggests field content from the prompts of a managed AI task.
 *
 * Each published prompt assigned to the configured task becomes one entry of a
 * dropbutton; a task with a single prompt renders as a plain button. Running
 * one opens the suggestions dialog, where the editor picks the text to insert.
 */
#[FieldWidgetAction(
  id: 'thunder_ai_task_suggestion',
  label: new TranslatableMarkup('AI Task suggestion'),
  widget_types: [
    'string_textfield',
    'string_textarea',
    'text_textfield',
    'text_textarea',
    'text_textarea_with_summary',
  ],
  field_types: [
    'string',
    'string_long',
    'text',
    'text_long',
    'text_with_summary',
  ],
  category: new TranslatableMarkup('Thunder AI'),
)]
final class AiTaskSuggestion extends FieldWidgetActionBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The prompt runner.
   */
  protected AIPromptRunner $promptRunner;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->promptRunner = $container->get('thunder_ai_prompt_management.prompt_runner');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => [
        'ai_task' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, $action_id = NULL) {
    $element = parent::buildConfigurationForm($form, $form_state, $action_id);
    $settings = $this->getConfiguration()['settings'] ?? [];

    $element['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Task settings'),
      '#open' => TRUE,
    ];
    $field_definition = $this->getFieldDefinition();
    if ($field_definition && $action_id) {
      $element['settings']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $field_definition->getName() . '][settings_edit_form][third_party_settings][field_widget_actions][' . $action_id . '][enabled]"]' => ['checked' => TRUE],
        ],
      ];
    }

    $options = [];
    foreach ($this->entityTypeManager->getStorage('ai_task')->loadMultiple() as $task) {
      $options[$task->id()] = $task->label();
    }
    $element['settings']['ai_task'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Task'),
      '#options' => $options,
      '#empty_option' => $this->t('- Pick an AI task -'),
      '#default_value' => $settings['ai_task'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Every published prompt assigned to this task, and scoped to this entity type, is offered on the button.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return $this->currentUser->hasPermission('view ai_prompt_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getAjaxCallback(): string {
    return 'aiTaskSuggestionAjax';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(): array {
    return [
      'field_widget_actions/suggestions',
      'thunder_ai_prompt_management/field_widget_action',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Renders one button per prompt, grouped into a dropbutton when the task has
   * more than one. Each button is built by the parent so it keeps the full
   * AJAX wiring; only the label and the prompt reference differ.
   */
  protected function actionButton(array &$form, FormStateInterface $form_state, array $context = []): void {
    $prompts = $this->loadPrompts($context['items']->getEntity());
    if (!$prompts) {
      return;
    }

    $fieldName = $context['items']->getFieldDefinition()->getName();
    $groupId = $this->getActionButtonWidgetId($fieldName, $context);

    if (count($prompts) === 1) {
      parent::actionButton($form, $form_state, $context);
      $form[$groupId]['#ai_prompt_id'] = reset($prompts)->id();
      return;
    }

    $links = [];
    foreach ($prompts as $prompt) {
      // A distinct action_id gives each button a unique #name, so Drupal can
      // tell which prompt was clicked.
      $subContext = ['action_id' => $groupId . '__' . $prompt->id()] + $context;
      parent::actionButton($form, $form_state, $subContext);
      // Ask for the key rather than assuming action_id: the parent appends the
      // delta for every item after the first, and this action is multiple by
      // default, so guessing would leave the entries empty on those items.
      $key = $this->getActionButtonWidgetId($fieldName, $subContext);

      $form[$key]['#value'] = $prompt->label();
      $form[$key]['#ai_prompt_id'] = $prompt->id();
      // Drupal only assigns #id to elements it walks as part of the form tree.
      // A button living under #links is not one, and preRenderAjaxForm() keys
      // its settings by #id - so without an explicit one every entry would
      // register under an empty key and overwrite the others.
      $form[$key]['#id'] = Html::getId(implode('-', array_filter([
        'fwa',
        $fieldName,
        (string) ($context['delta'] ?? ''),
        (string) $prompt->id(),
      ], static fn ($part) => $part !== '')));

      // Let the dropbutton own the entry's sizing; the standalone button
      // classes inherited from the parent make it too wide for the space the
      // list reserves, so the toggle ends up over the label.
      $classes = $form[$key]['#attributes']['class'] ?? [];
      $form[$key]['#attributes']['class'] = array_values(
        array_diff($classes, ['button--secondary', 'button--small'])
      );

      // Render a copy inside the dropbutton, but leave the original in the
      // form tree: Drupal only recognises a real form child as the triggering
      // element, and without that the click falls through to a plain submit.
      // Nested under #links the copy never passes through the form render
      // pipeline, so bind its #ajax here or the entry does nothing.
      $links[$key] = ['title' => RenderElementBase::preRenderAjaxForm($form[$key])];
      $form[$key]['#printed'] = TRUE;
    }

    $form[$groupId] = [
      '#type' => 'dropbutton',
      // Without a size variant the toggle renders at its full width while the
      // list only reserves room for a small one, so the arrow sits on top of
      // the first button's label. 'small' matches the paragraph add buttons.
      '#dropbutton_type' => 'small',
      '#attributes' => ['class' => ['ai-task-suggestion-dropbutton']],
      '#links' => $links,
      '#weight' => ($this->configuration['weight'] ?? 0) + 10,
    ];
  }

  /**
   * Runs the clicked prompt and offers the results in the suggestions dialog.
   */
  public function aiTaskSuggestionAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
    $trigger = $form_state->getTriggeringElement();
    $selector = $this->getSuggestionsTarget($form, $form_state);
    $prompt = $this->loadPrompt($trigger['#ai_prompt_id'] ?? NULL);
    if (!$prompt) {
      return $this->returnSuggestions([], $selector);
    }

    $entity = $this->buildEntity($form, $form_state);
    if ($entity && $entity->isNew()) {
      // Rendering an unsaved entity for context must not look like a real view.
      $entity->in_preview = TRUE;
    }

    return $this->returnSuggestions($this->promptRunner->suggest($prompt, $entity), $selector);
  }

  /**
   * {@inheritdoc}
   *
   * The parent resolves the widget from the root of the form, which misses a
   * field rendered inside a nested inline form - a paragraph, or
   * inline_entity_form. The button's own #array_parents carry the real path,
   * so fall back to walking from there; without it the suggestions dialog
   * would open with no insert target and clicking a suggestion would silently
   * do nothing.
   */
  protected function getTargetElement(array &$form, FormStateInterface $form_state): array {
    $element = parent::getTargetElement($form, $form_state);
    // Only the no-delta branch of the parent ignores #array_parents.
    if ($element || $this->getTargetElementDelta($form, $form_state) !== NULL) {
      return $element;
    }

    $parents = $form_state->getTriggeringElement()['#array_parents'] ?? [];
    if (!$parents) {
      return [];
    }
    // Drop the button itself, then descend into the widget's first item.
    array_pop($parents);
    array_push($parents, 'widget', 0, static::FORM_ELEMENT_PROPERTY);

    return NestedArray::getValue($form, $parents) ?? [];
  }

  /**
   * Loads the published prompts offered for the configured task.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   The entity being edited, used to resolve the bundle for base fields.
   *
   * @return \Drupal\thunder_ai_prompt_management\AIPromptInterface[]
   *   The prompts, keyed by ID and sorted by label.
   */
  public function loadPrompts(?ContentEntityInterface $entity = NULL): array {
    $task = $this->getConfiguration()['settings']['ai_task'] ?? '';
    $fieldDefinition = $this->getFieldDefinition();
    if (!$task || !$fieldDefinition) {
      return [];
    }

    $entityTypeId = $fieldDefinition->getTargetEntityTypeId();
    // Base fields such as the node title report no target bundle, so fall back
    // to the entity being edited.
    $bundle = $fieldDefinition->getTargetBundle() ?? $entity?->bundle();
    // entity_context values are "type.bundle" strings, with "*" meaning every
    // bundle of that entity type - see EntityContextWidget::massageFormValues().
    $contexts = [$entityTypeId . '.*'];
    if ($bundle) {
      $contexts[] = $entityTypeId . '.' . $bundle;
    }

    $storage = $this->entityTypeManager->getStorage('ai_prompt_content');
    $ids = $storage->getQuery()
      ->condition('ai_task', $task)
      ->condition('status', 1)
      ->condition('entity_context', $contexts, 'IN')
      ->accessCheck(TRUE)
      ->sort('label')
      ->execute();

    if (!$ids) {
      return [];
    }

    // loadMultiple() returns entities in storage order, so re-apply the sort.
    // The query is keyed by revision ID, so order by its values.
    /** @var \Drupal\thunder_ai_prompt_management\AIPromptInterface[] $prompts */
    $prompts = $storage->loadMultiple($ids);
    $sorted = [];
    foreach ($ids as $id) {
      if (isset($prompts[$id])) {
        $sorted[$id] = $prompts[$id];
      }
    }

    return $sorted;
  }

  /**
   * Loads one prompt by ID, if it is still there.
   */
  private function loadPrompt(string|int|null $id): ?AIPromptInterface {
    if (!$id) {
      return NULL;
    }
    $prompt = $this->entityTypeManager->getStorage('ai_prompt_content')->load($id);

    return $prompt instanceof AIPromptInterface ? $prompt : NULL;
  }

}
