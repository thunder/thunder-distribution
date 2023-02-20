<?php

namespace Drupal\thunder_workflow;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Base for handler for node add/edit forms.
 */
class ThunderWorkflowFormHelper implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface|null
   */
  protected ?ModerationInformationInterface $moderationInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface|null
   */
  protected ?StateTransitionValidationInterface $stateTransitionValidation;

  /**
   * Constructs a NodeForm object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface|null $moderationInfo
   *   (optional) The moderation info service. The optionality is important
   *   otherwise this form becomes dependent on the content_moderation module.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface|null $stateTransitionValidation
   *   (optional) The state transition validation service.
   */
  public function __construct(AccountInterface $current_user, MessengerInterface $messenger, RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, ModerationInformationInterface $moderationInfo = NULL, StateTransitionValidationInterface $stateTransitionValidation = NULL) {
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->request = $requestStack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
    $this->themeManager = $theme_manager;
    $this->moderationInfo = $moderationInfo;
    $this->stateTransitionValidation = $stateTransitionValidation;
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
      $container->get('content_moderation.moderation_information', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('content_moderation.state_transition_validation', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state): array {
    if (!$this->moderationInfo) {
      return [];
    }

    if (isset($this->getActiveThemes()['gin'])) {
      $form['#attached']['library'][] = 'thunder_workflow/edit-form';
    }

    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    if ($this->moderationInfo->hasPendingRevision($entity)) {
      $this->messenger->addWarning($this->t('This %entity_type has unpublished changes from user %user.', [
        '%entity_type' => $entity->get('type')->entity->label(),
        '%user' => $entity->getRevisionUser()->label(),
      ]));
    }

    if (!isset($form['moderation_state']['widget'][0]['current']) && $this->moderationInfo->isModeratedEntity($entity)) {
      $form = $this->moveStateToActions($entity, $form);
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()
      ->getId());

    if ($latest_revision_id === NULL) {
      return [];
    }

    if (isset($form['meta']['published']) && $this->moderationInfo->isModeratedEntity($entity)) {
      /** @var \Drupal\content_moderation\ContentModerationState $state */
      $state = $this->moderationInfo->getWorkflowForEntity($entity)->getTypePlugin()->getState($entity->moderation_state->value);
      if (!$state->isDefaultRevisionState()) {
        $args = [
          '@state' => $state->label(),
          '@entity_type' => strtolower($entity->type->entity->label()),
        ];
        $form['meta']['published']['#markup'] = $entity->isNew() || !$this->moderationInfo->isDefaultRevisionPublished($entity) ?
          $this->t('@state of unpublished @entity_type', $args) :
          $this->t('@state of published @entity_type', $args);
      }

      if ($this->moderationInfo->hasPendingRevision($entity)) {
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

    return $form;
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
   * Move state select to actions
   *
   * @param \Drupal\node\NodeInterface $entity
   * @param array $form
   *
   * @return array
   */
  public function moveStateToActions(NodeInterface $entity, array $form): array {
    $transitions = $this->stateTransitionValidation->getValidTransitions($entity,
      $this->currentUser);

    if (count($transitions) > 1) {
      $form['actions']['submit']['#value'] = $this->t('Save as');
    }
    elseif (count($transitions) === 1) {
      $form['moderation_state']['#attributes']['style'] = 'display: none';
      /** @var \Drupal\workflows\TransitionInterface $transition */
      $transition = reset($transitions);
      $form['actions']['submit']['#value'] = $this->t('Save as @state',
        ['@state' => $transition->to()->label()]);
    }

    unset($form['moderation_state']['#group']);
    $form['moderation_state']['#weight'] = 90;

    $form['actions']['moderation_state'] = $form['moderation_state'];
    unset($form['moderation_state']);

    return $form;
  }

}
