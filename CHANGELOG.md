# Changelog

## [8.3.5](https://github.com/thunder/thunder-distribution/tree/8.3.5) 2026-05-13

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.3.4...8.3.5)

- Fix ThunderRedirect cache metadata missing for 200 responses.
- **Prepare for GraphQL 5 stable release** — see details below.
- Add `hasNext` field to `EntityList` GraphQL type.
- Fix test stability: stop relying on external Pinterest `pinit.js`.
- Fix pathauto URL alias patterns (add leading `/`); pin `drupal/vgwort` to `^3.0.2`.

### Preparing for GraphQL 5 stable (`drupal/graphql ^5.0@RC`)

The `drupal/graphql` constraint has been updated from `5.0.0-beta2` to `^5.0@RC`.
This allows Thunder (and sites depending on it) to consume RC releases and, once
published, the stable `5.0.0` release without a further Thunder update.

#### What downstream projects must be aware of

1. **Custom schema extension plugins need to be updated.**
   If your project provides classes that extend
   `ThunderSchemaExtensionPluginBase` or implement `SdlSchemaPluginInterface`
   directly, two breaking API changes apply:
   - Replace any `getSchemaDefinition(): string` / `getBaseDefinition(): string` /
     `getExtensionDefinition(): string` return types with `GraphQL\Language\Source`
     (or `?Source` for optional definitions).
   - Replace `getResolverRegistry()` with
     `registerResolvers(ResolverRegistryInterface $registry): void` and move
     resolver registration into that method body instead of returning a registry
     object.

2. **Migrate inline `callback()` resolvers to DataProducer plugins.**
   GraphQL 5 deprecates anonymous inline callbacks as field resolvers. While
   Thunder ships a set of ready-made producers you can reuse (`array_value`,
   `entity_reference_item`, `image_derivative_src`, etc.), any custom resolver
   that still uses `$this->builder->callback(fn(...) => ...)` should be converted
   to a dedicated `@DataProducer` plugin.

3. **Remove empty `.graphqls` stub files.**
   GraphQL 5 no longer requires (or accepts) empty schema definition files as
   placeholders. If your modules ship `.base.graphqls` or `.extension.graphqls`
   files that contain no SDL, delete them and update your plugin's
   `getBaseDefinition()` / `getExtensionDefinition()` to return `null`.

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
