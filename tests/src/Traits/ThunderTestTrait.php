<?php

namespace Drupal\Tests\thunder\Traits;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\dblog\Controller\DbLogController;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Use this trait to reuse an existing database.
 */
trait ThunderTestTrait {

  /**
   * {@inheritdoc}
   */
  public function installDrupal(): void {
    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();
    $request = Request::createFromGlobals();
    $container = $this->initKernel($request);
    $this->initConfig($container);

    // Add the config directories to settings.php.
    $sync_directory = Settings::get('config_sync_directory');
    \Drupal::service('file_system')->prepareDirectory($sync_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Ensure the default temp directory exist and is writable. The configured
    // temp directory may be removed during update.
    \Drupal::service('file_system')->prepareDirectory($this->tempFilesDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $this->installDefaultThemeFromClassProperty($container);
    $this->installModulesFromClassProperty($container);
    $this->rebuildAll();
    $this->replaceUser1();
  }

  /**
   * Replace User 1 with the user created here.
   */
  protected function replaceUser1(): void {
    /** @var \Drupal\user\UserInterface $account */
    // @todo Saving the account before the update is problematic.
    // https://www.drupal.org/node/2560237
    $account = User::load(1);
    $account->setPassword($this->rootUser->pass_raw);
    $account->setEmail($this->rootUser->getEmail());
    $account->setUsername($this->rootUser->getAccountName());
    $account->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings(): void {
    $settings = [];
    parent::prepareSettings();

    // Remember the profile which was used.
    $settings['settings']['install_profile'] = (object) [
      'value' => $this->profile,
      'required' => TRUE,
    ];
    // Generate a hash salt.
    $settings['settings']['hash_salt'] = (object) [
      'value'    => Crypt::randomBytesBase64(55),
      'required' => TRUE,
    ];

    // Since the installer isn't run, add the database settings here too.
    $settings['databases']['default'] = (object) [
      'value' => Database::getConnectionInfo(),
      'required' => TRUE,
    ];

    // Force every update hook to only run one entity per batch.
    $settings['entity_update_batch_size'] = (object) [
      'value' => 1,
      'required' => TRUE,
    ];

    // Set up sync directory.
    $settings['settings']['config_sync_directory'] = (object) [
      'value' => $this->publicFilesDirectory . '/config_sync',
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function doInstall(): void {
    if (empty($_SERVER['thunderDumpFile']) || !file_exists($_SERVER['thunderDumpFile'])) {
      parent::doInstall();
      return;
    }

    if (str_ends_with($_SERVER['thunderDumpFile'], '.php')) {
      require $_SERVER['thunderDumpFile'];
    }

    if (str_ends_with($_SERVER['thunderDumpFile'], '.tar.gz')) {
      // Extract tar.gz file to public files' directory.
      $command = sprintf('LANG=en_US.UTF-8 LC_ALL=en_US.UTF-8 tar -xzf %s -C %s', $_SERVER['thunderDumpFile'], $this->siteDirectory);
      exec($command);
      require $this->siteDirectory . '/database-dump.php';
    }
  }

  /**
   * LogIn with defined role assigned to user.
   *
   * @param string $role
   *   Role name that will be assigned to user.
   *
   * @return \Drupal\user\Entity\User
   *   The newly created user.
   */
  protected function logWithRole(string $role): User {
    $editor = $this->drupalCreateUser();
    $editor->addRole($role);
    $editor->save();
    $this->drupalLogin($editor);
    return $editor;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message', 'variables']);
    $andGroup = $query->andConditionGroup()
      ->condition('severity', '5', '<')
      ->condition('type', 'php');
    $group = $query->orConditionGroup()
      ->condition('severity', '4', '<')
      ->condition($andGroup);
    $query->condition($group);
    $query->groupBy('w.message');
    $query->groupBy('w.variables');

    $controller = DbLogController::create($this->container);

    // Check that there are no warnings in the log after installation.
    // $this->assertEqual($query->countQuery()->execute()->fetchField(), 0);.
    if ($query->countQuery()->execute()->fetchField()) {
      // Output all errors for modules tested.
      $errors = [];
      foreach ($query->execute()->fetchAll() as $row) {
        $errors[] = Unicode::truncate(Html::decodeEntities(strip_tags((string) $controller->formatMessage($row))), 256, TRUE, TRUE);
      }
      throw new \Exception(print_r($errors, TRUE));
    }

    parent::tearDown();
  }

  /**
   * Load media by UUID.
   *
   * @param string $uuid
   *   The uuid.
   *
   * @return \Drupal\media\Entity\Media
   *   The media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loadMediaByUuid(string $uuid): MediaInterface {
    $media = \Drupal::getContainer()->get('entity.repository')->loadEntityByUuid('media', $uuid);
    assert($media instanceof MediaInterface);
    return $media;
  }

  /**
   * Load node by UUID.
   *
   * @param string $uuid
   *   The uuid.
   *
   * @return \Drupal\node\Entity\Node
   *   The node entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loadNodeByUuid(string $uuid): NodeInterface {
    $node = \Drupal::getContainer()->get('entity.repository')->loadEntityByUuid('node', $uuid);
    assert($node instanceof NodeInterface);
    return $node;
  }

  /**
   * Load term by UUID.
   *
   * @param string $uuid
   *   The uuid.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loadTermByUuid(string $uuid): TermInterface {
    $term = \Drupal::getContainer()->get('entity.repository')->loadEntityByUuid('taxonomy_term', $uuid);
    assert($term instanceof TermInterface);
    return $term;
  }

  /**
   * Get a media item from the database based on its name.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $name
   *   A media name, usually generated by $this->randomMachineName().
   * @param bool $reset
   *   (optional) Whether to reset the entity cache.
   *
   * @return \Drupal\media\MediaInterface|bool
   *   A media entity matching $name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMediaByName($name, bool $reset = FALSE) {
    if ($reset) {
      \Drupal::entityTypeManager()->getStorage('media')->resetCache();
    }
    // Cast MarkupInterface objects to string.
    $name = (string) $name;
    $medias = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadByProperties(['name' => $name]);
    return reset($medias);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanupEnvironment(): void {
    // No need to cleanup on CI.
    if (!getenv('SKIP_TEST_CLEANUP')) {
      parent::cleanupEnvironment();
    }
  }

}
