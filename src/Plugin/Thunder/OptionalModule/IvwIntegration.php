<?php

namespace Drupal\thunder\Plugin\Thunder\OptionalModule;

/**
 * IVW Integration.
 *
 * @ThunderOptionalModule(
 *   id = "ivw_integration",
 *   label = @Translation("IVW Integration"),
 *   description = @Translation("Integration module for the German audience measurement organization IVW. Enabling the integration will add an IVW field to the article and the channel."),
 *   modules = {"ivw_integration"},
 * )
 */
class IvwIntegration extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function install(array $formValues, array &$context) {
    parent::install($formValues, $context);

    $fieldWidget = 'ivw_integration_widget';

    // Attach field if channel vocabulary and article node type is
    // present in the distribution.
    try {
      \Drupal::service('entity_display.repository')
        ->getFormDisplay('node', 'article', 'default')
        ->setComponent(
          'field_ivw', [
            'type' => $fieldWidget,
          ])->save();
    }
    catch (\Exception $e) {
      \Drupal::logger('thunder')
        ->info(t('Could not add ivw field to article node: "@message"', ['@message' => $e->getMessage()]));
    }

    try {
      \Drupal::service('entity_display.repository')
        ->getFormDisplay('taxonomy_term', 'channel', 'default')
        ->setComponent('field_ivw', [
          'type' => $fieldWidget,
        ])->save();
    }
    catch (\Exception $e) {
      \Drupal::logger('thunder')
        ->info(t('Could not add ivw field to channel taxonomy: "@message"', ['@message' => $e->getMessage()]));
    }

  }

}
