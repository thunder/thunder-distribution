# Changelog

## [3.4.8](https://github.com/thunder/thunder-distribution/tree/3.4.8) 2020-06-18
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.7...3.4.8)

Update tarball to include latest security update and updating entity browser and update helper

- Do [Update entity browser to 2.5](https://www.drupal.org/node/3146606)

## [3.4.7](https://github.com/thunder/thunder-distribution/tree/3.4.7) 2020-06-04
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.6...3.4.7)

Fixing the download URL for the slick_media module in order to get the tarball built.

## [3.4.6](https://github.com/thunder/thunder-distribution/tree/3.4.6) 2020-06-04
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.5...3.4.6)

- Do [Move thunder_amp theme to d.o.](https://www.drupal.org/node/3128263)
- Do [Disable slick_media for new installations](https://www.drupal.org/node/3105337)
- Do [Update redirect module](https://www.drupal.org/node/3137492)
- Fix [Paragraph preview does not respect focal point settings](https://www.drupal.org/node/3083372)

## [3.4.5](https://github.com/thunder/thunder-distribution/tree/3.4.5) 2020-04-16
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.4...3.4.5)

Some bug fixes and module updates. Most notably we updated the entity browser to version 2.4. This update does not work
with the inline entity form field widget we used for the image and video paragraphs. For this reason, we changed the UX
those paragraphs and use the rendered entity display instead. The entity form opens in a modal.

- Fix [Upgrade testing](https://www.drupal.org/node/3127775)
- Fix [Installation from the browser fails](https://www.drupal.org/node/3122269)
- Do [Update to Entity Browser 2.4](https://www.drupal.org/node/3127484)
- Do [Update password policy module](https://www.drupal.org/node/3127479)
- Add [Content overview page test](https://www.drupal.org/node/3045492)

## [3.4.4](https://github.com/thunder/thunder-distribution/tree/3.4.4) 2020-03-20
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.3...3.4.4)

Small fixes and updates. In preparation of a future drop of facebook instant articles support,
we moved the integration code out into a separate module, which can be used by people, who want to
continue using it.

- Fix [Inconsistent order of paragraph types in add dialog](https://www.drupal.org/node/3117401)
- Do [Update blazy module](https://www.drupal.org/node/3118911)
- Do [Move facebook integration to its own module](https://www.drupal.org/node/3059036)
- Do [Disable image upload button in ckeditor](https://www.drupal.org/node/2875691)

## [3.4.3](https://github.com/thunder/thunder-distribution/tree/3.4.3) 2020-03-05
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.2...3.4.3)

Fixes and improvements related to the paragraphs module 1.11 release.

- Fix [Performance tests are failing with paragraphs 1.11](https://www.drupal.org/node/3115061)
- Do [Fix test failures](https://www.drupal.org/node/3114591)
- Do [Update to Paragraphs features 8.x-1.8](https://www.drupal.org/node/3116379)

## [3.4.2](https://github.com/thunder/thunder-distribution/tree/3.4.2) 2020-01-22
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.1...3.4.2)

Adding the autofill feature to Thunder. Autofill can be configured to copy the input of one field into another field
while typing. We use it to autofill the SEO title with the title value.

- Add [Autofill SEO field with title values, when initially being empty](https://www.drupal.org/node/3061243)
- Fix [Add missing modules in update dependencies](https://www.drupal.org/node/3102123)
- Fix [Metatag test fails with 1.11 release](https://www.drupal.org/node/3104801)
- Do [Update redirect module](https://www.drupal.org/node/3104813)

## [3.4.1](https://github.com/thunder/thunder-distribution/tree/3.4.1) 2019-12-19
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.4.0...3.4.1)

This release updates the Drupal version to 8.8.1, which is a security update. Following issues were resolved as well:

 - [Thunder 3.4 tests are failing with simple_sitemap 3.5 (fix composer min tests)](https://www.drupal.org/node/3101277)
 - [Fix deprecations and coding style issues](https://www.drupal.org/node/3100288)

## [3.4.0](https://github.com/thunder/thunder-distribution/tree/3.4.0) 2019-12-05

Add Drupal 8.8 compatibility

- Fix [Preview of transition to unpublished state creates errors on edit form.](https://www.drupal.org/node/3016921)
- Do [Make Thunder 8.8.x compatible](https://www.drupal.org/node/3089624)
