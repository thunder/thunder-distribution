<?php

namespace Drupal\Tests\thunder\Functional\Installer;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\dblog\Controller\DbLogController;
use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Tests the interactive installer installing the standard profile.
 *
 * @group ThunderInstaller
 */
class ThunderInstallerTest extends InstallerTestBase {

  /**
   * Number of known warnings during the installation.
   *
   * @var int
   */
  protected $knownWarnings = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage(): void {
    // Verify that the distribution name appears.
    $this->assertSession()->responseContains('thunder');
    // Verify that the "Choose profile" step does not appear.
    $this->assertSession()->pageTextNotContains('Choose profile');

    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile(): void {
    // This step is skipped, because there is a distribution profile.
  }

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite(): void {
    $edit = $this->translatePostValues($this->parameters['forms']['install_configure_form']);
    $edit['enable_update_status_module'] = FALSE;
    $edit['enable_update_status_emails'] = FALSE;
    $this->submitForm($edit, $this->translations['Save and continue']);
    dump($this->getSession()->getPage()->getContent());
    // If we've got to this point the site is installed using the regular
    // installation workflow.
    $this->setUpModules();
  }

  /**
   * Setup modules -> subroutine of test setUp process.
   */
  protected function setUpModules(): void {
    // @todo Add another test that tests interactive install of all optional
    //   Thunder modules.
    $this->submitForm([], $this->translations['Save and continue']);
    dump($this->getSession()->getPage()->getContent());
    $this->isInstalled = TRUE;
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('user/1');
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());

    $message = strip_tags(new TranslatableMarkup('Congratulations, you installed @drupal!', ['@drupal' => 'Thunder'], ['langcode' => $this->langcode]));
    $this->assertSession()->pageTextContains($message);

    $query = \Drupal::database()->select('watchdog', 'w')
      ->condition('severity', '4', '<');

    // Check that there are no warnings in the log after installation.
    $this->assertEquals($this->knownWarnings, $query->countQuery()->execute()->fetchField());

  }

  /**
   * {@inheritdoc}
   */
  protected function outputLogMessages(): void {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message', 'variables']);
    $andGroup = $query->andConditionGroup()
      ->condition('severity', '5', '<')
      ->condition('type', 'php');
    $group = $query->orConditionGroup()
      ->condition('severity', '10', '<')
      ->condition($andGroup);
    $query->condition($group);
    $query->groupBy('w.message');
    $query->groupBy('w.variables');

    $controller = DbLogController::create($this->container);

    // Check that there are no warnings in the log after installation.
    // $this->assertEqual($query->countQuery()->execute()->fetchField(), 0);.
    if ($query->countQuery()->execute()->fetchField()) {
      // Output all errors for modules tested.
      $errors = [];
      foreach ($query->execute()->fetchAll() as $row) {
        $errors[] = Unicode::truncate(Html::decodeEntities(strip_tags($controller->formatMessage($row))), 256, TRUE, TRUE);
      }
      throw new \Exception(print_r($errors, TRUE));
    }
  }

}
