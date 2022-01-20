<?php

namespace Drupal\Tests\thunder\Functional\Integration;

use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests integration with the metatag module.
 *
 * @group Thunder
 */
class MetatagTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['thunder_testing_demo'];

  /**
   * Tests default values as defined in metatag.metatag_defaults.node__article.
   */
  public function testTagDefaultValues() {
    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');

    /** @var \Drupal\metatag\MetatagManager $metatagManager */
    $metatagManager = $this->container->get('metatag.manager');

    $tags = $metatagManager->tagsFromEntityWithDefaults($node);
    $elements = $metatagManager->generateRawElements($tags, $node);

    $title = 'Burda Launches Open-Source CMS Thunder';
    $description = 'Munich, March 17th, 2016 – As of today, international media group Hubert Burda Media makes its Drupal 8 based Thunder Content Management System (CMS) available online as a free open-source platform for use and further development by other publishers. With this move, Burda joins forces with sector and industry partners including Acquia, Facebook, Microsoft, nexx.tv and Riddle.com, aiming to develop the best open-source CMS platform for publishers. Burda believes that in today’s world, successful media offerings result from the right combination of quality journalism and technology expertise. For the media company, this meant future-proofing its Content Management System by developing Thunder, an opensource system based on leading-edge technology, now available online free of charge for use and continuous development.';

    $this->assertStringEndsWith('/burda-launches-open-source-cms-thunder', $elements['canonical_url']['#attributes']['href']);
    $this->assertEquals('en', $elements['content_language']['#attributes']['content']);
    $this->assertEquals($description, $elements['description']['#attributes']['content']);
    $this->assertStringEndsWith('/files/2016-05/thunder.jpg', $elements['image_src']['#attributes']['href']);
    $this->assertEquals('no-referrer', $elements['referrer']['#attributes']['content']);
    $this->assertEquals('index, follow', $elements['robots']['#attributes']['content']);
    $this->assertEquals($title, $elements['title']['#attributes']['content']);
    $this->assertEquals($description, $elements['og_description']['#attributes']['content']);
    $this->assertStringContainsString('/files/styles/facebook/public/2016-05/thunder.jpg', $elements['og_image_0']['#attributes']['content']);
    $this->assertEquals('630', $elements['og_image_height']['#attributes']['content']);
    $this->assertEquals('1200', $elements['og_image_width']['#attributes']['content']);
    $this->assertEquals('image/jpeg', $elements['og_image_type']['#attributes']['content']);
    $this->assertEquals('Drush Site-Install', $elements['og_site_name']['#attributes']['content']);
    $this->assertEquals($title, $elements['og_title']['#attributes']['content']);
    $this->assertNotEmpty($elements['og_updated_time']['#attributes']['content']);
    $this->assertStringEndsWith('/burda-launches-open-source-cms-thunder', $elements['og_url']['#attributes']['content']);
    $this->assertEquals($description, $elements['twitter_cards_description']['#attributes']['content']);
    $this->assertStringContainsString('/files/styles/twitter/public/2016-05/thunder.jpg', $elements['twitter_cards_image']['#attributes']['content']);
    $this->assertEquals('512', $elements['twitter_cards_image_height']['#attributes']['content']);
    $this->assertEquals('1024', $elements['twitter_cards_image_width']['#attributes']['content']);
    $this->assertEquals('summary_large_image', $elements['twitter_cards_type']['#attributes']['content']);
  }

}
