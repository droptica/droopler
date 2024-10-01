<?php

/**
 * @file
 * Theme and preprocess functions for fields.
 */

declare(strict_types = 1);

use Drupal\Core\Template\Attribute;

/**
 * Implements template_preprocess_field().
 */
function droopler_theme_preprocess_field(&$variables) {
  $entity = $variables["element"]["#object"];
  if ($entity->getEntityTypeId() !== 'paragraph') {
    return;
  }

  // Alter field_d_main_title.
  if ($variables['element']['#field_name'] == 'field_d_main_title') {
    $variables['heading_tag'] = $entity->hasField('field_heading_type') && !$entity->get('field_heading_type')->isEmpty()
      ? $entity->get('field_heading_type')->getString()
      : NULL;
  }

  if ($variables['element']['#bundle'] === 'd_p_tiles') {
    $featured_images = $entity->hasField('field_featured_images') && !$entity->get('field_featured_images')->isEmpty()
      ? $entity->get('field_featured_images')->getString()
      : NULL;
    $featured_images = empty($featured_images) ? [] : explode(',', (string) $featured_images);
    $featured_images = array_map('intval', $featured_images);

    foreach ($featured_images as $image_number) {
      if ($image_number > 0 && !empty($variables['items'][$image_number - 1])) {
        $variables['items'][$image_number - 1]['content']['#view_mode'] = 'tiles_gallery_fullscreen_featured';
      }
    }
  }

  if ($variables['field_type'] === 'link' && $variables['element']['#formatter'] === 'ala') {
    foreach ($variables['items'] as $delta => $item) {
      $custom_classes = $variables['items'][$delta]['content']['#options']['attributes']['custom_classes'] ?? NULL;
      if (!$custom_classes) {
        continue;
      }

      $variables['items'][$delta]['content']['#options']['attributes']['class'][] = $custom_classes;
      $class = $variables['items'][$delta]['content']['#options']['class'] ?? '';
      $variables['items'][$delta]['content']['#options']['class'] = $class ? $class . ' ' . $custom_classes : $custom_classes;
    }

    $variables['#attached']['library'][] = 'droopler_theme/cta';
  }

}
