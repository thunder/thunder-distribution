<?php

/**
 * @file
 * The install file.
 */

use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\Role;

/**
 * Implements hook_install().
 */
function thunder_news_article_install(): void {
  if ($workflow = \Drupal::configFactory()->getEditable('workflows.workflow.editorial')) {
    $nodeEntityTypes = $workflow->get('type_settings.node.type');
    $nodeEntityTypes[] = 'news_article';
    $workflow->set('type_settings.entity_types.node', $nodeEntityTypes)->save();
  }
}

/* hook_update_n implementations should be in the profile instead of this
submodule. */
