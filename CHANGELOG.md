# Changelog

## [3.3.8](https://github.com/thunder/thunder-distribution/tree/3.3.1) 2019-09-05
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.7...3.3.8)

This release updates the Drupal version to 8.7.11, which is a security update. Following issues were resolved as well:

 - [Thunder 3.3 tests are failing with simple_sitemap 3.5](https://www.drupal.org/node/3101277)
 - [Thunder 3.3 tests are failing with paragraphs features 1.17](https://www.drupal.org/node/3100643)
 - [Fix deprecations and coding style issues](https://www.drupal.org/node/3100288)

## [3.3.7](https://github.com/thunder/thunder-distribution/tree/3.3.7) 2019-12-05
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.6...3.3.7)

Update of admin toolbar module.

- Do [Update Admin Toolbar module to 8.x-2.x](https://www.drupal.org/node/3097026)

## [3.3.6](https://github.com/thunder/thunder-distribution/tree/3.3.6) 2019-11-26
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.5...3.3.6)

Fixing bugs and bumping Drupal core version.

- Fix [Follow-up for "Fix field_group update": Check if field groups exists](https://www.drupal.org/node/3087938)
- Fix [Do not install thunder_demo in tests](https://www.drupal.org/node/3094367)

## [3.3.5](https://github.com/thunder/thunder-distribution/tree/3.3.5) 2019-11-07
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.4...3.3.5)

Maintenance release with improved performance test coverage and update to most recent Drupal release.

- Do [Create a performance test for editing nodes](https://www.drupal.org/node/3089695)
- Do [Create performance test for measuring select operations](https://www.drupal.org/node/3092267)
- Do [Add creation of paragraphs to performance tests](https://www.drupal.org/node/3092996)

## [3.3.4](https://github.com/thunder/thunder-distribution/tree/3.3.4) 2019-10-23
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.3...3.3.4)

Fixes removing of image paragraphs.

- Fix [Cannot remove image media from image paragraph](https://www.drupal.org/node/3088809)

## [3.3.3](https://github.com/thunder/thunder-distribution/tree/3.3.3) 2019-10-15
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.2...3.3.3)

Bump drupal core and entity browser version. Unlock scheduler version and set new defaults for translatable fields.

- Do [Update entity_browser to 8.x-2.2](https://www.drupal.org/node/3065999)
- Do [Remove scheduler patch](Remove scheduler patch)
- Do [Set sane defaults for 'translatable' property and add some tests](https://www.drupal.org/node/2918993)
- Do [Use phpunit instead of run-tests.sh in the CI](https://www.drupal.org/node/3083508)

## [3.3.2](https://github.com/thunder/thunder-distribution/tree/3.3.2) 2019-09-25
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.1...3.3.2)

We added autosave to Thunder and updated the Inline Entity Form Module. We introduced a UX change by removing the
Replace button for the Entity Browser.

- Add [Automatic saving of content](https://www.drupal.org/node/2828088)
- Do [Update Inline Entity Form Module](https://www.drupal.org/node/3083045)
- Fix [Entity Browser replace functionality could lead to a bad UX](https://www.drupal.org/node/3080403)
- Fix [Focal points in default content](https://www.drupal.org/node/3083350)

## [3.3.1](https://github.com/thunder/thunder-distribution/tree/3.3.1) 2019-09-05
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.0...3.3.1)

Update to Drupal 8.7.7 and Scheduler 1.1.

## [3.3.0](https://github.com/thunder/thunder-distribution/tree/3.3.0) 2019-08-27
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.0-rc1...3.3.0)

Thunder 3.0 is out! Since the rc1 we were able to update the paragraphs module to version 1.9 and the field groups
module to version 3.0-rc1.
If you are updating from Thunder 2, please have look at our [update documentation](https://thunder.github.io/thunder-documentation/update-2-to-3).

- Do [Consider upgrading field_group module](https://www.drupal.org/node/3059646)
- Do [Update to Paragraphs 1.9](https://www.drupal.org/node/3042078)
- Do [Remove deprecated code for checklist api integration](https://www.drupal.org/node/3040952)
- Fix [Occasionally failing tests for paragraphs text split](https://www.drupal.org/node/3073791)

## [3.3.0-rc1](https://github.com/thunder/thunder-distribution/tree/3.3.0-rc1) 2019-08-08
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.0-beta3...3.3.0-rc1)

Making sure, that projects updating from Thunder 2 are on the latest Thunder 2 release.

- Task [Ensure you are coming from the latest Thunder 2 release](https://www.drupal.org/project/thunder/issues/3064515)

## [3.3.0-beta3](https://github.com/thunder/thunder-distribution/tree/3.3.0-beta3) 2019-07-29
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.0-beta2...3.3.0-beta3)

This release updates the contrib Metatag module version to 1.9, which is a security update.

- Fix [Do not show revisionlog message form on inline forms](https://www.drupal.org/project/thunder/issues/3055350)
- Task [Update documentation](https://www.drupal.org/project/thunder/issues/3069987)

## [3.3.0-beta2](https://github.com/thunder/thunder-distribution/tree/3.3.0-beta2) 2019-07-18
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.3.0-beta1...3.3.0-beta2)

This release updates the Drupal version to 8.7.5, which is a security update. No features or fixes are included in this
release.

## [3.3.0-beta1](https://github.com/thunder/thunder-distribution/tree/3.3.0-beta1) 2019-07-08

The first public release of Thunder 3. Feature-wise it is currently identical to Thunder 2, but uses Drupal media instead
of the deprecated media_entity module. New installations should prefer this version to Thunder 2.
Update from Thunder 2 is not fully automated, several manual steps have to be executed, they are described here:

[How to update Thunder 2 to Thunder 3](https://thunder.github.io/thunder-documentation/update-2-to-3)

From now on, new features will only be added to Thunder 3. Thunder 2 will be maintained as long as Drupal 8.7 is
maintained.
Thunder 2 will not work with Drupal 9, you should consider updating as soon as possible!
