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
class IvwIntegration extends AbstractOptionalModule {}
