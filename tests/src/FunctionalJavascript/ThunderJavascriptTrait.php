<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use PHPUnit\Framework\Assert;

/**
 * Trait adding javascript functionality.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderJavascriptTrait {

  /**
   * Waits for AJAX request to be completed.
   *
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) A message for exception.
   *
   * @throws \RuntimeException
   *   When the request is not completed. If left blank, a default message will
   *   be displayed.
   */
  public function assertWaitOnAjaxRequest($timeout = 10000, $message = 'Unable to complete AJAX request.'): void {
    $this->assertExpectedAjaxRequest(NULL, $timeout, $message);
  }

  /**
   * Overrides this for testing purposes.
   *
   * @param int|null $count
   *   (Optional) The number of completed AJAX requests expected.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) A message for exception.
   *
   * @throws \RuntimeException
   *   When the request is not completed. If left blank, a default message will
   *   be displayed.
   */
  public function assertExpectedAjaxRequest(?int $count = NULL, int $timeout = 10000, string $message = 'Unable to complete AJAX request.'): void {
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
          // Assert at least one AJAX request was started and completed.
          // For example, the machine name UI component does not use the Drupal
          // AJAX system, which means the other two checks below are inadequate.
          // @see Drupal.behaviors.machineName
          window.drupalActiveXhrCount === 0 && window.drupalCumulativeXhrCount >= 1 &&
          // Assert no AJAX request is running (via jQuery or Drupal) and no
          // animation is running.
          (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
          (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
        );
      }())
JS;
    $result = $this->getSession()->wait($timeout, $condition);

    // Now that there definitely is no more AJAX request in progress, count the
    // number of AJAX responses.
    // @see core/modules/system/tests/modules/js_testing_ajax_request_test/js/js_testing_ajax_request_test.js
    // @see https://developer.mozilla.org/en-US/docs/Web/API/Performance/timeOrigin
    [$drupal_ajax_request_count, $browser_xhr_request_count, $page_hash] = $this->getSession()->evaluateScript(<<<JS
(function(){
  return [
    window.drupalCumulativeXhrCount,
    window.performance
      .getEntries()
      .filter(entry => entry.initiatorType === 'xmlhttprequest')
      .length,
    window.performance.timeOrigin
  ];
})()
JS);

    // First invocation of ::assertWaitOnAjaxRequest() on this page: initialize.
    static $current_page_hash;
    static $current_page_ajax_response_count;
    if ($current_page_hash !== $page_hash) {
      $current_page_hash = $page_hash;
      $current_page_ajax_response_count = 0;
    }

    // Detect unnecessary AJAX request waits and inform the test author.
    if ($drupal_ajax_request_count === $current_page_ajax_response_count) {
      @trigger_error(sprintf('%s called unnecessarily in a test is deprecated in drupal:10.2.0 and will throw an exception in drupal:11.0.0. See https://www.drupal.org/node/3401201', __METHOD__), E_USER_DEPRECATED);
    }

    // Detect untracked AJAX requests. This will alert if the detection is
    // failing to provide an accurate count of requests.
    // @see core/modules/system/tests/modules/js_testing_ajax_request_test/js/js_testing_ajax_request_test.js
    if (!is_null($count) && $drupal_ajax_request_count !== $browser_xhr_request_count) {
      throw new \RuntimeException(sprintf('%d XHR requests through jQuery, but %d observed in the browser — this requires js_testing_ajax_request_test.js to be updated.', $drupal_ajax_request_count, $browser_xhr_request_count));
    }

    // Detect incomplete AJAX request.
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
        $variables = @unserialize($row->variables, ['allowed_classes' => FALSE]);
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

    // Update the static variable for the next invocation, to allow detecting
    // unnecessary invocations.
    $current_page_ajax_response_count = $drupal_ajax_request_count;

    if (!is_null($count)) {
      Assert::assertSame($count, $drupal_ajax_request_count);
    }
  }

  /**
   * Scroll element with defined css selector in middle of browser view.
   *
   * @param string $cssSelector
   *   CSS Selector for element that should be centralized.
   */
  public function scrollElementInView(string $cssSelector): void {
    $this->getSession()
      ->executeScript("document.querySelector('$cssSelector').scrollIntoView({block: 'center'})");
  }

  /**
   * Click on Button based on Drupal selector (data-drupal-selector).
   *
   * @param string $drupalSelector
   *   Drupal selector.
   * @param bool $waitAfterAction
   *   Flag to wait for AJAX request to finish after click.
   */
  public function clickDrupalSelector(string $drupalSelector, bool $waitAfterAction = TRUE): void {
    $this->clickCssSelector('[data-drupal-selector="' . $drupalSelector . '"]', $waitAfterAction);
  }

  /**
   * Click on Button based on Drupal selector (data-drupal-selector).
   *
   * @param string $cssSelector
   *   Drupal selector.
   * @param bool $waitAfterAction
   *   Flag to wait for AJAX request to finish after click.
   */
  public function clickCssSelector(string $cssSelector, bool $waitAfterAction = TRUE): void {
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', $cssSelector));
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
  public function clickAjaxButtonCssSelector(string $cssSelector, bool $waitAfterAction = TRUE): void {
    $this->scrollElementInView($cssSelector);
    $this->getSession()->executeScript("document.querySelector('{$cssSelector}').dispatchEvent(new MouseEvent('mousedown'));");

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
  protected function assertPageTitle(string $expectedTitle): void {
    /** @var \Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver $driver */
    $driver = $this->getSession()->getDriver();
    $actualTitle = $driver->getWebDriverSession()->title();
    static::assertEquals($expectedTitle, $actualTitle, 'Title found');
  }

  /**
   * Click article save.
   */
  protected function clickSave(): void {
    $driver = $this->getSession()->getDriver();

    $driver->click('//div[@data-drupal-selector="edit-actions"]/input[@data-drupal-selector="edit-submit"]');
  }

}
