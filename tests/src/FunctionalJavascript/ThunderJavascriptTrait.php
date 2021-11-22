<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;

/**
 * Trait adding javascript functionality.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderJavascriptTrait {

  /**
   * Overrides this for testing purposes.
   */
  public function assertWaitOnAjaxRequest($timeout = 10000, $message = 'Unable to complete AJAX request.') {
    $attach_error_handler = <<<JS
      (function() {
        window.addEventListener('error', function (event) {
          document.body.innerHTML += '<div class="ajax-error">' + event.message + '</div>';
        });
      }());
JS;
    $this->getSession()->evaluateScript($attach_error_handler);

    // Wait for a very short time to allow page state to update after clicking.
    usleep(5000);
    $condition = <<<JS
      (function() {
        function isAjaxing(instance) {
          return instance && instance.ajaxing === true;
        }
        return (
          // Assert no AJAX request is running (via jQuery or Drupal) and no
          // animation is running.
          (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
          (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
        );
      }())
JS;
    $result = $this->getSession()->wait($timeout, $condition);
    if (!$result) {
      // Assert the absence of PHP notices that may have occurred while
      // responding to AJAX requests.
      $rows = Database::getConnection()
        ->select('watchdog', 'w')
        ->fields('w')
        ->condition('type', 'php')
        ->orderBy('wid', 'DESC')
        ->execute()
        ->fetchAll();
      $php_log_entries = [];
      foreach ($rows as $row) {
        // @see \Drupal\dblog\Controller\DbLogController::formatMessage()
        $variables = @unserialize($row->variables);
        // Messages without variables or user specified text.
        if ($variables === NULL) {
          $message = Xss::filterAdmin($row->message);
        }
        else {
          $message = new FormattableMarkup(Xss::filterAdmin($row->message), $variables);
        }
        $php_log_entries[] = (string) $message;
      }
      $this->assertSame([], $php_log_entries);

      $errors = $this->getSession()->getPage()->findAll('css', '.ajax-error');
      foreach ($errors as $error) {
        $message .= ' ' . $error->getText();
      }
      throw new \RuntimeException($message);
    }
  }

}
