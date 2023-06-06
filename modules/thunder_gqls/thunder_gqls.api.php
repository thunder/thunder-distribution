<?php

/**
 * @file
 * Describes hooks provided by the Thunder GraphQL schema module.
 */

use Drupal\media\MediaInterface;

/**
 * Overrides the type resolver.
 *
 * This hook can be used to override the type resolver. By default, the
 * type is determined by the bundle of the entity.
 *
 * @param string $interface
 *  The interface to resolve for. Could be Page, Media or Paragraph
 * @param mixed $value
 *  The value to resolve. This is usually an entity of some sort.
 * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
 *   The resolve context.
 * @param \GraphQL\Type\Definition\ResolveInfo $info
 *   The resolve information.
 *
 * @return string|null
 *   The resolved type. Return NULL to leave default implementation
 *   or be determined by another implementation. First hook to return a value
 *   wins.
 */
function hook_thunder_gqls_type_resolver(string $interface, mixed $value, \Drupal\graphql\GraphQL\Execution\ResolveContext $context, \GraphQL\Type\Definition\ResolveInfo $info): ?string {
  if ($interface !== 'Page') {
    return NULL;
  }
  if (!$value instanceof \Drupal\node\NodeInterface) {
    return NULL;
  }
  // Bundle of type page can not resolve to the interface name. We need to
  // return something else.
  if ($value->bundle() === 'page') {
    return 'BasicPage';
  }
  return NULL;
}
