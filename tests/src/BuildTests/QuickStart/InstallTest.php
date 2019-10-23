<?php

namespace Drupal\Tests\thunder\BuildTests\QuickStart;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;

/**
 * Test whether we can install a Thunder site using the quickstart CLI.
 *
 * @group Thunder
 */
class InstallTest extends QuickStartTestBase {

  /**
   * {@inheritdoc}
   */
  public function testInstall() {
    $working_dir = 'docroot';
    $profile = 'thunder';

    // Get the codebase.
    $this->copyCodebase();

    // Add some libs to the replace section of the root composer.json because
    // the merge plugin doesn't merge the replace entries from the distribution
    // composer.json.
    $this->executeCommand('jq \'.replace += {"npm-asset/fortawesome--fontawesome-free":"*", "npm-asset/jquery":"*"}\' composer.json > composer1.json');
    $this->executeCommand('mv composer1.json composer.json');

    // Composer tells you stuff in error output.
    $this->executeCommand('COMPOSER_DISCARD_CHANGES=true composer install --no-dev --no-interaction');
    $this->assertErrorOutputContains('Generating autoload files');
    $this->installQuickStart($profile, $working_dir);

    // Visit paths with expectations.
    $this->visit('/', $working_dir);
    $this->assertDrupalVisit();

    $this->visit('/does-not-exist', $working_dir);
    $assert = $this->getMink()->assertSession();
    $assert->statusCodeEquals(404);

    $this->visit('/admin', $working_dir);
    $assert->statusCodeEquals(403);
    $this->formLogin($this->adminUsername, $this->adminPassword, $working_dir);
    $this->visit('/admin', $working_dir);
    $assert->statusCodeEquals(200);

    $this->visit('/user/logout', $working_dir);
    $assert->statusCodeEquals(200);
    $this->visit('/admin', $working_dir);
    $assert->statusCodeEquals(403);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDrupalRoot() {
    return realpath(dirname(__DIR__, 8));
  }

}
