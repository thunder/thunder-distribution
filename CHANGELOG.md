# Changelog

## [6.3.8](https://github.com/thunder/thunder-distribution/tree/6.3.8) 2022-06-20

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.7...6.3.8)

Olivero is now our default frontend Theme, and we have a fix for menus in GraphQL.

- [Use olivero as Thunder's new frontend theme](https://www.drupal.org/node/3281046)
- [GraphQL Menus are not working for 404 urls](https://www.drupal.org/node/3281562)

## [6.3.7](https://github.com/thunder/thunder-distribution/tree/6.3.7) 2022-04-02

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.6...6.3.7)

The experimental paragraphs paste feature was added.

To test the feature enable the thunder_paragraphs_paste module. The UX and documentation of this feature is not very
fleshed out, but the general idea is, that you can text with multiple paragraphs, some textile markup and URLs to
embeds into a single field on your article edit and all necessary paragraphs will be created for you.

- [Paragraphs paste feature](https://www.drupal.org/node/2908496)

## [6.3.6](https://github.com/thunder/thunder-distribution/tree/6.3.6) 2022-03-24

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.5...6.3.6)

GraphQL Fixes and module updates.

- [Update locked modules](https://www.drupal.org/node/3271453)
- [GraphQL Menus are not working for views](https://www.drupal.org/node/3270689)

## [6.3.5](https://github.com/thunder/thunder-distribution/tree/6.3.5) 2022-03-15

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.4...6.3.5)

Thunder and its dependencies are now PHP 8.1 compatible, a first little step to Drupal 10!
Our basic page finally got a little love, which it definitely deserved. It now has paragraphs support and metatags.

This leads to backward compatibility problems with the removed body field and the changed GraphQL schema. To resolve
these issues, we will only add the paragraphs field on the update, but not remove the body field. The body field will be
considered to be deprecated.

We cannot really get a fully backward compatible GraphQl schema, but we added an optional schema extension that will
expose the body field as "body" instead of content.

- [PHP8.1 compatibility](https://www.drupal.org/node/3265222)
- [Improved basic page](https://www.drupal.org/node/3269389)

## [6.3.4](https://github.com/thunder/thunder-distribution/tree/6.3.4) 2022-02-17

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.3...6.3.4)

Maintenance release to remove patch for the graphql module, that was merged and prevents installation.

## [6.3.3](https://github.com/thunder/thunder-distribution/tree/6.3.3) 2022-01-26

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.2...6.3.3)

Drush 11 compatibility and improved entity browser integration for single images.

## [6.3.2](https://github.com/thunder/thunder-distribution/tree/6.3.2) 2022-01-24

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.1...6.3.2)

We added two new features and some bug fixes in this release.

The schema_metatag module has finally been added to Thunder. More Importantly, we added a new feature for fast access to
your favorite paragraph types!

- Fix [Invalid token used in metatag config](https://www.drupal.org/node/3260090)
- Fix [Conflict with hierarchy manager](https://www.drupal.org/node/3255519)
- Documentation
  update [The module thunder_riddle is missing after upgrading from thunder 3 to 6](https://www.drupal.org/node/3244796)
- Add [Structured data for Thunder article](https://www.drupal.org/node/3259163)
- Add [paragraphs_features quick links functionality](https://www.drupal.org/node/3259071)

## [6.3.1](https://github.com/thunder/thunder-distribution/tree/6.3.1) 2021-12-20

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.0...6.3.1)

Bugfix release to fix issues of twig filter using \Drupal::service('renderer')->render($element) and allowing newer
versions of facets module.

- Fix [Plain text twig filter throws an error if field does not exist or is hidden](https://www.drupal.org/node/3253753)
- Fix [Facets module > 1.4 not supported](https://www.drupal.org/node/3254295)

## [6.3.0](https://github.com/thunder/thunder-distribution/tree/6.3.0) 2021-12-09

Minor release, that bumps the Drupal core dependency to 9.3.x.
