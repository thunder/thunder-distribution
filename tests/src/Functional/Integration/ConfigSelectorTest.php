<?php

namespace Drupal\Tests\thunder\Functional\Integration;

use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests integration with the config_selector.
 *
 * @group Thunder
 */
class ConfigSelectorTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_test_mock_request',
  ];

  /**
   * Tests content view with and without search_api.
   */
  public function testContentViewSearchApi(): void {
    $this->logWithRole('administrator');

    $assert_session = $this->assertSession();

    // Content lock fields are there by default.
    $this->drupalGet('admin/content');
    $assert_session->elementExists('xpath', '//*[@id="view-title-table-column"]/a');
    $assert_session->elementExists('css', '#block-gin-content > div > div.view-content');

    // Install search_api.
    $this->drupalGet('admin/modules');
    $edit = [
      'modules[thunder_search][enable]' => TRUE,
    ];
    $this->submitForm($edit, 'Install');
    $this->submitForm([], 'Continue');

    // Now we have a search_api based view.
    $this->drupalGet('admin/config/search/search-api/index/content');
    $this->submitForm([], 'Index now');
    $this->assertSession()->statusCodeEquals(200);
    $this->checkForMetaRefresh();

    $this->drupalGet('admin/content');
    $assert_session->elementExists('xpath', '//*[@id="view-title-table-column"]/a');
    $assert_session->elementExists('css', '#block-gin-content > div > div.view-content-search-api');

    // Uninstall thunder_search and search_api_mark_outdated.
    $this->drupalGet('admin/modules/uninstall');
    $edit = [
      'uninstall[thunder_search]' => TRUE,
    ];
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');

    $this->drupalGet('admin/modules/uninstall');
    $edit = [
      'uninstall[search_api_mark_outdated]' => TRUE,
    ];
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');

    // The normal view is back.
    $this->drupalGet('admin/content');
    $assert_session->elementExists('xpath', '//*[@id="view-title-table-column"]/a');
    $assert_session->elementExists('css', '#block-gin-content > div > div.view-content');
  }

}
