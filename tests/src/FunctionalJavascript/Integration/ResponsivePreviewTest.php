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
    $session->evaluateScript("document.querySelector('#responsive-preview-frame').setAttribute('name', 'responsive-preview-frame-testing')");
    $session->switchToIFrame('responsive-preview-frame-testing');
    $assert_session->waitForElement('css', 'h1.title.page-title');
    $this->assertTrue($session->evaluateScript("window.location.href.endsWith('news')"));
    $session->switchToIFrame();

    // Clicking of rotate should rotate iframe sizes.
    $current_width = $session->evaluateScript("document.getElementById('responsive-preview-frame').clientWidth");
    $current_height = $session->evaluateScript("document.getElementById('responsive-preview-frame').clientHeight");
    $this->changeDeviceRotation();
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
      ->wait(5000, "jQuery('#responsive-preview').length === 0");
    $assert_session->elementNotExists('xpath', '//*[@id="responsive-preview"]');

    $node = $this->loadNodeByUuid('bbb1ee17-15f8-46bd-9df5-21c58040d741');
    $this->drupalGet($node->toUrl('edit-form'));

    // Using preview on entity edit should use preview page.
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $this->assertNotEquals(-1, $session->evaluateScript("jQuery('#responsive-preview-frame')[0].contentWindow.location.href.indexOf('/node/preview/')"));
    $this->changeDeviceRotation();

    // Un-checking device from dropdown should turn off preview.
    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $this->getSession()
      ->wait(5000, "jQuery('#responsive-preview').length === 0");
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
