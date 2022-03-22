<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Tests\thunder\Functional\ThunderTestBase;
use Drupal\Tests\thunder_gqls\Traits\ThunderGqlsTestTrait;

/**
 * The base class for all functional Thunder GraphQl schema tests.
 */
abstract class ThunderGqlsTestBase extends ThunderTestBase {

  use ThunderGqlsTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
    'thunder_testing_demo',
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
    $this->drupalLogin($this->graphqlUser);

  }

}
