<?php

namespace Drupal\Tests\thunder\Functional;

use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests breadcrumbs functionality.
 *
 * @group Thunder
 */
class BreadcrumbTest extends ThunderTestBase {


  use AssertBreadcrumbTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
  ];

  /**
   * Tests breadcrumbs on node and administrative paths.
   *
   * @group NoUpdate
   */
  public function testBreadCrumbs() {

    $home = [Url::fromRoute('<front>')->toString() => 'Home'];
    $overview = [Url::fromRoute('system.admin_content')->toString() => 'Overview'];
    $node_add = [Url::fromRoute('node.add_page')->toString() => 'Add content'];

    // Page content.
    $node1 = $this->loadNodeByUuid('f3f1e924-d404-425e-8130-eeb554e36f7a');
    // @todo Failing since https://www.drupal.org/node/2716019 was committed.
    // $this->assertBreadcrumb($node1->toUrl(), $home + ['/node' => 'Node']);
    // Article content.
    $node2 = $this->loadNodeByUuid('bbb1ee17-15f8-46bd-9df5-21c58040d741');
    $this->assertBreadcrumb($node2->toUrl(), $home + ['/events' => 'Events']);

    $this->logWithRole('administrator');

    $this->assertBreadcrumb('node/add', $home + $overview);
    $this->assertBreadcrumb('node/add/article', $home + $overview + $node_add);
    $this->assertBreadcrumb('node/add/page', $home + $overview + $node_add);

    // Page content.
    $this->assertBreadcrumb($node1->toUrl('edit-form'), $home + $overview);
    // Article content.
    $this->assertBreadcrumb($node2->toUrl('edit-form'), $home + $overview);
  }

}
