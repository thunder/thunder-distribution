<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Exposed-like filters for the AI prompt admin listing.
 */
final class AIPromptFilterForm extends FormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the filter form.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $request_stack = $container->get('request_stack');
    assert($request_stack instanceof RequestStack);

    return new static(
      $entity_type_manager,
      $request_stack,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'thunder_ai_prompt_management_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->requestStack->getCurrentRequest();

    $form['#method'] = 'get';
    $form['#attributes']['class'][] = 'container-inline';
    $form['#attributes']['class'][] = 'ai-prompt-filters';

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => (string) $request->query->get('title', ''),
      '#size' => 30,
    ];

    $form['ai_task'] = [
      '#type' => 'select',
      '#title' => $this->t('Prompt type'),
      '#options' => $this->getAiTaskOptions(),
      '#default_value' => (string) $request->query->get('ai_task', ''),
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Published status'),
      '#options' => [
        '' => $this->t('- Any -'),
        '1' => $this->t('Published'),
        '0' => $this->t('Unpublished'),
      ],
      '#default_value' => (string) $request->query->get('status', ''),
    ];

    $form['filter_controls'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ai-prompt-filters__controls']],
    ];
    $form['filter_controls']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
    ];
    $form['filter_controls']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('entity.ai_prompt_content.collection'),
      '#attributes' => ['class' => ['button']],
    ];

    // Keep the query string clean for a GET filter form.
    unset($form['form_build_id'], $form['form_token'], $form['form_id']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op: GET method serializes filter values in query parameters.
  }

  /**
   * Builds select options for AI task entities.
   */
  private function getAiTaskOptions(): array {
    $options = ['' => $this->t('- Any -')];
    $storage = $this->entityTypeManager->getStorage('ai_task');
    $ids = $storage->getQuery()->sort('label')->accessCheck(TRUE)->execute();

    if (!$ids) {
      return $options;
    }

    foreach ($storage->loadMultiple($ids) as $task) {
      $options[(string) $task->id()] = $task->label();
    }

    return $options;
  }

}
