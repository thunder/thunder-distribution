<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides GET filters for the AI prompt admin listing page.
 */
final class AIPromptListFilterForm extends FormBase {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'thunder_ai_prompt_management_list_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $query = $this->getRequest()->query;

    $form['#attributes']['class'][] = 'form--inline';
    $form['#attributes']['class'][] = 'container-inline';
    $form['#method'] = 'get';
    $form['#action'] = Url::fromRoute('entity.ai_prompt_content.collection')->toString();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title or label'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Title or label'),
      '#default_value' => (string) $query->get('label', ''),
      '#size' => 30,
    ];

    $form['ai_task'] = [
      '#type' => 'select',
      '#title' => $this->t('Prompt type'),
      '#title_display' => 'invisible',
      '#options' => $this->getPromptTypeOptions(),
      '#default_value' => (string) $query->get('ai_task', ''),
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication status'),
      '#title_display' => 'invisible',
      '#options' => [
        '' => $this->t('- Published status -'),
        '1' => $this->t('Published'),
        '0' => $this->t('Unpublished'),
      ],
      '#default_value' => (string) $query->get('status', ''),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#attributes' => ['class' => ['button']],
    ];
    $form['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('entity.ai_prompt_content.collection'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $query = [];

    $label = trim((string) $form_state->getValue('label', ''));
    if ($label !== '') {
      $query['label'] = $label;
    }

    $ai_task = (string) $form_state->getValue('ai_task', '');
    if ($ai_task !== '') {
      $query['ai_task'] = $ai_task;
    }

    $status = (string) $form_state->getValue('status', '');
    if ($status === '0' || $status === '1') {
      $query['status'] = $status;
    }

    $form_state->setRedirect('entity.ai_prompt_content.collection', [], ['query' => $query]);
  }

  /**
   * Builds prompt type filter options from configured AI tasks.
   *
   * @return array<string, string>
   *   Select options keyed by AI task ID.
   */
  private function getPromptTypeOptions(): array {
    $options = ['' => (string) $this->t('- Prompt type -')];
    $tasks = $this->entityTypeManager->getStorage('ai_task')->loadMultiple();

    foreach ($tasks as $task) {
      $options[$task->id()] = $task->label();
    }

    return $options;
  }

}
