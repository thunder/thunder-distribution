<?php

namespace Drupal\Tests\thunder\BuildTests\QuickStart;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;

/**
 * Test whether we can install a Thunder site using the quickstart CLI.
 *
 * @group Thunder
 */
class UpdateTest extends QuickStartTestBase {

  /**
   * {@inheritdoc}
   */
  public function testUpdate() {

    $working_dir = 'docroot';
    $profile = 'thunder';

    $repository = $this->getThunderRepoRoot();

    $this->executeCommand("composer create-project thunder/thunder-project:3.x . --stability dev --no-interaction --no-install");
    $this->executeCommand("cp $repository/tests/fixtures/thunder3.3.0.composer.lock composer.lock");
    $this->executeCommand("composer install");
    $this->executeCommand("composer drupal:scaffold");
    $this->installQuickStart($profile, $working_dir);

    $this->visitThunder($working_dir);

    $this->executeCommand("composer config repositories.thunder path $repository");
    $this->executeCommand('composer require "thunder/thunder-distribution:*" --no-update');
    // @todo: Remove when 8.8.0-alpha1 is released.
    $this->executeCommand('composer require "webflo/drupal-core-require-dev:8.8.x-dev" --dev --no-update');
    $this->executeCommand('composer update');

    // Perform the update steps.
    $this->executeCommand('drush updb -y', $working_dir);
    $this->assertCommandSuccessful();

    $this->visitThunder($working_dir);
  }

  protected function visitThunder($working_dir) {
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

  /**
   * {@inheritdoc}
   */
  protected function getThunderRepoRoot() {
    return realpath(dirname(__DIR__, 4));
  }

}
