<?php

/**
 * @file
 * Drush (< 9) integration.
 */

/**
 * Implements hook_drush_command().
 */
function simple_sitemap_drush_command() {
  $items['simple_sitemap-generate'] = [
    'description' => 'Regenerate the XML sitemap according to the module settings.',
    'callback' => 'drush_simple_sitemap_generate',
    'drupal dependencies' => ['simple_sitemap'],
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate the XML sitemap.
 */
function drush_simple_sitemap_generate() {
  \Drupal::service('simple_sitemap.generator')->generateSitemap('drush');
}
