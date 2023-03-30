# Changelog

## [6.5.4](https://github.com/thunder/thunder-distribution/tree/6.5.4) 2023-03-30

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.5.3...6.5.4)

* This release adds an integration for the [Xymatic](https://www.drupal.org/project/xymatic/) module.
* A new content type "News Article", that is similar to the "Article" content type, but has different metadata.

## [6.5.3](https://github.com/thunder/thunder-distribution/tree/6.5.3) 2023-03-01

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.5.2...6.5.3)

This is a security release of Thunder.

## [6.5.2](https://github.com/thunder/thunder-distribution/tree/6.5.2) 2023-02-23

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.5.1...6.5.2)

* [Fix Attempted to create an instance of field with name field_meta_tags on entity type node when the field storage does not exist](https://www.drupal.org/node/3340586)
* [Patch too fix "Call to a member function mainPropertyName() on null"](https://www.drupal.org/issues/3179172)
* Fix caching of GraphQl sub request data producers.

## [6.5.1](https://github.com/thunder/thunder-distribution/tree/6.5.1) 2023-01-19

Adding Entity Browser patch for SA-CONTRIB-2023-002. We cannot update entity browser, so we have to backport the patch.
Our Entity Browser will still be shown as vulnerable, but it is fixed with this patch.

## [6.5.0](https://github.com/thunder/thunder-distribution/tree/6.5.0) 2023-01-05

Minor release, that bumps the Drupal core dependency to 9.5.x.
