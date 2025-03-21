<?php

/**
 * @file Theme and preprocess functions for responsive image.
 */

declare(strict_types = 1);

use Drupal\Core\Site\Settings;

/**
 * Implements hook_preprocess_responsive_image().
 */
function droopler_theme_preprocess_responsive_image(&$variables): void {
  // Generate a list of image links for httrack.
  if (empty($variables['sources']) || !Settings::get('httrack_enabled', FALSE)) {
    return;
  }

  $id = substr(md5($variables['uri']), 0, 6);
  foreach ($variables['sources'] as $k => $attribute) {
    /** @var \Drupal\Core\Template\Attribute $attribute */
    $source = preg_replace('/\s\d[xX]$/', '', $attribute->offsetGet('srcset'));
    $variables['#attached']['html_head_link'][] = [
      [
        'href' => $source,
        'rel' => "droopler:$id:img$k",
      ],
    ];
  }
}