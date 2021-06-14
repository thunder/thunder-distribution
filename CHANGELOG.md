# Changelog

## [6.1.4](https://github.com/thunder/thunder-distribution/tree/6.1.4) 2021-06-14
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.1.3...6.1.4)

With this release we require the exact version of th entity_reference_actions module, that is still working with
Drupal Version 9.1. Newer Versions of the module will require Drupal 9.2.

## [6.1.3](https://github.com/thunder/thunder-distribution/tree/6.1.3) 2021-05-10
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.1.2...6.1.3)

Having the metatag form on the node edit page is a huge performance hit on save, autosave and adding new paragraphs. To
improve those operations, we integrate the metatag_async_widget, which loads the metatag form on demand.

- [Use asynchronous widget for metatag handling](https://www.drupal.org/node/3208355)

## [6.1.2](https://github.com/thunder/thunder-distribution/tree/6.1.2) 2021-04-15
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.1.1...6.1.2)

We spend a lot of effort into improving the sitemap generation speed. We saw on larger sites, that generating the sitemap
took about 30 minutes and consumed a lot of memory.

We reduced that generation time to 3 minutes and heavily reduced memory usage as well. Additionally, the sitemap
generation does not clear the entity cache anymore.

More information about those fixes can be found in the release notes of simple_sitemap 3.10:
[simple_sitemap 8.x-3.10](https://www.drupal.org/project/simple_sitemap/releases/8.x-3.10)

If you experience similar Problems with the sitemap generation, we urge you to update and set the config value entities_per_dataset
in simple_sitemap.settings configuration to a value that fits to your circumstances. It will be set to 50 by default,
and that will already give you huge performance boosts, but depending on your server configuration you might try and
increase this value to gain even better performance.

Thunder is now PHP 8 ready!

- [Disable xsl on sitemap and bump simple_sitemap version](https://www.drupal.org/node/3208377)
- [PHP 8 support](https://www.drupal.org/node/3202526)
- [Set the media name by default to the image filename in dropzone upload widget](https://www.drupal.org/node/3200971)
- [Clean up content admin views](https://www.drupal.org/node/3185134)

## [6.1.1](https://github.com/thunder/thunder-distribution/tree/6.1.1) 2020-12-16
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.1.0...6.1.1)

We added the ability to edit all images in a gallery. With this feature you can for example change the copyright for all images at once.

Changes since 6.1.0

- Fix [Additional breadcrumb link on node/add pages](https://www.drupal.org/node/3180882)
- Add [Bulk edit of gallery images](https://www.drupal.org/node/3187607)

## [6.1.0](https://github.com/thunder/thunder-distribution/tree/6.1.0) 2020-12-08

Minor release, that corresponds to the Drupal 9.1.x minor releases.

Changes since 6.0.3:

- [Fix the interactive installer](https://www.drupal.org/node/3181696)
- [Fix admin toolbar logo](https://www.drupal.org/node/3176562)
- [Remove system.site.yml from shipped config](https://www.drupal.org/node/3176823)
