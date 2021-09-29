<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait with functionality required for Article handling.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderArticleTestTrait {

  use ThunderFormFieldTestTrait;
  use ThunderMediaTestTrait;

  /**
   * Pre-fill defined article fields for new article.
   *
   * @param array $fieldValues
   *   Field values for new article.
   */
  public function articleFillNew(array $fieldValues) {
    if (!$this instanceof ThunderJavascriptTestBase) {
      throw new \RuntimeException('Trait is not used in correct context.');
    }

    $this->drupalGet('node/add/article');
    $this->assertWaitOnAjaxRequest();

    if (!empty($fieldValues)) {
      $this->expandAllTabs();
      if ($this->getSession()->getPage()->hasButton('Customize meta tags')) {
        $this->getSession()->getPage()->pressButton('Customize meta tags');
        $this->assertWaitOnAjaxRequest();
        $this->expandAllTabs();
      }
      $this->setFieldValues($this->getSession()->getPage(), $fieldValues);
    }

  }

}
