<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait with functionality required for Article handling.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderArticleTestTrait {

  use ThunderFormFieldTestTrait;
  use ThunderJavascriptTrait;

  /**
   * Pre-fill defined article fields for new article.
   *
   * @param array $fieldValues
   *   Field values for new article.
   * @param string $type
   *   The node type to create.
   */
  public function nodeFillNew(array $fieldValues, string $type): void {
    $this->drupalGet('node/add/' . $type);
    $this->assertWaitOnAjaxRequest();
    if (empty($fieldValues)) {
      return;
    }

    $this->expandAllTabs();
    if ($this->getSession()->getPage()->hasButton('Customize meta tags')) {
      $this->scrollElementInView('#metatag-async-widget-wrapper');
      $this->getSession()->getPage()->pressButton('Customize meta tags');
      $this->assertWaitOnAjaxRequest();
      $this->expandAllTabs();
    }

    $this->setFieldValues($fieldValues);
  }

  /**
   * Expand all tabs on page.
   *
   * It goes up to level 3 by default.
   *
   * @param int $maxLevel
   *   Max depth of nested collapsed tabs.
   */
  public function expandAllTabs(int $maxLevel = 3): void {
    $jsScript = "(() => { const elements = document.querySelectorAll('details.js-form-wrapper.form-wrapper:not([open]) > summary'); elements.forEach((elem) => { elem.click(); }); elements.length; })()";

    $numOfOpen = $this->getSession()->evaluateScript($jsScript);
    $this->assertWaitOnAjaxRequest();

    for ($i = 0; $i < $maxLevel && $numOfOpen > 0; $i++) {
      $numOfOpen = $this->getSession()->evaluateScript($jsScript);
      $this->assertWaitOnAjaxRequest();
    }
  }

  /**
   * Set moderation state.
   *
   * @param string $state
   *   State id.
   */
  protected function setModerationState(string $state): void {
    $this->getSession()
      ->getDriver()
      ->selectOption('//*[@data-drupal-selector="edit-moderation-state-0"]', $state);
  }

}
