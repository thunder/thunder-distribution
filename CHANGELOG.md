# Changelog

## [6.2.3](https://github.com/thunder/thunder-distribution/tree/6.2.3) 2021-11-22

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.2.2...6.2.3)

Fixes for GraphQL implementation.

- [Fix GraphQL responses contain https links](https://www.drupal.org/node/3250583)

## [6.2.2](https://github.com/thunder/thunder-distribution/tree/6.2.2) 2021-10-13

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.2.1...6.2.2)

We are fixing a few bugs with this release. Most notably we are fixing an update hook of the 6.2.1 release, which might
have created empty configs. The configs, that were created are "field.field.node.liveblog.field_paragraphs" and
"field.storage.liveblog_post.field_embed_media". You can check your installation by exporting your config. When you get
the mentioned configuration files as empty files (only containing an empty set of brackets), you can safely delete those
files.

- Fix update hook thunder_update_8316
- [Fix 'Scheduled content' is shown twice on admin/content](https://www.drupal.org/node/3224286)
- [Allow install admin_toolbar v3, require security update](https://www.drupal.org/node/3229926)

## [6.2.1](https://github.com/thunder/thunder-distribution/tree/6.2.1) 2021-07-08

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/6.2.0...6.2.1)

This release is a bit larger than usually. While it contains some smaller bugfixes and module updates, the release is
also adding one of the biggest features we had in a while. Thunder now has an official API for all your headless and
decoupled approaches. We decided to not simply enable the JSON:API module and be done with it, but went instead with a
custom GraphQL schema, based on the GraphQL version 4 module. You can read more about this on our
new [API documentation](https://thunder.github.io/developer-guide/headless.html).

Additionally, we added a new type of Sitemap to Thunder, which creates index sitemaps to index all the existing use-case
specific sitemaps.

- [Fix broken tour](https://www.drupal.org/node/3219546)

- [Update password policy module](https://www.drupal.org/node/3222188)
- [Update entity browser](https://www.drupal.org/node/3222146)

- [Provide multiple sitemaps for specific content types](https://www.drupal.org/node/3222332)
- [Add GraphQL schema](https://www.drupal.org/node/3220096)

- [Deprecate liveblog integration](https://www.drupal.org/node/3220009)

## [6.2.0](https://github.com/thunder/thunder-distribution/tree/6.2.0) 2021-06-17

Minor release, that bumps the Drupal core dependency to 9.2.x.
