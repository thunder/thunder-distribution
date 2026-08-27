<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Form controller for the ai prompt entity edit forms.
 */
final class AIPromptForm extends ContentEntityForm {

  use AutowireTrait;

  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    protected readonly AccountInterface $currentUser,
    protected readonly DateFormatterInterface $dateFormatter,
    #[Autowire(service: 'ai.provider')]
    protected readonly AiProviderPluginManager $providerPluginManager,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New ai prompt %label has been created.', $message_args));
        $this->logger('thunder_ai_prompt_management')->notice('New ai prompt %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The ai prompt %label has been updated.', $message_args));
        $this->logger('thunder_ai_prompt_management')->notice('The ai prompt %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\thunder_ai_prompt_management\AIPromptInterface $node */
    $node = $this->entity;

    if (isset($form['ai_task']['widget'])) {
      $form['ai_task']['widget']['#description'] = $this->t('The ai task this prompt is used for. Manage available tasks on the <a href=":url">AI Task configuration</a> page.', [
        ':url' => Url::fromRoute('entity.ai_task.collection')->toString(),
      ]);
    }

    $model_options = $this->providerPluginManager->getSimpleProviderModelOptions('chat', FALSE);
    $default_model = $this->entity->get('model')->value;
    if (!$default_model && $default = $this->providerPluginManager->getDefaultProviderForOperationType('chat')) {
      $default_model = $default['provider_id'] . '__' . $default['model_id'];
    }
    // A plain 'select' element doesn't support '#group' (only container-like
    // types do), so wrap it to join the group_basis field_group fieldset.
    $form['model_wrapper'] = [
      '#type' => 'container',
      '#tree' => FALSE,
      '#weight' => 5,
      '#group' => 'group_basis',
    ];
    $form['model_wrapper']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#description' => $this->t('The AI provider model this prompt is intended for.'),
      '#options' => $model_options,
      '#default_value' => $default_model,
      '#empty_option' => $this->t('- Select a model -'),
      '#required' => TRUE,
    ];

    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status'),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => $this->currentUser->hasPermission('administer ai_prompt_content'),
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $node->isPublished() ? $this->t('Published') : $this->t('Not published'),
      '#access' => !$node->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$node->isNew() ? $this->dateFormatter->format($node->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author'),
      '#markup' => $node->getOwner()?->getDisplayName(),
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    if (isset($form['status'])) {
      $form['status']['#group'] = 'footer';
    }

    // Author information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['ai-prompt-form-author'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    $form['#attached']['library'][] = 'thunder_ai_prompt_management/form';

    return $form;

  }

}
