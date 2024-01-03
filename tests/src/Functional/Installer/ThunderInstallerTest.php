<?php

namespace Drupal\Tests\thunder\Functional\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\FunctionalTests\Installer\InstallerTestBase;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

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
  protected function setUp(): void {
    BrowserTestBase::setUp();

    $this->visitInstaller();

    // Select language.
    $this->setUpLanguage();

    // Select profile.
    $this->setUpProfile();

    // Address the requirements problem screen, if any.
    $this->setUpRequirementsProblem();

    // Configure settings.
    $this->setUpSettings();

    // Configure site.
    $this->setUpSite();

    // Configure modules.
    $this->setUpModules();

    if ($this->isInstalled) {
      // Import new settings.php written by the installer.
      $request = Request::createFromGlobals();
      $class_loader = require $this->container->getParameter('app.root') . '/autoload.php';
      Settings::initialize($this->container->getParameter('app.root'), DrupalKernel::findSitePath($request), $class_loader);

      // After writing settings.php, the installer removes write permissions
      // from the site directory. To allow drupal_generate_test_ua() to write
      // a file containing the private key for drupal_valid_test_ua(), the site
      // directory has to be writable.
      // BrowserTestBase::tearDown() will delete the entire test site directory.
      // Not using File API; a potential error must trigger a PHP warning.
      chmod($this->container->getParameter('app.root') . '/' . $this->siteDirectory, 0777);
      $this->kernel = DrupalKernel::createFromRequest($request, $class_loader, 'prod', FALSE);
      $this->kernel->boot();
      $this->kernel->preHandle($request);
      $this->container = $this->kernel->getContainer();

      // Manually configure the test mail collector implementation to prevent
      // tests from sending out emails and collect them in state instead.
      $this->container->get('config.factory')
        ->getEditable('system.mail')
        ->set('interface.default', 'test_mail_collector')
        ->set('mailer_dsn', [
          'scheme' => 'null',
          'host' => 'null',
          'user' => NULL,
          'password' => NULL,
          'port' => NULL,
          'options' => [],
        ])
        ->save();

      $this->installDefaultThemeFromClassProperty($this->container);
    }
  }

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
    $edit['enable_update_status_module'] = NULL;
    $edit['enable_update_status_emails'] = NULL;
    $this->submitForm($edit, $this->translations['Save and continue']);
    // If we've got to this point the site is installed using the regular
    // installation workflow.
  }

  /**
   * Setup modules -> subroutine of test setUp process.
   */
  protected function setUpModules(): void {
    // @todo Add another test that tests interactive install of all optional
    //   Thunder modules.
    $this->submitForm([], $this->translations['Save and continue']);
    $this->isInstalled = TRUE;
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());

    $message = strip_tags(new TranslatableMarkup('Congratulations, you installed @drupal!', ['@drupal' => 'Thunder'], ['langcode' => $this->langcode]));
    $this->assertSession()->pageTextContains($message);

    $query = \Drupal::database()->select('watchdog', 'w')
      ->condition('severity', '4', '<');

    // Check that there are no warnings in the log after installation.
    $this->assertEquals($this->knownWarnings, $query->countQuery()->execute()->fetchField());

  }

}
