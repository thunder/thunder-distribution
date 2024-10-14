# Changelog

## [7.3.6](https://github.com/thunder/thunder-distribution/tree/7.3.6) 2024-10-04

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.3.5...7.3.6)

Update diff module to 1.8

## [7.3.5](https://github.com/thunder/thunder-distribution/tree/7.3.5) 2024-09-24

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.3.3...7.3.5)

Allow scheduler version ^3.0 in composer.json

## [7.3.3](https://github.com/thunder/thunder-distribution/tree/7.3.3) 2024-08-22

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.3.2...7.3.3)

Add search api GraphQl schema and data producer.

## [7.3.2](https://github.com/thunder/thunder-distribution/tree/7.3.2) 2024-08-14

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.3.1...7.3.2)

* [Issue #3462165: Add focal_point patch](https://www.drupal.org/node/3462165)

## [7.3.1](https://github.com/thunder/thunder-distribution/tree/7.3.1) 2024-06-024

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.3.0...7.3.1)

Add patches for upstream issues.

* [Issue #3465364: Fatal error when changing password when password_policy_history is enabled](https://www.drupal.org/project/password_policy/issues/3465364)
* [Issue #3455558: There is no visible change to a toggle when pressed (but it does trigger conditional fields, value is saved, etc)](https://www.drupal.org/project/gin/issues/3455558)

## [7.3.0](https://github.com/thunder/thunder-distribution/tree/7.1.0) 2024-06-024

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.2.2...7.3.0)

* Drupal 10.3 compatibility.
* Updated Gin theme.
* PHP8.3 compatibility.

## [7.2.2](https://github.com/thunder/thunder-distribution/tree/7.2.2) 2024-04-30

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.2.1...7.2.2)

* Fix menu links active trail data producer
* Update simple_sitemap and diff modules
* Bring back paragraphs split!
* [Possible break of Thunder GraphQL schema with drupal/graphql:4.6.0](https://www.drupal.org/node/3401211)

## [7.2.1](https://github.com/thunder/thunder-distribution/tree/7.2.1) 2024-04-10

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.2.0...7.2.1)

* Fix thunder redirect data producer with query strings.
* Move xymatic GraphQL schema to base.
* Update to gin rc9.
* Update graphql module to 4.7.0 and remove patch.

## [7.2.0](https://github.com/thunder/thunder-distribution/tree/7.2.0) 2024-03-07

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.7...7.2.0)

Support Drupal 10.2

## [7.1.7](https://github.com/thunder/thunder-distribution/tree/7.1.7) 2024-02-14

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/7.1.6...7.1.7)

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
