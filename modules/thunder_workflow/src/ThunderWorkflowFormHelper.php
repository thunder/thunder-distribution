<?php

namespace Drupal\thunder_workflow;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base for handler for node add/edit forms.
 */
class ThunderWorkflowFormHelper implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Constructs a NodeForm object.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInfo
   *   The moderation info service. The optionality is important
   *   otherwise this form becomes dependent on the content_moderation module.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $stateTransitionValidation
   *   The state transition validation service.
   */
  final public function __construct(protected readonly AccountInterface $currentUser, protected readonly MessengerInterface $messenger, RequestStack $requestStack, protected readonly EntityTypeManagerInterface $entityTypeManager, protected readonly ThemeManagerInterface $themeManager, protected readonly ModerationInformationInterface $moderationInfo, protected readonly StateTransitionValidationInterface $stateTransitionValidation) {
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('theme.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * Alter content moderation widgets.
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $form_object->getEntity();

    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }

    if (isset($this->getActiveThemes()['gin'])) {
      $form['#attached']['library'][] = 'thunder_workflow/edit-form';
    }

    if ($this->moderationInfo->hasPendingRevision($entity)) {
      $this->messenger->addWarning($this->t('This %entity_type has unpublished changes from user %user.', [
        '%entity_type' => $entity->get('type')->entity->label(),
        '%user' => $entity->getRevisionUser()->label(),
      ]));
    }

    // Get the field widget for the current form mode.
    $form_display = $form_object->getFormDisplay($form_state);
    $widget = $form_display->getRenderer('moderation_state');

    // Move the custom thunder widget to actions.
    if ($widget->getPluginId() === 'thunder_moderation_state_default') {
      $form = $this->moveStateToActions($entity, $form);
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()
      ->getId());

    if ($latest_revision_id !== NULL && isset($form['meta']['published'])) {
      $this->displayPublishedinformation($form, $entity);
      $this->createRevisionRevertButton($form, $entity);
    }

  }

  /**
   * Return current active theme including base themes.
   */
  public function getActiveThemes(): array {
    $activeTheme = $this->themeManager->getActiveTheme();
    $activeThemes = $activeTheme->getBaseThemeExtensions();
    $activeThemes[$activeTheme->getName()] = $activeTheme;

    return $activeThemes;
  }

  /**
   * Move state select to actions.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The altered form array.
   */
  public function moveStateToActions(NodeInterface $entity, array $form): array {
    $transitions = $this->stateTransitionValidation->getValidTransitions($entity, $this->currentUser);

    if (count($transitions) > 1) {
      $form['actions']['submit']['#value'] = $this->t('Save as');
      $form['actions']['submit']['#weight'] = 50;
    }
    elseif (count($transitions) === 1) {
      $form['moderation_state']['#attributes']['style'] = 'display: none';
      /** @var \Drupal\workflows\TransitionInterface $transition */
      $transition = reset($transitions);
      $form['actions']['submit']['#value'] = $this->t('Save as @state',
        ['@state' => $transition->to()->label()]);
    }

    // Move to gin_sticky_actions and promote moderation_state in gin theme to
    // not end up in dropdown button.
    $form['moderation_state']['#group'] = 'gin_sticky_actions';
    $form['moderation_state']['widget'][0]['#attributes']['form'] = $form['#id'];

    return $form;
  }

  /**
   * Display published information next to state in meta section.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\node\Entity\Node $entity
   *   The node entity.
   */
  public function displayPublishedinformation(array &$form, Node $entity): void {
    /** @var \Drupal\content_moderation\ContentModerationState $state */
    $state = $this->moderationInfo->getWorkflowForEntity($entity)->getTypePlugin()->getState($entity->moderation_state->value);

    if ($state->isDefaultRevisionState()) {
      return;
    }

    $args = [
      '@state' => $state->label(),
      '@entity_type' => strtolower($entity->type->entity->label()),
    ];

    $form['meta']['published']['#markup'] = $entity->isNew() || !$this->moderationInfo->isDefaultRevisionPublished($entity) ?
      $this->t('@state of unpublished @entity_type', $args) :
      $this->t('@state of published @entity_type', $args);
  }

  /**
   * Move revision revert Button to sidebar.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   */
  public function createRevisionRevertButton(array &$form, NodeInterface $entity): void {
    if (!$this->moderationInfo->hasPendingRevision($entity)) {
      return;
    }

    $route_info = Url::fromRoute('node.revision_revert_default_confirm', [
      'node' => $entity->id(),
      'node_revision' => $entity->getRevisionId(),
    ]);

    if ($this->request->query->has('destination')) {
      $query = $route_info->getOption('query');
      $query['destination'] = $this->request->query->get('destination');
      $route_info->setOption('query', $query);
    }

    $form['meta']['revert_to_default'] = [
      '#type' => 'link',
      '#title' => $this->t('Revert unpublished changes'),
      '#access' => $entity->access('revert revision', $this->currentUser),
      '#weight' => 101,
      '#url' => $route_info,
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    ];
  }

}
