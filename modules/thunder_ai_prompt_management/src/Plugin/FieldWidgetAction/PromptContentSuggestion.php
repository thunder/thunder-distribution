<?php

namespace Drupal\thunder_ai_prompt_management\Plugin\FieldWidgetAction;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface;
use Drupal\field_widget_actions\Attribute\FieldWidgetAction;
use Drupal\field_widget_actions\FieldWidgetActionBase;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The 'managed_prompt_content_suggestion' action.
 */
#[FieldWidgetAction(
  id: 'managed_prompt_content_suggestion',
  label: new TranslatableMarkup('Content Suggestion with managed prompt'),
  widget_types: ['string_textfield', 'string_textarea', 'text_textarea', 'text_textarea_with_summary', 'text_textfield'],
  field_types: ['string', 'string_long', 'text', 'text_long', 'text_with_summary'],
  category: new TranslatableMarkup('AI Content Suggestions with managed prompts'),
)]
class PromptContentSuggestion extends FieldWidgetActionBase {

  /**
   * The AI provider plugins manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected AiProviderPluginManager $aiProvider;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The AI content suggestions' config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The prompt JSON decoder.
   *
   * @var \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface
   */
  protected PromptJsonDecoderInterface $promptJsonDecoder;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => [
        'model' => '',
        'prompt' => '',
        'view_mode' => 'full',
        'attach_entity' => FALSE,
        'display_on_focus' => FALSE,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->aiProvider = $container->get('ai.provider');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->token = $container->get('token');
    $instance->renderer = $container->get('renderer');
    $instance->config = $container->get('config.factory')->get('ai_content_suggestions.settings');
    $instance->logger = $container->get('logger.channel.field_widget_actions');
    $instance->promptJsonDecoder = $container->get('ai.prompt_json_decode');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, $action_id = NULL) {
    $settings = $this->getConfiguration();
    if (!empty($settings['settings'])) {
      $settings = $settings['settings'];
    }
    $element = parent::buildConfigurationForm($form, $form_state, $action_id);
    $element['enabled']['#title'] = $this->t('Enable AI Suggestions');
    $element['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Suggestions Settings'),
    ];
    $field_definition = $this->getFieldDefinition();
    if (!empty($field_definition) && $action_id) {
      $element['settings']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $field_definition->getName() . '][settings_edit_form][third_party_settings][field_widget_actions][' . $action_id . '][enabled]"]' => ['checked' => TRUE],
        ],
      ];
    }
    $models = $this->aiProvider->getSimpleProviderModelOptions('chat', FALSE);
    $default_model = $this->aiProvider->getDefaultProviderForOperationType('chat');
    $element['settings']['model'] = [
      '#title' => $this->t('AI model to use for suggestions'),
      '#type' => 'select',
      '#options' => $models,
      '#empty_option' => $this->t('- Use default model for chat operation -'),
      '#default_value' => $settings['model'] ?? '',
      '#description' => $this->t('The default models could be set <a href="@url" target="_blank">here</a>. Current default model is @provider @model.', [
        '@url' => Url::fromRoute('ai.settings_form')->toString(),
        '@provider' => $default_model['provider_id'] ?? $this->t('not set'),
        '@model' => $default_model['model_id'] ?? '',
      ]),
    ];
    $field_entity_type = $form_state->getFormObject()->getEntity()->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($field_entity_type);
    $element['settings']['prompt'] = [
      '#title' => $this->t('AI Suggestion prompt'),
      '#type' => 'textarea',
      '#default_value' => $settings['prompt'] ?? '',
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $element['settings']['prompt']['#description'] = $this->t('It is allowed to use the tokens for entity type "@label", for example: <em>[@id:field_name]</em>. If no tokens for entity type @id are used, the entity is rendered in the selected view mode and appended to the prompt.', [
        '@id' => $field_entity_type,
        '@label' => $entity_type ? $entity_type->getLabel() : $field_entity_type,
      ]);
      $element['settings']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$field_entity_type],
      ];
    }
    $view_mode_options = [];
    $view_modes = $this->entityTypeManager->getStorage('entity_view_mode')->loadByProperties([
      'targetEntityType' => $field_entity_type,
    ]);
    $view_mode_options['full'] = $this->t('Full');
    foreach ($view_modes as $view_mode) {
      $view_mode_id = explode('.', $view_mode->id())[1];
      $view_mode_options[$view_mode_id] = $view_mode->label();
    }
    $element['settings']['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $view_mode_options,
      '#default_value' => $settings['view_mode'] ?? 'full',
      '#description' => $this->t('The view mode used to render the entity when it is attached to the prompt.'),
    ];
    $element['settings']['attach_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Attach the rendered entity'),
      '#default_value' => $settings['attach_entity'] ?? FALSE,
      '#description' => $this->t('Always render the entity in the selected view mode and append it to the prompt, in addition to replacing any tokens. When disabled, the entity is only attached if the prompt contains no entity tokens.'),
    ];
    $element['settings']['display_on_focus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the buttons when the form element is in focus'),
      '#default_value' => $settings['display_on_focus'] ?? FALSE,
      '#description' => $this->t('The buttons will be hidden by default and will be displayed only when the form element is focused. <b>Be aware that this is not good for accessibility</b>.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    // Only show if the user has permission to use ai content suggestions tools.
    return $this->currentUser->hasPermission('access ai content suggestion tools');
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(): array {
    return ['ai_content_suggestions/field_widget'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAjaxCallback(): ?string {
    return 'aiContentSuggestionsAjax';
  }

  /**
   * {@inheritdoc}
   */
  public function completeFormAlter(array &$form, FormStateInterface $form_state, array $context = []) {
    parent::completeFormAlter($form, $form_state, $context);
    $form['#attributes']['class'][] = 'ai-content-suggestions--enabled';
    $settings = $this->getConfiguration();
    if (!empty($settings['settings']['display_on_focus'])) {
      $form['#attributes']['class'][] = 'ai-content-suggestions--on-focus';
    }
  }

  /**
   * Ajax handler for AI content suggestions.
   */
  public function aiContentSuggestionsAjax(array &$form, FormStateInterface $form_state) {
    // Get the triggering element, as it contains the settings.
    $triggering_element = $form_state->getTriggeringElement();
    // Get the element selector that should have the selected suggestion.
    $selector = $this->getSuggestionsTarget($form, $form_state);
    // Set provider for AI Suggestions.
    $provider_config = $this->aiProvider->getSetProvider('chat', $triggering_element['#field_widget_action_settings']['settings']['model']);
    $prompt = $triggering_element['#field_widget_action_settings']['settings']['prompt'];
    // Get the content entity from form object.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);
    if ($entity->isNew()) {
      $entity->in_preview = TRUE;
    }
    $tokens = $this->token->scan($prompt);
    $markdown_options = [
      'header_style' => 'atx',
      'strip_tags' => TRUE,
      'strip_whitespace' => TRUE,
      'strip_placeholder_links' => TRUE,
    ];
    $converter = new HtmlConverter($markdown_options);
    $has_entity_tokens = !empty($tokens[$entity->getEntityTypeId()]);
    $attach_entity = !empty($triggering_element['#field_widget_action_settings']['settings']['attach_entity']);
    // Replace tokens in the prompt.
    if ($has_entity_tokens) {
      $prompt = $this->token->replace($prompt, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
    }
    else {
      // Replace any other token type.
      $prompt = $this->token->replacePlain($prompt);
    }
    $prompt = $converter->convert($prompt);
    // Attach the rendered entity when explicitly requested, or when the prompt
    // contains no entity tokens (backwards-compatible default).
    if ($attach_entity || !$has_entity_tokens) {
      // Render the entity in the configured view mode and convert to markdown.
      $view_mode = $triggering_element['#field_widget_action_settings']['settings']['view_mode'] ?? 'full';
      $render_array = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode);
      $html = $this->renderer->renderInIsolation($render_array);
      $prompt .= "\n\n" . $converter->convert($html);
    }
    $suggestions = '';
    /** @var \Drupal\ai\AiProviderInterface $ai_provider */
    $ai_provider = $provider_config['provider_id'];
    try {
      $messages = new ChatInput([
        new ChatMessage('user', $prompt),
      ]);
      $messages->setSystemPrompt($this->config->get('field_widget_prompt') ?? $this->t('You are helpful assistant.'));
      /** @var \Drupal\ai\OperationType\Chat\ChatMessage $response */
      $response = $ai_provider->chat($messages, $provider_config['model_id'], [
        'field_widget_action',
        'ai_content_suggestions',
      ])->getNormalized();
      $suggestion_candidates = $this->promptJsonDecoder->decode($response);
      // In case json is not found, the result of decoding will be a stream or a
      // chat message. We do not want to display a raw response to LLM, so the
      // suggestions will be left empty, so the default error message could be
      // displayed instead.
      if (is_array($suggestion_candidates)) {
        $suggestions = array_map(
          [Html::class, 'decodeEntities'],
          array_column($suggestion_candidates, 'suggestion')
        );
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    return $this->returnSuggestions($suggestions, $selector);
  }

}
