# Changelog

## [6.3.2](https://github.com/thunder/thunder-distribution/tree/6.3.2) 2022-01-24

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.1...6.3.2)

We added two new features and some bug fixes in this release.

The schema_metatag module has finally been added to Thunder. More Importantly, we added a new feature for fast access to your
favorite paragraph types!

- Fix [Invalid token used in metatag config](https://www.drupal.org/node/3260090)
- Fix [Conflict with hierarchy manager](https://www.drupal.org/node/3255519)
- Documentation update [The module thunder_riddle is missing after upgrading from thunder 3 to 6](https://www.drupal.org/node/3244796)
- Add [Structured data for Thunder article](https://www.drupal.org/node/3259163)
- Add [paragraphs_features quick links functionality](https://www.drupal.org/node/3259071)

## [6.3.1](https://github.com/thunder/thunder-distribution/tree/6.3.1) 2021-12-20

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.3.0...6.3.1)

Bugfix release to fix issues of twig filter using \Drupal::service('renderer')->render($element) and
allowing newer versions of facets module.

- Fix [Plain text twig filter throws an error if field does not exist or is hidden](https://www.drupal.org/node/3253753)
- Fix [Facets module > 1.4 not supported](https://www.drupal.org/node/3254295)

## [6.3.0](https://github.com/thunder/thunder-distribution/tree/6.3.0) 2021-12-09

Minor release, that bumps the Drupal core dependency to 9.3.x.
