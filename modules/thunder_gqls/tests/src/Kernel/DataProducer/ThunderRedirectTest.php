<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\redirect\Entity\Redirect;
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
   * @var \Drupal\redirect\Entity\Redirect
   */
  protected Redirect $redirect;

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
    $this->installConfig(['redirect']);
    $this->installEntitySchema('redirect');

    $this->node = Node::create([
      'title' => 'Title',
      'type' => 'article',
    ]);

    $this->node->save();
    $this->redirect = $this->container->get('entity_type.manager')->getStorage('redirect')->create();
  }

  /**
   * Test simple redirect and redirect with query string.
   */
  public function testRedirect(): void {
    $redirectPath = 'redirect-test-path';
    $this->redirect->setSource($redirectPath);
    $this->redirect->setRedirect('node/' . $this->node->id());
    $this->redirect->setStatusCode(301);
    $this->redirect->save();

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
