<?php

namespace Drupal\thunder\Plugin\Thunder\OptionalModule;

/**
 * Thunder Demo Content.
 *
 * @ThunderOptionalModule(
 *   id = "thunder_demo",
 *   label = @Translation("Thunder Demo Content"),
 *   description = @Translation("Installs demo content to show how Thunder works."),
 *   modules = {"thunder_demo"},
 *   standardlyEnabled = TRUE,
 *   weight = -1
 * )
 */
class ThunderDemo extends AbstractOptionalModule {}
