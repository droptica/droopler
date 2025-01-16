<?php

/**
 * @file Theme and preprocess functions for links.
 */

declare(strict_types = 1);

use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_links().
 */
function droopler_theme_preprocess_links(&$variables): void {
  if (isset($variables['links']['node-readmore'])) {
    $variables['links']['node-readmore']['link']['#title'] = t('Read article...');
  }
}

/**
 * Implements hook_node_links_alter().
 */
function droopler_theme_node_links_alter(array &$links, NodeInterface $node, array &$context): void {
  foreach ($links as $key => $link) {
    if (strpos($key, 'comment__field') !== FALSE) {
      unset($links[$key]);
    }
  }
}

function droopler_theme_preprocess_file_link(array &$variables): void {
  $file = $variables['file'];
  if ($file->_referringItem->getParent()?->getName() === 'field_media_file') {
    unset($variables['file_size']);
    $media_name = $file->_referringItem?->getParent()?->getParent()?->getValue()?->getName() ?? NULL;
    $variables['link']['#title'] = $media_name ?? $variables['link']['#title'];
  }
}
