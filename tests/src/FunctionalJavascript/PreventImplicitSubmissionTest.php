<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Tests that implicit submission of forms is prevented on node edit form.
 *
 * @group Thunder
 */
class PreventImplicitSubmissionTest extends ThunderJavascriptTestBase {

  /**
   * Test implicit submission.
   */
  public function testImplicitSubmissionArticle(): void {
    $this->drupalGet('node/add/article');
    $this->assertWaitOnAjaxRequest();

    // Press enter key in the title field.
    $this->getSession()->getPage()->find('css', 'input[name="title[0][value]"]')->click()->keyDown(13);

    // Ensure, that we do not leave the page.
    $this->assertSession()->addressEquals('node/add/article');

  }

}
