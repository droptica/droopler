<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

declare(strict_types = 1);

use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_taxonomy_term().
 */
function droopler_theme_preprocess_taxonomy_term(array &$variables): void {
  $taxonomy_bundle = $variables['term']->bundle();
  if (!in_array($taxonomy_bundle, ['d_product_categories', 'tags'])) {
    return;
  }

  $node = \Drupal::routeMatch()->getParameter('node');
  if (!$node instanceof NodeInterface || $node->getType() !== 'd_product') {
    return;
  }

  $filter_name = match ($taxonomy_bundle) {
    'd_product_categories' => 'product_categories_taxonomy_term_name_block',
    'tags' => 'tags_taxonomy_term_name_block',
    default => null,
  };

  if (!$filter_name) {
    return;
  }

  $product_list_page = '/products';
  $variables['url'] = Url::fromUserInput(
    "$product_list_page?f%5B0%5D=$filter_name%3A{$variables['term']->getName()}"
  )->toString();
}

/**
 * Implements hook_preprocess_node().
 */
function droopler_theme_preprocess_node(&$variables) {
  $node = $variables['node'];
  if ($node->getType() == 'd_product') {
    if (isset($variables['view_mode']) && $variables['view_mode'] == 'teaser') {
      if (isset($variables['node'])) {

        if (isset($variables['content']['field_d_product_media'][0])) {
          $variables['main_image'] = $variables['content']['field_d_product_media'][0];
          unset($variables['content']['field_d_product_media']);
        }

        if (isset($variables['content']['field_product_categories'][0])) {
          $variables['category'] = $variables['content']['field_product_categories'];
          unset($variables['content']['field_product_categories']);
        }

        if (isset($variables['content']['field_d_product_link'])) {
          $variables['link'] = $variables['content']['field_d_product_link'];
          unset($variables['content']['field_d_product_link']);
        }
      }
    }
  }
}

