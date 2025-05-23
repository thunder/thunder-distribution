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
   */
  public function testBreadCrumbs(): void {

    $home = [Url::fromRoute('<front>')->toString() => 'Back to site'];
    $overview = [
      Url::fromRoute('system.admin_content')
        ->toString() => 'Overview',
    ];
    $node_add = [Url::fromRoute('node.add_page')->toString() => 'Add content'];

    // Basic Page.
    $node1 = $this->loadNodeByUuid('f3f1e924-d404-425e-8130-eeb554e36f7a');
    // Article.
    $node2 = $this->loadNodeByUuid('bbb1ee17-15f8-46bd-9df5-21c58040d741');

    $this->logWithRole('administrator');

    $this->assertBreadcrumb('node/add', $home + $overview);
    $this->assertBreadcrumb('node/add/article', $home + $overview + $node_add);
    $this->assertBreadcrumb('node/add/page', $home + $overview + $node_add);

    // Removed overview breadrumb due to https://drupal.org/i/3315662
    // @todo gin removes custom breadcrumb links from edit pages
    // $node1->toUrl()->toString() => 'Back to site'] + $overview);
    // Page content.
    $this->assertBreadcrumb($node1->toUrl('edit-form'), [$node1->toUrl()->toString() => 'Back to site']);
    // Article content.
    $this->assertBreadcrumb($node2->toUrl('edit-form'), [$node2->toUrl()->toString() => 'Back to site']);
  }

}
