<?php

namespace Drupal\Tests\thunder\Traits;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\dblog\Controller\DbLogController;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Use this trait to reuse an existing database.
 */
trait ThunderTestTrait {

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['forms']['thunder_module_configure_form'] = ['install_modules_thunder_demo' => NULL];
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function installDrupal() {
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
    $this->deriveAllImageStyles();
  }

  /**
   * Pre-creates all image styles to avoid race conditions.
   *
   * @todo Remove once https://www.drupal.org/project/drupal/issues/3211936 is
   *   in core.
   */
  private function deriveAllImageStyles() {
    $extensions = ['jpeg', 'jpg', 'gif', 'png'];
    $mimetypes = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'];
    $regex = "^public:\/\/.*\.(" . implode('|', $extensions) . ")$";

    // Query the database for files that match this pattern.
    $query = Database::getConnection()
      ->select('file_managed', 'f');
    $andGroup = $query->andConditionGroup()
      ->condition('filemime', $mimetypes, 'IN')
      ->condition('uri', $regex, 'REGEXP');
    $query->condition($andGroup);

    // Select the files to have derivatives created.
    $files = $query->fields('f', ['fid', 'filename', 'uri'])
      ->execute()
      ->fetchAll();

    $count = 0;
    $image_styles = ImageStyle::loadMultiple();
    /** @var \Drupal\image\Entity\ImageStyle $style ImageStyle Object */
    foreach ($image_styles as $style) {
      foreach ($files as $file) {
        $destination = $style->buildUri($file->uri);
        if (!file_exists($destination)) {
          if ($style->createDerivative($file->uri, $destination)) {
            $count++;
          }
        }
      }
    }
    if ($count === 0) {
      $this->fail('Failed to pre-generated image styles');
    }
  }

  /**
   * Replace User 1 with the user created here.
   */
  protected function replaceUser1() {
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
  protected function prepareSettings() {
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
  protected function doInstall() {

    if (!empty($_SERVER['thunderDumpFile']) && file_exists($_SERVER['thunderDumpFile'])) {
      $file = $_SERVER['thunderDumpFile'];
      // Load the database.
      if (substr($file, -3) == '.gz') {
        $file = "compress.zlib://$file";
      }
      require $file;
    }
    else {
      parent::doInstall();
    }
  }

  /**
   * LogIn with defined role assigned to user.
   *
   * @param string $role
   *   Role name that will be assigned to user.
   */
  protected function logWithRole($role) {
    $editor = $this->drupalCreateUser();
    $editor->addRole($role);
    $editor->save();
    $this->drupalLogin($editor);
    return $editor;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message', 'variables']);
    $andGroup = $query->andConditionGroup()
      ->condition('severity', 5, '<')
      ->condition('type', 'php');
    $group = $query->orConditionGroup()
      ->condition('severity', 4, '<')
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
        $errors[] = Unicode::truncate(Html::decodeEntities(strip_tags($controller->formatMessage($row))), 256, TRUE, TRUE);
      }
      throw new \Exception(print_r($errors, TRUE));
    }

    parent::tearDown();
  }

}
