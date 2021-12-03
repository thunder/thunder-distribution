<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Behat\Mink\Driver\Selenium2Driver;

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

  /**
   * Scroll element with defined css selector in middle of browser view.
   *
   * @param string $cssSelector
   *   CSS Selector for element that should be centralized.
   */
  public function scrollElementInView($cssSelector) {
    $this->getSession()
      ->executeScript('
        var viewPortHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
        var element = jQuery(\'' . addcslashes($cssSelector, '\'') . '\');
        var scrollTop = element.offset().top - (viewPortHeight/2);
        var scrollableParent = jQuery.isFunction(element.scrollParent) ? element.scrollParent() : [];
        if (scrollableParent.length > 0 && scrollableParent[0] !== document && scrollableParent[0] !== document.body) { scrollableParent[0].scrollTop = scrollableParent[0].scrollTop + scrollTop - scrollableParent.offset().top } else { window.scroll(0, scrollTop); };
      ');
  }

  /**
   * Click on Button based on Drupal selector (data-drupal-selector).
   *
   * @param string $drupalSelector
   *   Drupal selector.
   * @param bool $waitAfterAction
   *   Flag to wait for AJAX request to finish after click.
   */
  public function clickButtonDrupalSelector($drupalSelector, $waitAfterAction = TRUE) {
    $this->clickButtonCssSelector('[data-drupal-selector="' . $drupalSelector . '"]', $waitAfterAction);
  }

  /**
   * Click on Button based on Drupal selector (data-drupal-selector).
   *
   * @param string $cssSelector
   *   Drupal selector.
   * @param bool $waitAfterAction
   *   Flag to wait for AJAX request to finish after click.
   */
  public function clickButtonCssSelector($cssSelector, $waitAfterAction = TRUE) {
    $this->scrollElementInView($cssSelector);
    $this->click($cssSelector);

    if ($waitAfterAction) {
      $this->assertWaitOnAjaxRequest();
    }
  }

  /**
   * Click on Ajax Button based on CSS selector.
   *
   * Ajax buttons handler is triggered on "mousedown" event, so it has to be
   * triggered over JavaScript.
   *
   * @param string $cssSelector
   *   CSS selector.
   * @param bool $waitAfterAction
   *   Flag to wait for AJAX request to finish after click.
   */
  public function clickAjaxButtonCssSelector($cssSelector, $waitAfterAction = TRUE) {
    $this->scrollElementInView($cssSelector);
    $this->getSession()->executeScript("jQuery('{$cssSelector}').trigger('mousedown');");

    if ($waitAfterAction) {
      $this->assertWaitOnAjaxRequest();
    }
  }

  /**
   * Assert page title.
   *
   * @todo Remove when https://www.drupal.org/project/drupal/issues/3025845 is
   *   fixed.
   *
   * @param string $expectedTitle
   *   Expected title.
   */
  protected function assertPageTitle($expectedTitle) {
    /** @var \Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver $driver */
    $driver = $this->getSession()->getDriver();
    $actualTitle = $driver->getWebDriverSession()->title();
    static::assertEquals($expectedTitle, $actualTitle, 'Title found');
  }

  /**
   * Execute Cron over UI.
   */
  public function runCron() {
    $this->drupalGet('admin/config/system/cron');

    $this->getSession()
      ->getDriver()
      ->click('//input[@name="op"]');
  }

  /**
   * Click article save.
   */
  protected function clickSave() {
    $driver = $this->getSession()->getDriver();

    $driver->click('//div[@data-drupal-selector="edit-actions"]/input[@id="edit-submit"]');
  }

  /**
   * Wait for images to load.
   *
   * This functionality is sometimes need, because positions of elements can be
   * changed in middle of execution and make problems with execution of clicks
   * or other position depending actions. Image property complete is used.
   *
   * @param string $cssSelector
   *   Css selector, but without single quotes.
   * @param int $total
   *   Total number of images that should selected with provided css selector.
   * @param int $time
   *   Waiting time, by default 10sec.
   */
  public function waitForImages($cssSelector, $total, $time = 10000) {
    $this->getSession()
      ->wait($time, "jQuery('{$cssSelector}').filter(function(){return jQuery(this).prop('complete');}).length === {$total}");
  }

}
