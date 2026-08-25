<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\thunder_ai_prompt_management\Form\AIPromptFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a list controller for the ai prompt entity type.
 */
final class AIPromptListBuilder extends EntityListBuilder {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a new AIPromptListBuilder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder, RequestStack $request_stack) {
    parent::__construct($entity_type, $storage);
    $this->formBuilder = $form_builder;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    $form_builder = $container->get('form_builder');
    assert($form_builder instanceof FormBuilderInterface);
    $request_stack = $container->get('request_stack');
    assert($request_stack instanceof RequestStack);

    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $form_builder,
      $request_stack,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build['filters'] = $this->formBuilder->getForm(AIPromptFilterForm::class);
    $build['table'] = parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['ai_task'] = $this->t('Prompt type');
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
  protected function getEntityListQuery(): QueryInterface {
    $query = parent::getEntityListQuery();
    $request = $this->requestStack->getCurrentRequest();

    $title = trim((string) $request->query->get('title', ''));
    if ($title !== '') {
      $query->condition('label', $title, 'CONTAINS');
    }

    $ai_task = $request->query->get('ai_task');
    if (is_numeric($ai_task) && (int) $ai_task > 0) {
      $query->condition('ai_task', (int) $ai_task);
    }

    $status = $request->query->get('status');
    if ($status === '0' || $status === '1') {
      $query->condition('status', (int) $status);
    }

    return $query;
  }

}
