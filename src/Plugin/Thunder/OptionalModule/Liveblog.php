<?php

namespace Drupal\thunder\Plugin\Thunder\OptionalModule;

/**
 * Liveblog integration.
 *
 * @ThunderOptionalModule(
 *   id = "thunder_liveblog",
 *   label = @Translation("Liveblog"),
 *   description = @Translation("The Liveblog module allows you to distribute blog posts to thousands of users in realtime."),
 *   modules = {"thunder_liveblog"},
 * )
 */
class Liveblog extends AbstractOptionalModule {}
