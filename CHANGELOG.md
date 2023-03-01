# Changelog

## [6.4.6](https://github.com/thunder/thunder-distribution/tree/6.4.6) 2023-03-01

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.4.5...6.4.6)

This is a security release of Thunder.

## [6.4.5](https://github.com/thunder/thunder-distribution/tree/6.4.5) 2023-01-19

Adding Entity Browser patch for SA-CONTRIB-2023-002. We cannot update entity browser, so we have to backport the patch.
Our Entity Browser will still be shown as vulnerable, but it is fixed with this patch.

## [6.4.4](https://github.com/thunder/thunder-distribution/tree/6.4.4) 2022-12-19

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.4.3...6.4.4)

### New Features

We added support for the METIS VG Wort counter. This counter is used by many german publishers to track the usage of
their content.
See: [VG Wort Integration](https://www.drupal.org/project/vgwort)

The media_file_delete module was added to be able to quickly remove image files.
See: [Add Media File Delete integration and new permission to delete any file](https://www.drupal.org/project/thunder/issues/3319701)

This release also updates some modules, that have fixed versions because of applied patches.

* drupal/diff
* drupal/password_policy
* drupal/field_group (no fixed version anymore)

## [6.4.3](https://github.com/thunder/thunder-distribution/tree/6.4.3) 2022-09-22

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.4.2...6.4.3)

Add ParagraphBehavior GraphQL data producer.

## [6.4.2](https://github.com/thunder/thunder-distribution/tree/6.4.2) 2022-08-25

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.4.1...6.4.2)

Add TikTok oembed in video paragraph and fix entity list queries in GraphQl implementation.

- [Fix GraphQL entity query with unpublished entities](https://www.drupal.org/node/3305447)
- [Add tiktok integration](https://www.drupal.org/node/3305451)

## [6.4.1](https://github.com/thunder/thunder-distribution/tree/6.4.1) 2022-08-02

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.4.0...6.4.1)

Patch release to fix schema of shipped config.

## [6.4.0](https://github.com/thunder/thunder-distribution/tree/6.4.0) 2022-06-20

Minor release, that bumps the Drupal core dependency to 9.4.x.
