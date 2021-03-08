<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * The base class for all functional Thunder GraphQl schema tests.
 */
abstract class ThunderGqlsTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'thunder';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * This can be removed, once the linked issue is resolved.
   *
   * @todo See https://github.com/drupal-graphql/graphql/issues/1177.
   *
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    'graphql.graphql_servers.thunder_graphql',
  ];

  /**
   * User with graphql request privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $graphqlUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->graphqlUser = $this->drupalCreateUser([
      'execute thunder_graphql arbitrary graphql requests',
      'access content',
      'access user profiles',
      'view media',
      'view published terms in channel',
      'view published terms in tags',
    ]);

  }

  /**
   * Login with defined role assigned to user.
   *
   * @param string $role
   *   Role name that will be assigned to user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function logWithRole(string $role) {
    $user = $this->drupalCreateUser();
    $user->addRole($role);
    $user->save();
    $this->drupalLogin($user);
  }

}
