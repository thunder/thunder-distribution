<?php

namespace Drupal\thunder_media\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\pathauto\AliasCleanerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for file upload events.
 */
class FileEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The alias cleaner service.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface|null
   */
  protected $aliasCleaner;

  /**
   * FileEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\pathauto\AliasCleanerInterface|null $aliasCleaner
   *   The alias cleaner service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AliasCleanerInterface $aliasCleaner = NULL) {
    $this->config = $configFactory->get('thunder_media.settings');
    $this->aliasCleaner = $aliasCleaner;
  }

  /**
   * Sanitize a filename during upload.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   The file upload event.
   */
  public function sanitizeFilename(FileUploadSanitizeNameEvent $event) {
    if ($this->config->get('enable_filename_transliteration')) {
      $pathinfo = pathinfo($event->getFilename());

      // Check for needed pathinfo array keys.
      if (!empty($pathinfo['filename']) && !empty($pathinfo['extension'])) {
        $cleanFilename = $this->aliasCleaner->cleanString($pathinfo['filename']) . '.' . $pathinfo['extension'];
        $event->setFilename($cleanFilename);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FileUploadSanitizeNameEvent::class => 'sanitizeFilename',
    ];
  }

}
