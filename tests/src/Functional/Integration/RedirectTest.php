<?php

namespace Drupal\Tests\thunder\Functional\Integration;

use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests integration with the redirect.
 *
 * @group Thunder
 */
class RedirectTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['thunder_testing_demo', 'thunder_workflow'];

  /**
   * Tests redirect from old URL to new one.
   */
  public function testRedirectFromOldToNewUrl() {

    $this->logWithRole('editor');

    $this->drupalGet('burda-launches-open-source-cms-thunder');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();

    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $this->drupalGet($node->toUrl('edit-form'));
    $page->fillField('SEO Title', 'Burda Launches Worldwide Coalition');
    $page->find('xpath', '//*[@id="edit-moderation-state-0"]')
      ->selectOption('published');
    $page->pressButton('Save');

    $this->drupalGet('burda-launches-open-source-cms-thunder');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('burda-launches-worldwide-coalition');
  }

}
