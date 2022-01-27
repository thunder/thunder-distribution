<?php

namespace Drupal\Tests\thunder\Functional\Integration;

use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests integration with the content_lock module.
 *
 * @group Thunder
 */
class ContentLockTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_test_mock_request',
  ];

  /**
   * Testing the content lock integration.
   */
  public function testContentLock(): void {
    $this->logWithRole('editor');

    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('This content is now locked against simultaneous editing. This content will remain locked if you navigate away from this page without saving or unlocking it.');

    $driver = $this->getSession()->getDriver();
    $driver->click('//*[@id="edit-unlock"]');

    $driver->click('//*[@id="edit-submit"]');
    $this->assertSession()->pageTextContains('Lock broken. Anyone can now edit this content.');

    $this->drupalGet($node->toUrl('edit-form'));
    $loggedInUser = $this->loggedInUser->label();

    $this->drupalLogout();

    // Login with other user.
    $this->logWithRole('restricted_editor');

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('This content is being edited by the user ' . $loggedInUser . ' and is therefore locked to prevent other users changes.');
  }

}
