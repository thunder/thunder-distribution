<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;

/**
 * Tests the device preview functionality integration.
 *
 * @group Thunder
 */
class ResponsivePreviewTest extends ThunderJavascriptTestBase {

  /**
   * Testing integration of "responsive_preview" module.
   */
  public function testDevicePreview() {
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
    $assert_session = $this->assertSession();

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();

    // Check channel page.
    $this->drupalGet('news');

    // The selection of device should create overlay with iframe to news page.
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $assert_session->elementNotExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');
    $assert_session->elementExists('xpath', '//*[@id="responsive-preview-frame"]');
    $this->waitForIframeContent();
    $this->assertTrue($session->evaluateScript("document.getElementById('responsive-preview-frame').contentWindow.location.href.endsWith('news')"));

    // Clicking of rotate should rotate iframe sizes.
    $current_width = $session->evaluateScript("document.getElementById('responsive-preview-frame').clientWidth");
    $current_height = $session->evaluateScript("document.getElementById('responsive-preview-frame').clientHeight");
    $this->changeDeviceRotation();
    $this->waitForIframeContent();
    $assert_session->elementExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');
    $this->assertEquals($current_height, $session->evaluateScript("document.getElementById('responsive-preview-frame').clientWidth"));
    $this->assertEquals($current_width, $session->evaluateScript("document.getElementById('responsive-preview-frame').clientHeight"));

    // Switching of device should keep rotation.
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[last()]');
    $assert_session->elementExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');
    $this->changeDeviceRotation();
    $assert_session->elementNotExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');

    // Clicking on preview close, should remove overlay.
    $this->getSession()
      ->getPage()
      ->find('xpath', '//*[@id="responsive-preview-close"]')
      ->click();
    $this->getSession()
      ->wait(5000, "document.getElementById('responsive-preview-frame') === null");
    $assert_session->elementNotExists('xpath', '//*[@id="responsive-preview"]');

    $node = $this->loadNodeByUuid('bbb1ee17-15f8-46bd-9df5-21c58040d741');
    $this->drupalGet($node->toUrl('edit-form'));

    // Using preview on entity edit should use preview page.
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $this->waitForIframeContent();
    $this->assertNotEquals(-1, $session->evaluateScript("document.getElementById('responsive-preview-frame').contentWindow.location.href.indexOf('/node/preview/')"));
    $this->changeDeviceRotation();

    // Un-checking device from dropdown should turn off preview.
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $this->getSession()
      ->wait(5000, "document.getElementById('responsive-preview-frame') === null");
    $assert_session->elementNotExists('xpath', '//*[@id="responsive-preview"]');
  }

  /**
   * Change device rotation for device preview.
   */
  protected function changeDeviceRotation() {
    $this->getSession()
      ->getPage()
      ->find('xpath', '//*[@id="responsive-preview-orientation"]')
      ->click();
    $this->assertWaitOnAjaxRequest();
  }

  /**
   * Wait for iframe content loaded.
   */
  protected function waitForIframeContent() {
    $this->getSession()->evaluateScript("document.getElementById('responsive-preview-frame').setAttribute('name', 'responsive-preview-frame-testing')");
    $this->getSession()->switchToIFrame('responsive-preview-frame-testing');
    $this->assertSession()->waitForElement('css', 'h1.page-title');
    $this->getSession()->switchToIFrame();
  }

  /**
   * Select device for device preview.
   *
   * NOTE: Index starts from 1.
   *
   * @param int $xpath_device_button
   *   The index number of device in drop-down list.
   */
  protected function selectDevice($xpath_device_button) {
    $page = $this->getSession()->getPage();

    $page->find('xpath', '//*[@id="responsive-preview-toolbar-tab"]/button')
      ->click();
    $this->assertWaitOnAjaxRequest();

    $page->find('xpath', $xpath_device_button)->click();
    $this->assertWaitOnAjaxRequest();
  }

}
