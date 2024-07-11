<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

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
    ]);

    $this->node->save();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('redirect');
  }

  /**
   * Test simple redirect and redirect with query string.
   */
  public function testRedirect(): void {
    $redirectPath = 'redirect-test-path';

    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->storage->create();
    $redirect->setSource($redirectPath);
    $redirect->setRedirect('node/' . $this->node->id());
    $redirect->setStatusCode(301);
    $redirect->save();

    $result = $this->executeDataProducer('thunder_redirect', [
      'path' => $redirectPath,
    ]);

    $this->assertEquals('/node/1', $result['url']);
    $this->assertEquals('301', $result['status']);

    $result = $this->executeDataProducer('thunder_redirect', [
      'path' => $redirectPath . '?test=1',
    ]);

    $this->assertEquals('/node/1?test=1', $result['url']);
    $this->assertEquals('301', $result['status']);
  }

}
