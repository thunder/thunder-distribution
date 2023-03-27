<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderArticleTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;

/**
 * Test for access unpublished integration.
 *
 * @group Thunder
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript\Integration
 */
class AccessUnpublishedTest extends ThunderJavascriptTestBase {

  use ThunderArticleTestTrait;
  use ThunderParagraphsTestTrait;

  /**
   * Testing integration of "access_unpublished" module.
   *
   * @dataProvider providerContentTypes
   */
  public function testAccessUnpublished(string $contentType, string $contentTypeDisplayName): void {
    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');

    $nodeTitle = $contentTypeDisplayName;
    // Create article and save it as unpublished.
    $this->nodeFillNew([
      'field_channel' => $term->id(),
      'title[0][value]' => $nodeTitle,
      'field_seo_title[0][value]' => $nodeTitle,
    ], $contentType);
    $this->addTextParagraph('field_paragraphs', 'Article Text 1');
    $this->setModerationState('draft');
    $this->clickSave();
    // Edit article and generate access unpublished token.
    $node = $this->drupalGetNodeByTitle($nodeTitle);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->expandAllTabs();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();
    $this->scrollElementInView('[data-drupal-selector="edit-generate-token"]');
    $driver->click('//*[@data-drupal-selector="edit-generate-token"]');
    $this->assertSession()->waitForElementVisible('css', '[data-drupal-selector="access-token-list"] a.clipboard-button', 5000);
    $copyToClipboard = $page->find('xpath', '//*[@data-drupal-selector="access-token-list"]//a[contains(@class, "clipboard-button")]');
    $tokenUrl = $copyToClipboard->getAttribute('data-unpublished-access-url');

    // Log-Out and check that URL with token works, but not URL without it.
    $loggedInUser = $this->loggedInUser;
    $this->drupalLogout();
    $this->drupalGet($tokenUrl);
    $this->assertSession()->pageTextContains('Article Text 1');
    $this->drupalGet($node->toUrl());
    $noAccess = $this->xpath('//h1[contains(@class, "page-title")]//span[text() = "403"]');
    $this->assertEquals(1, count($noAccess));

    // Log-In and delete token -> check page can't be accessed.
    $this->drupalLogin($loggedInUser);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->expandAllTabs();
    $this->scrollElementInView('[data-drupal-selector="edit-generate-token"]');
    $this->click('[data-drupal-selector="access-token-list"] li.dropbutton-toggle > button');
    $this->click('[data-drupal-selector="access-token-list"] li.delete > a');
    $this->assertWaitOnAjaxRequest();
    $this->clickSave();

    // Log-Out and check that URL with token doesn't work anymore.
    $this->drupalLogout();
    $this->drupalGet($tokenUrl);
    $noAccess = $this->xpath('//h1[contains(@class, "page-title")]//span[text() = "403"]');
    $this->assertCount(1, $noAccess);

    // Log-In and publish article.
    $this->drupalLogin($loggedInUser);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->setModerationState('published');
    $this->clickSave();

    // Log-Out and check that URL to article works.
    $this->drupalLogout();
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('Article Text 1');
  }

}
