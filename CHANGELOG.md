# Changelog

## [8.3.5](https://github.com/thunder/thunder-distribution/tree/8.3.5) 2026-05-13

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.3.4...8.3.5)

- Fix ThunderRedirect cache metadata missing for 200 responses.
- **Prepare for GraphQL 5 stable release** — see details below.
- Add `hasNext` field to `EntityList` GraphQL type.
- Fix test stability: stop relying on external Pinterest `pinit.js`.
- Fix pathauto URL alias patterns (add leading `/`); pin `drupal/vgwort` to `^3.0.2`.

### Preparing for GraphQL 5 stable (`drupal/graphql ^5.0@RC`)

`drupal/graphql` is updated from `5.0.0-beta2` to `^5.0@RC`, allowing RC and eventual stable releases to be picked up
automatically. If your project has custom GraphQL schema extension plugins, be aware of these breaking changes:

1. **Update schema plugin API.** Classes extending `ThunderSchemaExtensionPluginBase` or implementing
   `SdlSchemaPluginInterface` must return `GraphQL\Language\Source` (not `string`) from
   `getSchemaDefinition()` / `getBaseDefinition()` / `getExtensionDefinition()`, and replace
   `getResolverRegistry()` with `registerResolvers(ResolverRegistryInterface $registry): void`.

2. **Replace inline `callback()` resolvers with DataProducer plugins.** Use one of the new built-in producers
   (`array_value`, `entity_reference_item`, `image_derivative_src`, etc.) or create your own `@DataProducer` plugin.

3. **Delete empty `.graphqls` stub files.** GraphQL 5 no longer needs them. Return `null` from
   `getBaseDefinition()` / `getExtensionDefinition()` instead.

## [8.3.4](https://github.com/thunder/thunder-distribution/tree/8.3.4) 2026-04-26

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.3.3...8.3.4)

- Pin GraphQL and Scheduler Content Moderation Integration modules to prevent braking changes to crawl in without a fix.

## [8.3.3](https://github.com/thunder/thunder-distribution/tree/8.3.3) 2026-03-05

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.3.2...8.3.3)

- Remove merged graphql patch to fix the composer install.

## [8.3.2](https://github.com/thunder/thunder-distribution/tree/8.3.2) 2026-02-17

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.3.1...8.3.2)

- Responsive preview for decoupled sites.

## [8.3.1](https://github.com/thunder/thunder-distribution/tree/8.3.1) 2026-01-30

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.3.0...8.3.1)

- Remove merged responsive_preview patch to fix the composer install.

## [8.3.0](https://github.com/thunder/thunder-distribution/tree/8.3.0) 2026-01-08

Support Drupal 11.3.
