# Changelog

## [8.2.5](https://github.com/thunder/thunder-distribution/tree/8.2.3) 2025-08-28

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.2.4...8.2.5)

- Fix drupal/facets to 3.0.0 until we have made Thunder compatible with 3.0.1
- Remove gin patch (it was only needed in combination with core navigation)
- Remove claro patch

## [8.2.4](https://github.com/thunder/thunder-distribution/tree/8.2.4) 2025-08-21

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.2.3...8.2.4)

- [Update graphql module](https://www.drupal.org/project/thunder/issues/3542384)
- [Add IEF patch to prevent empty media items](https://www.drupal.org/project/thunder/issues/3542377)

## [8.2.3](https://github.com/thunder/thunder-distribution/tree/8.2.3) 2025-08-13

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.2.2...8.2.3)

- Fix fromRoute GraphQL helper to not throw warnings on 404 routes

## [8.2.2](https://github.com/thunder/thunder-distribution/tree/8.2.2) 2025-08-06

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.2.1...8.2.2)

- Remove navigation bar and revert to admin toolbar

## [8.2.1](https://github.com/thunder/thunder-distribution/tree/8.2.1) 2025-07-25

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.2.0...8.2.1)

- Fix navigation bar with moderation items

## [8.2.0](https://github.com/thunder/thunder-distribution/tree/8.2.0) 2025-07-16

First release of Thunder 8 with Drupal 11 support.

Besides being compatible to Drupal 11, the most notable change is the removal of
several module dependencies.

Therefore, manual update steps for updating from Thunder 7 are required and can be found here:

[Migrate Thunder 7 to Thunder 8](https://thunder.github.io/developer-guide/migration/migrate-7-8.html)
