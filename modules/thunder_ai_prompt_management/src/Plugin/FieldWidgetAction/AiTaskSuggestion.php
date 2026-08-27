<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Plugin\FieldWidgetAction;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field_widget_actions\Attribute\FieldWidgetAction;
use Drupal\field_widget_actions\FieldWidgetActionBase;
use Drupal\thunder_ai_prompt_management\AIPromptInterface;
use Drupal\thunder_ai_prompt_management\AIPromptRunner;
use Drupal\thunder_ai_prompt_management\EntityContext;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Suggests field content from the prompts of a managed AI task.
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
   * Per-request memoization of loadPrompts(), keyed by task/type/bundle.
   *
   * @var array<string, \Drupal\thunder_ai_prompt_management\AIPromptInterface[]>
   */
  protected array $promptsCache = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MessengerInterface $messenger,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    #[Autowire(service: 'thunder_ai_prompt_management.prompt_runner')]
    protected readonly AIPromptRunner $promptRunner,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger);
  }

  /**
   * {@inheritdoc}
   *
   * FieldWidgetActionBase::create() doesn't autowire, so bypass it here.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return static::createInstanceAutowired($container, $configuration, $plugin_id, $plugin_definition);
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
   * Renders one button per prompt; a dropbutton groups more than one.
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
      // A distinct action_id gives each button its own #name.
      $subContext = ['action_id' => $groupId . '__' . $prompt->id()] + $context;
      parent::actionButton($form, $form_state, $subContext);
      // Read back the key: the parent appends the delta past the first item.
      $key = $this->getActionButtonWidgetId($fieldName, $subContext);

      $form[$key]['#value'] = $prompt->label();
      $form[$key]['#ai_prompt_id'] = $prompt->id();
      // preRenderAjaxForm() keys settings by #id, which #links buttons lack.
      $form[$key]['#id'] = Html::getId(implode('-', array_filter([
        'fwa',
        $fieldName,
        (string) ($context['delta'] ?? ''),
        (string) $prompt->id(),
      ], static fn ($part) => $part !== '')));

      // Drop the standalone-button sizing classes for the dropbutton's own.
      $classes = $form[$key]['#attributes']['class'] ?? [];
      $form[$key]['#attributes']['class'] = array_values(
        array_diff($classes, ['button--secondary', 'button--small'])
      );

      // The original stays the trigger; bind #ajax here, #links skips it.
      $links[$key] = ['title' => RenderElementBase::preRenderAjaxForm($form[$key])];
      $form[$key]['#printed'] = TRUE;
    }

    $form[$groupId] = [
      '#type' => 'dropbutton',
      // 'small' matches the paragraph add buttons.
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

    return $this->returnSuggestions($this->promptRunner->suggest($prompt, $entity, ['field_widget_action']), $selector);
  }

  /**
   * {@inheritdoc}
   *
   * Falls back to #array_parents for fields nested in a paragraph/IEF.
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
    // Base fields report no target bundle, so fall back to the entity.
    $bundle = $fieldDefinition->getTargetBundle() ?? $entity?->bundle();
    $contexts = EntityContext::candidates($entityTypeId, $bundle ?: NULL);

    $cacheKey = $task . ':' . implode(',', $contexts);
    if (isset($this->promptsCache[$cacheKey])) {
      return $this->promptsCache[$cacheKey];
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
      return $this->promptsCache[$cacheKey] = [];
    }

    // loadMultiple() loses order, so re-apply the sort by revision ID.
    /** @var \Drupal\thunder_ai_prompt_management\AIPromptInterface[] $prompts */
    $prompts = $storage->loadMultiple($ids);
    $sorted = [];
    foreach ($ids as $id) {
      if (isset($prompts[$id])) {
        $sorted[$id] = $prompts[$id];
      }
    }

    return $this->promptsCache[$cacheKey] = $sorted;
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
