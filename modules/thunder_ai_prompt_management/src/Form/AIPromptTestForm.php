<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Form;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\thunder_ai_prompt_management\AIPromptInterface;
use Drupal\thunder_ai_prompt_management\AIPromptRunner;
use Drupal\thunder_ai_prompt_management\EntityContext;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Runs an AI prompt against a real entity, for testing and refinement.
 */
final class AIPromptTestForm extends FormBase {

  use AutowireTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'ai.provider')]
    protected readonly AiProviderPluginManager $providerPluginManager,
    #[Autowire(service: 'thunder_ai_prompt_management.prompt_runner')]
    protected readonly AIPromptRunner $promptRunner,
  ) {}

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

    $form['#attributes']['class'][] = 'ai-prompt-test-form';
    $form['#attached']['library'][] = 'thunder_ai_prompt_management/form';
    $form['#prefix'] = '<div id="ai-prompt-test-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $form['prompt_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Prompt settings'),
    ];
    $form['prompt_settings']['prompt_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt text'),
      '#description' => $this->t('Used as the system prompt. Edit freely to refine it, then save it back to the prompt when you are happy with it.'),
      '#default_value' => $ai_prompt_content->get('prompt')->value,
      '#required' => TRUE,
      '#rows' => 5,
    ];
    $form['prompt_settings']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $this->providerPluginManager->getSimpleProviderModelOptions('chat', FALSE),
      '#default_value' => $ai_prompt_content->get('model')->value,
      '#required' => TRUE,
    ];

    $form['contexts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test context'),
      '#tree' => FALSE,
    ];
    $values = EntityContext::valuesFromField($ai_prompt_content->get('entity_context'));
    foreach (EntityContext::groupByType($values) as $type_id => $bundles) {
      $definition = $this->entityTypeManager->getDefinition($type_id, FALSE);
      if (!$definition) {
        continue;
      }
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
      '#ajax' => [
        'callback' => '::ajaxRefreshForm',
        'wrapper' => 'ai-prompt-test-form-wrapper',
        'progress' => ['type' => 'throbber', 'message' => $this->t('Running the prompt…')],
      ],
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save prompt'),
      '#submit' => ['::savePrompt'],
      '#ajax' => [
        'callback' => '::ajaxRefreshForm',
        'wrapper' => 'ai-prompt-test-form-wrapper',
        'progress' => ['type' => 'throbber', 'message' => $this->t('Saving…')],
      ],
    ];

    $response = $form_state->get('response');
    if ($response !== NULL) {
      $form['result_response'] = [
        '#type' => 'details',
        '#title' => $this->t('AI response'),
        '#open' => TRUE,
        'text' => [
          '#type' => 'inline_template',
          '#template' => '<pre class="ai-prompt-test-form__response">{{ text }}</pre>',
          '#context' => ['text' => $response],
        ],
      ];
    }

    return $form;
  }

  /**
   * AJAX callback returning the rebuilt form after a submit or save.
   */
  public function ajaxRefreshForm(array &$form, FormStateInterface $form_state): array {
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

    // Runs the form's edits, not the stored values, to allow refining first.
    $clone = clone $prompt;
    $clone->set('model', (string) $form_state->getValue('model'));
    $response = $this->promptRunner->run($clone, $entity, (string) $form_state->getValue('prompt_text'));

    $form_state->set('response', $response !== '' ? $response : NULL);
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
   * Resolves the entity picked in whichever context selector was filled in.
   */
  private function resolveEntity(FormStateInterface $form_state, AIPromptInterface $prompt): ?ContentEntityInterface {
    $values = EntityContext::valuesFromField($prompt->get('entity_context'));
    foreach (array_keys(EntityContext::groupByType($values)) as $type_id) {
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
