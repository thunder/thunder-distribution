# Changelog

## [7.1.7](https://github.com/thunder/thunder-distribution/tree/7.1.7) 2024-02-14

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.5...7.1.6)

* Add patch to fix [Checkbox for Media library modal missing after search](https://www.drupal.org/project/drupal/issues/3388913)

## [7.1.6](https://github.com/thunder/thunder-distribution/tree/7.1.6) 2024-01-09

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.5...7.1.6)

* Allow update_helper version "^4.0" in composer.json

## [7.1.5](https://github.com/thunder/thunder-distribution/tree/7.1.5) 2023-12-21

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.4...7.1.5)

* Improved update path from Thunder 6 to 7

## [7.1.4](https://github.com/thunder/thunder-distribution/tree/7.1.4) 2023-12-04

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.3...7.1.4)

* Fix possible break of Thunder GraphQL schema with drupal/graphql:4.6.0
* Fix warning on missing entityLinks keys in GraphQL

## [7.1.3](https://github.com/thunder/thunder-distribution/tree/7.1.3) 2023-11-07

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.2...7.1.3)

* Update to gin rc7
* Fix issue with form fields for media
* Bump Drupal version number in thunder.profile
* Update focal point patch

## [7.1.2](https://github.com/thunder/thunder-distribution/tree/7.1.2) 2023-09-01

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.1...7.1.2)

* Fix yaml error in xymatic config.

## [7.1.1](https://github.com/thunder/thunder-distribution/tree/7.1.1) 2023-08-28

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.0...7.1.1)

* Update Gin to RC5.
* Change paragraphs_feature requirement to ^2.0.0-beta3.
* Remove all entity browser permissions in all roles during the Thunder 6 to 7 migration.

## [7.1.0](https://github.com/thunder/thunder-distribution/tree/7.1.0) 2023-07-03

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.0.0...7.1.0)

* Drupal 10.1 compatibility.

## [7.0.0](https://github.com/thunder/thunder-distribution/tree/7.0.0) 2023-06-15

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.0.0-beta2...7.0.0)

* Add decoratable type resolver for GraphQL.
* Add integration for the Xymatic module.
* A new content type "News Article", that is similar to the "Article" content type, but has different metadata.

## [7.0.0-beta2](https://github.com/thunder/thunder-distribution/tree/7.0.0-beta2) 2023-03-13

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.0.0-beta1...7.0.0-beta2)

Remove merged Gin patches and update to latest Gin release candidate.

## [7.0.0-beta1](https://github.com/thunder/thunder-distribution/tree/7.0.0-beta1) 2023-03-09

First beta of Thunder 7.0.0 with Drupal 10 support.

Besides being Drupal 10 compatible the most notable changes are the retirement of the Thunder admin theme in favor
of the community driven Gin theme and the switch from Entity Browser to Drupal core Media Library.

Manual update steps from Thunder 6 are required and can be found here:

[Migrate Thunder 6 to Thunder 7](https://thunder.github.io/developer-guide/migration/migrate-6-7.html)
