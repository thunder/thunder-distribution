<?php

namespace Drupal\thunder\Plugin\Thunder\OptionalModule;

/**
 * Password.
 *
 * @ThunderOptionalModule(
 *   id = "thunder_password_policy",
 *   label = @Translation("Password integration"),
 *   description = @Translation("Add the possibility to define more sophisticated password policies."),
 *   modules = {"password_policy", "password_policy_length", "password_policy_history", "password_policy_character_types"},
 * )
 */
class Password extends AbstractOptionalModule {}
