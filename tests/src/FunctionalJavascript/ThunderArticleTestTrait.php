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
   */
  public function articleFillNew(array $fieldValues) {
    $this->drupalGet('node/add/article');
    $this->assertWaitOnAjaxRequest();

    if (!empty($fieldValues)) {
      $this->expandAllTabs();
      if ($this->getSession()->getPage()->hasButton('Customize meta tags')) {
        $this->getSession()->getPage()->pressButton('Customize meta tags');
        $this->assertWaitOnAjaxRequest();
        $this->expandAllTabs();
      }
      $this->setFieldValues($fieldValues);
    }
  }

  /**
   * Expand all tabs on page.
   *
   * It goes up to level 3 by default.
   *
   * @param int $maxLevel
   *   Max depth of nested collapsed tabs.
   */
  public function expandAllTabs($maxLevel = 3) {
    $jsScript = 'jQuery(\'details.js-form-wrapper.form-wrapper:not([open]) > summary\').click().length';

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
  protected function setModerationState($state) {
    $this->getSession()
      ->getDriver()
      ->selectOption('//*[@id="edit-moderation-state-0"]', $state);
  }

}
