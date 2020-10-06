# Changelog

## [6.0.2](https://github.com/thunder/thunder-distribution/tree/6.0.2) 2020-09-03
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.0.1...6.0.2)

Modal dialogs do not work with Drupal 9.0.4. Until a new release of Drupal has been done, we need to prevent installation of Drupal 9.0.4.

- Do [Make Thunder PHP 7.4 compatible](https://www.drupal.org/node/3168860)
- Fix [Prevent installing of Drupal 8.9.4/9.0.4](https://www.drupal.org/node/3168846)

## [6.0.1](https://github.com/thunder/thunder-distribution/tree/6.0.1) 2020-08-27
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.0.0...6.0.1)

After a lot of work, we have finally integrated the search api to have a better editorial search.
To start using the new article search, the optional Thunder Search API module has to be enabled, it is not enabled by default. The content list will get a fulltext search index instead of the previous search by title.
We recommend to use Solr-Search for improved performance.

- Do [Integrate editorial search with Search API module](https://www.drupal.org/node/2899254)

## [6.0.0](https://github.com/thunder/thunder-distribution/tree/6.0.0) 2020-07-27

Add Drupal 9 compatibility. To achieve this, we had to remove the AMP anf Facebook instant articles modules. If you need
these modules, you have to stay on Thunder 3.5 (Drupal 8.9) for now. Thunder 3.5 will be supported as long as Drupal 8.9
is supported. Drupal 8.9 Support will end in November 2021.

For update instructions from Thunder 3 to Thunder 6 see the [Thunder 6 update documentation](https://thunder.github.io/thunder-documentation/update-3-to-6)

What happened to Thunder 4 and 5? Drupal.org introduced semantic versioning, and what we considered to be Thunder 3.4 and Thunder 3.5
is Thunder 4 and Thunder 5 in drupal.org terms. So we had to do the big version jump to Thunder 6.
