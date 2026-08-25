<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\thunder_ai_prompt_management\Form\AIPromptListFilterForm;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a list controller for the ai prompt entity type.
 */
final class AIPromptListBuilder extends EntityListBuilder {

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
    $build['filters']['form'] = \Drupal::formBuilder()->getForm(AIPromptListFilterForm::class);

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

    $request = \Drupal::requestStack()->getCurrentRequest();
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
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

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
      \Drupal::service('router.route_provider')->getRouteByName('entity.ai_prompt_content.test_form');
      return TRUE;
    }
    catch (RouteNotFoundException) {
      return FALSE;
    }
  }

}
