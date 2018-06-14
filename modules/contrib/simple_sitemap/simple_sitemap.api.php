<?php

/**
 * @file
 * Hooks provided by the Simple XML sitemap module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the generated link data before the sitemap is saved.
 * This hook gets invoked for every sitemap chunk generated.
 *
 * @param array &$links
 *   Array containing multilingual links generated for each path to be indexed.
 */
function hook_simple_sitemap_links_alter(array &$links) {

  // Remove German URL for a certain path in the hreflang sitemap.
  foreach ($links as $key => $link) {
    if ($link['path'] === 'node/1') {

      // Remove 'loc' URL if it points to a german site.
      if ($link['langcode'] === 'de') {
        unset($links[$key]);
      }

      // If this 'loc' URL points to a non-german site, make sure to remove
      // its german alternate URL.
      else {
        if ($link['alternate_urls']['de']) {
          unset($links[$key]['alternate_urls']['de']);
        }
      }
    }
  }
}

/**
 * Add arbitrary links to the sitemap.
 *
 * @param array &$arbitrary_links
 */
function hook_simple_sitemap_arbitrary_links_alter(array &$arbitrary_links) {

  // Add an arbitrary link.
  $arbitrary_links[] = [
    'url' => 'http://this-is-your-life.net/tyler',
    'priority' => '0.5',

    // An ISO8601 formatted date.
    'lastmod' => '2012-10-12T17:40:30+02:00',

    'changefreq' => 'weekly',
    'images' => [
      ['path' => 'http://path-to-image.png']
    ],

    // Add alternate URLs for every language of a multilingual site.
    // Not necessary for monolingual sites.
    'alternate_urls' => [
      'en' => 'http://this-is-your-life.net/de/tyler',
      'de' => 'http://this-is-your-life.net/en/tyler',
    ]
  ];
}

/**
 * Alters the sitemap attributes shortly before XML document generation.
 * Attributes can be added, changed and removed.
 *
 * @param array &$attributes
 */
function hook_simple_sitemap_attributes_alter(array &$attributes) {

  // Remove the xhtml attribute e.g. if no xhtml sitemap elements are present.
  unset($attributes['xmlns:xhtml']);
}

/**
 * Alters attributes of the sitemap index shortly before XML document generation.
 * Attributes can be added, changed and removed.
 *
 * @param array &$index_attributes
 */
function hook_simple_sitemap_index_attributes_alter(array &$index_attributes) {

  // Add some attribute to the sitemap index.
  $index_attributes['name'] = 'value';
}

/**
 * Alter properties of and remove generator plugins.
 *
 * @param array $generators
 */
function hook_simple_sitemap_url_generators_alter(array &$generators) {

  // Remove the entity generator.
  // Useful when creating your own entity generator plugin.
  unset($generators['entity']);

  // Change the weight of the arbitrary link generator.
  $generators['arbitrary']['weight'] = -100;
}

/**
 * @} End of "addtogroup hooks".
 */
