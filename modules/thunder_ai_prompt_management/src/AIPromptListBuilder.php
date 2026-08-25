<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\thunder_ai_prompt_management\Form\AIPromptListFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a list controller for the ai prompt entity type.
 */
final class AIPromptListBuilder extends EntityListBuilder {

  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    protected readonly FormBuilderInterface $formBuilder,
    protected readonly RequestStack $requestStack,
    protected readonly RouteProviderInterface $routeProvider,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $container->get('form_builder');
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $container->get('request_stack');
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = $container->get('router.route_provider');

    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $form_builder,
      $request_stack,
      $route_provider,
    );
  }

  /**
   * Number of rows shown on the admin listing page.
   *
   * @var int
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $build['filters']['form'] = $this->formBuilder->getForm(AIPromptListFilterForm::class);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['ai_task'] = $this->t('Prompt type');
    $header['model'] = $this->t('Model');
    $header['status'] = $this->t('Status');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\thunder_ai_prompt_management\AIPromptInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['ai_task'] = $entity->get('ai_task')->entity?->label() ?? $this->t('- None -');
    $row['model'] = $entity->get('model')->value;
    $row['status'] = $entity->get('status')->value ? $this->t('Published') : $this->t('Unpublished');
    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => $entity->getOwner()?->isAuthenticated()],
    ];
    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('id'));

    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return parent::getEntityIds();
    }
    $request_query = $request->query;

    $label = trim((string) $request_query->get('label', ''));
    if ($label !== '') {
      $query->condition('label', '%' . $label . '%', 'LIKE');
    }

    $ai_task = (string) $request_query->get('ai_task', '');
    if ($ai_task !== '') {
      $query->condition('ai_task.target_id', $ai_task);
    }

    $status = $request_query->get('status');
    if ($status === '0' || $status === '1') {
      $query->condition('status', (int) $status);
    }

    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL): array {
    $operations = parent::getDefaultOperations($entity, $cacheability);

    if ($entity->access('update') && $this->isTestRouteAvailable()) {
      $operations['test'] = [
        'title' => $this->t('Test prompt'),
        'weight' => 15,
        'url' => Url::fromRoute('entity.ai_prompt_content.test_form', ['ai_prompt_content' => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Checks whether the prompt test route exists in the active routing table.
   */
  private function isTestRouteAvailable(): bool {
    try {
      $this->routeProvider->getRouteByName('entity.ai_prompt_content.test_form');
      return TRUE;
    }
    catch (RouteNotFoundException) {
      return FALSE;
    }
  }

}
