<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\entity_blueprint\Exception\BlueprintException;
use Drupal\thunder_ai_prompt_management\AIPromptInterface;
use Drupal\thunder_ai_prompt_management\EntityContextPromptBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs an AI prompt against a real entity, for testing and refinement.
 */
final class AIPromptTestForm extends FormBase {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AiProviderPluginManager $providerPluginManager,
    protected readonly EntityContextPromptBuilderInterface $promptBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ai.provider'),
      $container->get('thunder_ai_prompt_management.entity_context_prompt_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'thunder_ai_prompt_management_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AIPromptInterface $ai_prompt_content = NULL): array {
    $form_state->set('ai_prompt_content', $ai_prompt_content);

    $form['prompt_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt text'),
      '#description' => $this->t('Used as the system prompt. Edit freely to refine it, then save it back to the prompt when you are happy with it.'),
      '#default_value' => $ai_prompt_content->get('prompt')->value,
      '#required' => TRUE,
      '#rows' => 5,
    ];

    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $this->providerPluginManager->getSimpleProviderModelOptions('chat', FALSE),
      '#default_value' => $ai_prompt_content->get('model')->value,
      '#required' => TRUE,
    ];

    $form['contexts'] = [
      '#type' => 'container',
      '#tree' => FALSE,
    ];
    foreach ($this->allowedContexts($ai_prompt_content) as $type_id => $bundles) {
      $definition = $this->entityTypeManager->getDefinition($type_id);
      $form['contexts']['entity_' . $type_id] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => $type_id,
        '#title' => $this->t('@label to test against', ['@label' => $definition->getLabel()]),
        '#description' => $this->t('Optional. Leave empty to run the prompt without an entity.'),
      ];
      if (!in_array('*', $bundles, TRUE)) {
        $form['contexts']['entity_' . $type_id]['#selection_settings']['target_bundles'] = $bundles;
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run test'),
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save prompt'),
      '#submit' => ['::savePrompt'],
    ];

    $response = $form_state->get('response');
    if ($response !== NULL) {
      $form['result_response'] = [
        '#type' => 'details',
        '#title' => $this->t('AI response'),
        '#open' => TRUE,
        'text' => [
          '#type' => 'inline_template',
          '#template' => '<pre>{{ text }}</pre>',
          '#context' => ['text' => $response],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild(TRUE);

    /** @var \Drupal\thunder_ai_prompt_management\AIPromptInterface $prompt */
    $prompt = $form_state->get('ai_prompt_content');
    $entity = $this->resolveEntity($form_state, $prompt);
    $prompt_text = (string) $form_state->getValue('prompt_text');

    try {
      $system_prompt = $this->promptBuilder->build($prompt_text, $entity);
    }
    catch (BlueprintException $e) {
      $this->messenger()->addWarning($this->t('Could not include the entity context: @message. Running the prompt without it.', ['@message' => $e->getMessage()]));
      $system_prompt = $this->promptBuilder->build($prompt_text);
    }

    try {
      $provider = $this->providerPluginManager->getSetProvider('chat', (string) $form_state->getValue('model'));
      // The chat API always needs a user turn; the system prompt under test
      // carries the actual instructions plus the entity context, so this is
      // just a generic trigger.
      $input = new ChatInput([new ChatMessage('user', 'Please respond.')]);
      $input->setSystemPrompt($system_prompt);
      $response = $provider['provider_id']->chat($input, $provider['model_id'], [
        'thunder_ai_prompt_management',
        'prompt_test',
      ])->getNormalized();
      $form_state->set('response', $response->getText());
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('The AI provider returned an error: @message', ['@message' => $e->getMessage()]));
      $form_state->set('response', NULL);
    }
  }

  /**
   * Saves the edited prompt text and model back to the prompt entity.
   */
  public function savePrompt(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild(TRUE);

    /** @var \Drupal\thunder_ai_prompt_management\AIPromptInterface $prompt */
    $prompt = $form_state->get('ai_prompt_content');
    $prompt->set('prompt', (string) $form_state->getValue('prompt_text'));
    $prompt->set('model', (string) $form_state->getValue('model'));
    $prompt->setNewRevision();
    $prompt->save();

    $this->messenger()->addStatus($this->t('The prompt %label has been updated.', ['%label' => $prompt->label()]));
  }

  /**
   * Groups the prompt's entity_context values by entity type.
   *
   * @return array<string, string[]>
   *   Map of entity type ID to allowed bundle keys ('*' for all bundles).
   */
  private function allowedContexts(AIPromptInterface $prompt): array {
    $contexts = [];
    foreach ($prompt->get('entity_context') as $item) {
      [$type_id, $bundle_id] = array_pad(explode('.', (string) $item->value, 2), 2, '*');
      $contexts[$type_id][] = $bundle_id;
    }
    return $contexts;
  }

  /**
   * Resolves the entity picked in whichever context selector was filled in.
   */
  private function resolveEntity(FormStateInterface $form_state, AIPromptInterface $prompt): ?ContentEntityInterface {
    foreach (array_keys($this->allowedContexts($prompt)) as $type_id) {
      $id = $form_state->getValue('entity_' . $type_id);
      if (empty($id)) {
        continue;
      }
      $entity = $this->entityTypeManager->getStorage($type_id)->load($id);
      if ($entity instanceof ContentEntityInterface && $entity->access('view')) {
        return $entity;
      }
    }
    return NULL;
  }

}
