<?php

namespace Drupal\thunder_article\Form;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Base for handler for node add/edit forms.
 */
class ThunderNodeFormHelper implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface|null
   */
  protected $moderationInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

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
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInfo
   *   (optional) The moderation info service. The optionality is important
   *   otherwise this form becomes dependent on the content_moderation module.
   */
  public function __construct(AccountInterface $current_user, MessengerInterface $messenger, RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, ModerationInformationInterface $moderationInfo = NULL) {
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->request = $requestStack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
    $this->themeManager = $theme_manager;
    $this->moderationInfo = $moderationInfo;
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
      $container->get('content_moderation.moderation_information', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $form_object->getEntity();

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId());
    if ($latest_revision_id !== NULL && $this->moderationInfo && $this->moderationInfo->hasPendingRevision($entity)) {
      $this->messenger->addWarning($this->t('This %entity_type has unpublished changes from user %user.', [
        '%entity_type' => $entity->get('type')->entity->label(),
        '%user' => $entity->getRevisionUser()->label(),
      ]));
    }

    $form['actions'] = array_merge($form['actions'], $this->actions($entity));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(NodeInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId());

    if ($latest_revision_id == NULL || !$this->moderationInfo || !$this->moderationInfo->isModeratedEntity($entity)) {
      return [];
    }

    $element = [];
    // @todo Remove after seven / thunder_admin support is dropped.
    $activeTheme = $this->themeManager->getActiveTheme();
    $activeThemes = array_keys($activeTheme->getBaseThemeExtensions());
    $activeThemes[] = $activeTheme->getName();

    if (!empty(array_intersect($activeThemes, ['seven', 'thunder_admin']))) {
      /** @var \Drupal\content_moderation\ContentModerationState $state */
      $state = $this->moderationInfo->getWorkflowForEntity($entity)->getTypePlugin()->getState($entity->moderation_state->value);
      $element['status'] = [
        '#type' => 'item',
        '#markup' => $entity->isNew() || !$this->moderationInfo->isDefaultRevisionPublished($entity) ? $this->t('of unpublished @entity_type', ['@entity_type' => strtolower($entity->type->entity->label())]) : $this->t('of published @entity_type', ['@entity_type' => strtolower($entity->type->entity->label())]),
        '#weight' => 200,
        '#wrapper_attributes' => [
          'class' => ['status'],
        ],
        '#access' => !$state->isDefaultRevisionState(),
      ];

      $element['moderation_state_current'] = [
        '#type' => 'item',
        '#markup' => $state->label(),
        '#weight' => 210,
        '#wrapper_attributes' => [
          'class' => ['status', $state->id()],
        ],
      ];
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

      $element['revert_to_default'] = [
        '#type' => 'link',
        '#title' => $this->t('Revert to default revision'),
        '#access' => $entity->access('revert revision', $this->currentUser),
        '#weight' => 101,
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
      $element['revert_to_default']['#url'] = $route_info;
    }

    return $element;
  }

}
