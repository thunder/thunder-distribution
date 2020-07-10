<?php

namespace Drupal\thunder\Plugin\Thunder\OptionalModule;

/**
 * Password.
 *
 * @ThunderOptionalModule(
 *   id = "thunder_password_policy",
 *   label = @Translation("Password integration"),
 *   description = @Translation("Add the possibility to define more sophisticated password policies."),
 *   modules = {"thunder_password_policy"},
 * )
 */
class PasswordPolicy extends AbstractOptionalModule {}
