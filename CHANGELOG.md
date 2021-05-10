# Changelog

## [3.5.9](https://github.com/thunder/thunder-distribution/tree/3.5.8) 2021-05-10
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.7...3.5.8)

Having the metatag form on the node edit page is a huge performance hit on save, autosave and adding new paragraphs. To
improve those operations, we integrate the metatag_async_widget, which loads the metatag form on demand.

We cannot ship update hooks with Thunder 3.5 anymore. In order to get the new metatag widget, you will have to enable 
the metatag_async_widget module and change the widget of the metatag module field manually.

- [Use asynchronous widget for metatag handling](https://www.drupal.org/node/3208355)

## [3.5.8](https://github.com/thunder/thunder-distribution/tree/3.5.8) 2021-04-15
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.7...3.5.8)

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

- [Disable xsl on sitemap and bump simple_sitemap version](https://www.drupal.org/node/3208377)

## [3.5.7](https://github.com/thunder/thunder-distribution/tree/3.5.7) 2020-12-16
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.6...3.5.7)

We added the ability to edit all images in a gallery. With this feature you can for example change the copyright for all images at once.

Changes since 3.5.6

- Add [Bulk edit of gallery images](https://www.drupal.org/node/3187607)

## [3.5.6](https://github.com/thunder/thunder-distribution/tree/3.5.6) 2020-11-19
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.5...3.5.6)

Adding composer 2 compatibility.

Please make sure, that your project is requiring oomphinc/composer-installers-extender by calling

    composer require oomphinc/composer-installers-extender:^2.0


## [3.5.5](https://github.com/thunder/thunder-distribution/tree/3.5.5) 2020-10-12
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.4...3.5.5)

Instagram will shut down its current oEmbed API on October 24, 2020. We integrate the new version of Media Entity Instagram to support the future integration.
After that date, you will need a Facebook Developer Account and App credentials to be able to show Instagram embeds.
More information on what is necessary to be able to integrate Instagram embeds in the future can be found here:
https://developers.facebook.com/docs/instagram/oembed

- Fix [Add config dependencies to thunder_media AutoAspectEffect](https://www.drupal.org/node/3164391)
- Do [Update to media_entity_instagram v3](https://www.drupal.org/node/3171500)

## [3.5.4](https://github.com/thunder/thunder-distribution/tree/3.5.4) 2020-09-03
[Full Changelog](https://github.com/thunder/thunder-distribution/compare/3.5.3...3.5.4)

Modal dialogs do not work with Drupal 8.9.4. Until a new release of Drupal has been done, we need to prevent installation of Drupal 8.9.4.

- Do [Make Thunder PHP 7.4 compatible](https://www.drupal.org/node/3168860)
- Fix [Prevent installing of Drupal 8.9.4/9.0.4](https://www.drupal.org/node/3168846)

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

