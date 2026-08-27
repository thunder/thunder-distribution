<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_ai_prompt_management\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\thunder_ai_prompt_management\Entity\AIPrompt;
use Drupal\thunder_ai_prompt_management\Entity\AiTask;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the access control handler for the AI prompt entity.
 *
 * @coversDefaultClass \Drupal\thunder_ai_prompt_management\AIPromptAccessControlHandler
 * @group Thunder
 */
#[RunTestsInSeparateProcesses]
class AIPromptAccessControlHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'key',
    'ai',
    'entity_blueprint',
    'thunder_ai_prompt_management',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('ai_prompt_content');
    $this->installEntitySchema('user');

    // User 1 is created first so it never owns the prompts under test.
    User::create(['uid' => 1, 'name' => 'admin'])->save();
  }

  /**
   * Creates a user with the given permissions.
   *
   * @param string[] $permissions
   *   The permissions to grant.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The created user.
   */
  protected function createUserWithPermissions(array $permissions): AccountInterface {
    $role = Role::create([
      'id' => 'test_role_' . $this->randomMachineName(),
      'label' => 'Test role',
    ]);
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
    $user = User::create([
      'name' => $this->randomMachineName(),
    ]);
    $user->addRole($role->id());
    $user->save();
    return $user;
  }

  /**
   * Creates an AI task config entity.
   *
   * @return \Drupal\thunder_ai_prompt_management\Entity\AiTask
   *   The created AI task.
   */
  protected function createTask(): AiTask {
    $task = AiTask::create([
      'id' => 'seo_description',
      'label' => 'SEO description',
      'description' => 'SEO description suggest button for article.',
    ]);
    $task->save();
    return $task;
  }

  /**
   * Creates an AI prompt owned by the given user.
   *
   * @param int $owner_id
   *   The owner user ID.
   *
   * @return \Drupal\thunder_ai_prompt_management\Entity\AIPrompt
   *   The created AI prompt.
   */
  protected function createPrompt(int $owner_id): AIPrompt {
    $prompt = AIPrompt::create([
      'label' => 'SEO description prompt',
      'prompt' => 'Suggest a meta description for the article.',
      'ai_task' => 'seo_description',
      'model' => 'openai__gpt-4o',
      'uid' => $owner_id,
    ]);
    $prompt->save();
    return $prompt;
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkCreateAccess
   */
  public function testCreateAndViewAccess(): void {
    $this->createTask();
    $prompt = $this->createPrompt(0);

    $creator = $this->createUserWithPermissions(['create ai_prompt_content', 'view ai_prompt_content']);
    $viewer = $this->createUserWithPermissions(['view ai_prompt_content']);
    $nobody = $this->createUserWithPermissions([]);

    $this->assertTrue($creator->hasPermission('create ai_prompt_content'));
    $this->assertTrue($this->container->get('entity_type.manager')
      ->getAccessControlHandler('ai_prompt_content')
      ->createAccess(NULL, $creator));
    $this->assertFalse($this->container->get('entity_type.manager')
      ->getAccessControlHandler('ai_prompt_content')
      ->createAccess(NULL, $nobody));

    $this->assertTrue($prompt->access('view', $viewer));
    $this->assertTrue($prompt->access('view all revisions', $viewer));
    $this->assertFalse($prompt->access('view', $nobody));
    $this->assertFalse($prompt->access('view all revisions', $nobody));
  }

  /**
   * @covers ::checkAccess
   */
  public function testUpdateOwnAndAnyAccess(): void {
    $this->createTask();
    $owner = $this->createUserWithPermissions(['edit own ai_prompt_content']);
    $prompt = $this->createPrompt((int) $owner->id());

    $own_editor = $this->createUserWithPermissions(['edit own ai_prompt_content']);
    $any_editor = $this->createUserWithPermissions(['edit any ai_prompt_content']);

    $this->assertTrue($prompt->access('update', $owner));
    $this->assertFalse($prompt->access('update', $own_editor));

    $other_owner_prompt = $this->createPrompt((int) $owner->id());
    $other_owner_prompt->setOwnerId($own_editor->id());
    $other_owner_prompt->save();

    $this->assertTrue($other_owner_prompt->access('update', $own_editor));
    $this->assertFalse($prompt->access('update', $own_editor));
    $this->assertTrue($prompt->access('update', $any_editor));

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('ai_prompt_content');
    $first_revision = $storage->getLatestRevisionId($prompt->id());
    $prompt->setNewRevision(TRUE);
    $prompt->setRevisionLogMessage('Update');
    $prompt->save();
    $this->assertTrue($storage->loadRevision($first_revision)->access('revert', $any_editor));
  }

  /**
   * @covers ::checkAccess
   */
  public function testDeleteOwnAndAnyAccess(): void {
    $this->createTask();
    $owner = $this->createUserWithPermissions(['delete own ai_prompt_content']);
    $prompt = $this->createPrompt((int) $owner->id());

    $own_deleter = $this->createUserWithPermissions(['delete own ai_prompt_content']);
    $any_deleter = $this->createUserWithPermissions(['delete any ai_prompt_content']);

    $this->assertTrue($prompt->access('delete', $owner));
    $this->assertFalse($prompt->access('delete', $own_deleter));

    $other_owner_prompt = $this->createPrompt((int) $owner->id());
    $other_owner_prompt->setOwnerId($own_deleter->id());
    $other_owner_prompt->save();

    $this->assertTrue($other_owner_prompt->access('delete', $own_deleter));
    $this->assertFalse($prompt->access('delete', $own_deleter));
    $this->assertTrue($prompt->access('delete', $any_deleter));

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('ai_prompt_content');
    $first_revision = $storage->getLatestRevisionId($prompt->id());
    $prompt->setNewRevision(TRUE);
    $prompt->setRevisionLogMessage('Update');
    $prompt->save();
    $this->assertTrue($storage->loadRevision($first_revision)->access('delete revision', $any_deleter));
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkCreateAccess
   */
  public function testAdminCanDoEverything(): void {
    $this->createTask();
    $owner = $this->createUserWithPermissions([]);
    $prompt = $this->createPrompt((int) $owner->id());

    $admin = $this->createUserWithPermissions(['administer ai_prompt_content']);

    $this->assertTrue($prompt->access('view', $admin));
    $this->assertTrue($prompt->access('update', $admin));
    $this->assertTrue($prompt->access('delete', $admin));
    $this->assertTrue($this->container->get('entity_type.manager')
      ->getAccessControlHandler('ai_prompt_content')
      ->createAccess(NULL, $admin));
  }

  /**
   * Tests that prompts are discoverable by their ai_task usage.
   */
  public function testPromptsDiscoverableByTask(): void {
    $this->createTask();
    $owner = $this->createUserWithPermissions([]);
    $this->createPrompt((int) $owner->id());

    $ids = $this->container->get('entity_type.manager')
      ->getStorage('ai_prompt_content')
      ->getQuery()
      ->condition('ai_task', 'seo_description')
      ->accessCheck(FALSE)
      ->execute();

    $this->assertCount(1, $ids);
  }

  /**
   * Tests that prompts are discoverable by their entity context.
   */
  public function testPromptsDiscoverableByEntityContext(): void {
    $this->createTask();
    $owner = $this->createUserWithPermissions([]);

    AIPrompt::create([
      'label' => 'Node article prompt',
      'prompt' => 'A prompt for node articles.',
      'ai_task' => 'seo_description',
      'model' => 'openai__gpt-4o',
      'uid' => $owner->id(),
      'entity_context' => ['node.article', 'node.*', 'media.image'],
    ])->save();

    $storage = $this->container->get('entity_type.manager')->getStorage('ai_prompt_content');

    $specific_bundle_ids = $storage->getQuery()
      ->condition('entity_context', 'node.article', 'IN')
      ->accessCheck(FALSE)
      ->execute();
    $this->assertCount(1, $specific_bundle_ids);

    $all_bundle_ids = $storage->getQuery()
      ->condition('entity_context', 'node.*', 'IN')
      ->accessCheck(FALSE)
      ->execute();
    $this->assertCount(1, $all_bundle_ids);

    $unrelated_ids = $storage->getQuery()
      ->condition('entity_context', 'taxonomy_term.*', 'IN')
      ->accessCheck(FALSE)
      ->execute();
    $this->assertCount(0, $unrelated_ids);
  }

}
