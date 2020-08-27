# Changelog

## [3.5.3](https://github.com/thunder/thunder-distribution/tree/3.5.3) 2020-08-27
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.2...3.5.3)

After a lot of work, we have finally integrated the search api to have a better editorial search.
To start using the new article search, the optional Thunder Search API module has to be enabled, it is not enabled by default. The content list will get a fulltext search index instead of the previous search by title. We recommend to use Solr-Search for improved performance.

Starting with this release, we will not provide a tar-ball on drupal.org anymore. Only composer installs are supported.


- Do [Integrate editorial search with Search API module](https://www.drupal.org/node/2899254)

## [3.5.2](https://github.com/thunder/thunder-distribution/tree/3.5.2) 2020-07-23
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.1...3.5.2)

Adds a new admin install page for optional modules, which was previously shown on install only. The page can be accessed at admin/modules/extend-thunder.
Updating modules and improving the composer file.

- Fix [Remove simple_gmap from composer.json and let composer figure out the right version](https://www.drupal.org/node/3133327)
- Do [Streamline the handling of optional modules](https://www.drupal.org/node/3160788)
- Do [Update Simple Sitemap to 8.x-3.7](https://www.drupal.org/node/3157156)

## [3.5.1](https://github.com/thunder/thunder-distribution/tree/3.5.1) 2020-06-18
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.0...3.5.1)

Update tarball to include latest security update and updating entity browser and update helper.

- Do [Update entity browser to 2.5](https://www.drupal.org/node/3146606)

## [3.5.0](https://github.com/thunder/thunder-distribution/tree/3.5.0) 2020-06-04

Add Drupal 8.9 compatibility

