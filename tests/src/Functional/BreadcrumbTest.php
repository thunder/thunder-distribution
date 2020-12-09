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
    $this->assertBreadcrumb('node/3', $home + ['/node' => 'Node']);
    // Article content.
    $this->assertBreadcrumb('node/8', $home + ['/events' => 'Events']);

    $this->logWithRole('administrator');

    $this->assertBreadcrumb('node/add', $home + $overview);
    $this->assertBreadcrumb('node/add/article', $home + $overview + $node_add);
    $this->assertBreadcrumb('node/add/page', $home + $overview + $node_add);

    // Page content.
    $this->assertBreadcrumb('node/3/edit', $home + $overview);
    // Article content.
    $this->assertBreadcrumb('node/8/edit', $home + $overview);
  }

}
