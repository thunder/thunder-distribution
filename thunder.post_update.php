<?php

/**
 * @file
 * Thunder post_update hooks.
 */

use Drupal\views\Entity\View;

/**
 * Remove thunder_article.settings and scheduler menu entry.
 */
function thunder_post_update_remove_scheduler_menu_entry(&$sandbox) {
  \Drupal::configFactory()
    ->getEditable('thunder_article.settings')
    ->delete();

  $view = View::load('scheduler_scheduled_content');
  if ($view) {
    $display =& $view->getDisplay('overview');
    $display['display_options']['menu']['type'] = 'none';
    $view->save();
    return t('The "Scheduled" menu entry was removed.');
  }
}
