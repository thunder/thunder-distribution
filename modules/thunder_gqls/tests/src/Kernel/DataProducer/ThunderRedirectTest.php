<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * ThunderRedirect data producer test class.
 *
 * @group Thunder
 */
class ThunderRedirectTest extends GraphQLTestBase {

  /**
   * The article node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * The redirect entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
    'redirect',
    'path_alias',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
    $this->installConfig(['redirect']);
    $this->installEntitySchema('redirect');

    $this->node = Node::create([
      'title' => 'Title',
      'type' => 'article',
      'path' => ['alias' => '/article'],
    ]);

    $this->node->save();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('redirect');
  }

  /**
   * Test simple redirect and redirect with query string.
   */
  public function testRedirect(): void {
    $redirectSource = 'redirect-test-source';
    $redirectDestination = '/redirect-test-destination';

    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->storage->create();
    $redirect->setSource($redirectSource);
    $redirect->setRedirect($redirectDestination);
    $redirect->setStatusCode(301);
    $redirect->save();

    $result = $this->executeDataProducer('thunder_redirect', [
      'path' => $redirectSource,
    ]);

    $this->assertEquals($redirectDestination, $result['url']);
    $this->assertEquals('301', $result['status']);

    $result = $this->executeDataProducer('thunder_redirect', [
      'path' => $redirectSource . '?test=1',
    ]);

    $this->assertEquals($redirectDestination . '?test=1', $result['url']);
    $this->assertEquals('301', $result['status']);
  }

}
