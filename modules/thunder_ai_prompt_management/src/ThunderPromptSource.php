<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\ai_chatbot_assistant_ui\PromptSourceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\thunder_ai_prompt_management\Entity\AIPrompt;

/**
 * Prompt source backed by ai_prompt_content, grouped by ai_task.
 */
final class ThunderPromptSource implements PromptSourceInterface {

  use StringTranslationTrait;

  /**
   * Group id for prompts whose ai_task is unset.
   */
  private const UNGROUPED = '_none';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getSlashGroups(?string $entityType, ?string $bundle, CacheableMetadata $cacheability): array {
    $cacheability->addCacheTags($this->entityTypeManager->getStorage('ai_task')->getEntityType()->getListCacheTags());
    $cacheability->addCacheTags($this->entityTypeManager->getStorage('ai_prompt_content')->getEntityType()->getListCacheTags());

    $by_task = [];
    foreach ($this->publishedPrompts() as $prompt) {
      $values = EntityContext::valuesFromField($prompt->get('entity_context'));
      if (!EntityContext::matches($values, $entityType, $bundle)) {
        continue;
      }
      $cacheability->addCacheableDependency($prompt);
      $task = $prompt->get('ai_task')->entity;
      // ai_task is optional, so an unassigned prompt still needs a group.
      $task_id = $task !== NULL ? $task->id() : self::UNGROUPED;
      $by_task[$task_id][] = [
        'id' => (string) $prompt->id(),
        'label' => $prompt->label(),
        'description' => '',
        'prompt' => (string) $prompt->get('prompt')->value,
      ];
    }

    $groups = [];
    foreach ($by_task as $task_id => $prompts) {
      if ($task_id === self::UNGROUPED) {
        $label = $this->t('Prompts');
      }
      else {
        $task = $this->entityTypeManager->getStorage('ai_task')->load($task_id);
        if ($task === NULL) {
          continue;
        }
        $cacheability->addCacheableDependency($task);
        $label = $task->label();
      }
      $groups[] = [
        'id' => $task_id,
        'label' => $label,
        'variables' => [],
        'prompts' => $prompts,
      ];
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestions(array $ids, ?string $entityType, ?string $bundle, CacheableMetadata $cacheability): array {
    $ids = array_values(array_filter(array_map('strval', $ids)));
    if (!$ids) {
      return [];
    }
    $prompts = $this->entityTypeManager->getStorage('ai_prompt_content')->loadMultiple($ids);

    $suggestions = [];
    foreach ($ids as $id) {
      $prompt = $prompts[$id] ?? NULL;
      if (!$prompt instanceof AIPrompt || !$prompt->isPublished()) {
        continue;
      }
      $values = EntityContext::valuesFromField($prompt->get('entity_context'));
      if (!EntityContext::matches($values, $entityType, $bundle)) {
        continue;
      }
      $cacheability->addCacheableDependency($prompt);
      $suggestions[] = [
        'id' => (int) $prompt->id(),
        'label' => (string) $prompt->label(),
        'prompt' => (string) $prompt->get('prompt')->value,
        'variables' => [],
      ];
    }
    return $suggestions;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvePrompt(string $id, array $values): string {
    $prompt = $this->loadPublished($id);
    if (!$prompt instanceof AIPrompt) {
      throw new \InvalidArgumentException('Prompt not found or access denied.');
    }
    // Thunder prompts carry no variables, so there is nothing to substitute.
    return (string) $prompt->get('prompt')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStarterEntityTypeId(): string {
    return 'ai_prompt_content';
  }

  /**
   * Loads a single published prompt.
   *
   * @param string $id
   *   The prompt id.
   *
   * @return \Drupal\thunder_ai_prompt_management\Entity\AIPrompt|null
   *   The prompt, or NULL if it does not exist or is unpublished.
   */
  private function loadPublished(string $id): ?AIPrompt {
    $storage = $this->entityTypeManager->getStorage('ai_prompt_content');
    $prompt = $storage->load($id);
    return $prompt instanceof AIPrompt && $prompt->isPublished() ? $prompt : NULL;
  }

  /**
   * Published prompts, no view-permission check (unpublished is the only gate).
   *
   * @return \Drupal\thunder_ai_prompt_management\Entity\AIPrompt[]
   *   The published prompts.
   */
  private function publishedPrompts(): array {
    $storage = $this->entityTypeManager->getStorage('ai_prompt_content');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->execute();
    /** @var \Drupal\thunder_ai_prompt_management\Entity\AIPrompt[] $prompts */
    $prompts = array_filter(
      $storage->loadMultiple($ids),
      static fn ($prompt): bool => $prompt instanceof AIPrompt,
    );
    return $prompts;
  }

}
