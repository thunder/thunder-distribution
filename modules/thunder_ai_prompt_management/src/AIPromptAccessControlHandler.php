<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the ai prompt entity.
 *
 * @see \Drupal\thunder_ai_prompt_management\Entity\AIPrompt
 */
final class AIPromptAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    assert($entity instanceof AIPromptInterface);

    if ($admin_permission = $this->entityType->getAdminPermission()) {
      if ($account->hasPermission($admin_permission)) {
        return AccessResult::allowed();
      }
    }

    if ($operation === 'view' || $operation === 'view all revisions' || $operation === 'view revision') {
      return AccessResult::allowedIfHasPermission($account, 'view ai_prompt_content');
    }

    if ($operation === 'update' || $operation === 'revert') {
      $own = $entity->getOwnerId() === $account->id();
      return AccessResult::allowedIf($own)
        ->andIf(AccessResult::allowedIfHasPermission($account, 'edit own ai_prompt_content'))
        ->orIf(AccessResult::allowedIfHasPermission($account, 'edit any ai_prompt_content'));
    }

    if ($operation === 'delete' || $operation === 'delete revision') {
      $own = $entity->getOwnerId() === $account->id();
      return AccessResult::allowedIf($own)
        ->andIf(AccessResult::allowedIfHasPermission($account, 'delete own ai_prompt_content'))
        ->orIf(AccessResult::allowedIfHasPermission($account, 'delete any ai_prompt_content'));
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      if ($account->hasPermission($admin_permission)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::allowedIfHasPermission($account, 'create ai_prompt_content');
  }

}
