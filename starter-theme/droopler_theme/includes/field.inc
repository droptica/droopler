<?php

/**
 * @file
 * Theme and preprocess functions for fields.
 */

declare(strict_types = 1);

use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Template\Attribute;

/**
 * Implements template_preprocess_field().
 */
function droopler_theme_preprocess_field(&$variables) {
  $field_type = $variables['element']['#field_type'] ?? NULL;
  $field_name = $variables['element']['#field_name'] ?? NULL;
  $view_mode = $variables['element']['#view_mode'] ?? NULL;
  $formatter = $variables['element']['#formatter'] ?? NULL;
  $entity = $variables["element"]["#object"];

  if ($field_type === 'entity_reference' && $view_mode === 'd_small_box' && $formatter === 'media_thumbnail') {
      $items = Element::getVisibleChildren($variables['element']);
      foreach ($items as $key => $delta) {
        if ($key == 0) {
          continue;
        }
        unset($variables['items'][$delta]);
      }
  }

  if ($field_name === 'field_media_oembed_video' && isset($variables['items']) && $view_mode !== 'media_library') {
    foreach ($variables['items'] as $delta => $item) {
      $variables['items'][$delta]['content']['#view_mode'] = $view_mode;

      $iframe_src = $variables['items'][$delta]['content']['#attributes']['src'] ?? NULL;
      if ($iframe_src) {
        if ($view_mode !== 'side_image') {
          $width = $variables['items'][0]['content']['#attributes']['width'] ?? NULL;
          $height = $variables['items'][0]['content']['#attributes']['height'] ?? NULL;

          if ($width && $height) {
            $aspect_ratio = _calculate_aspect_ratio($height, $width);
            $variables['items'][$delta]['content']['#attributes']['data-aspect-ratio'] = $aspect_ratio;
            
            $variables['items'][$delta]['content']['#attributes']['style'] = "aspect-ratio: 1/$aspect_ratio;";
          }
        }
        
        $variables['items'][$delta]['content']['#attributes']['src'] = _get_video_src($entity, $view_mode);
        $variables['items'][$delta]['content']['#attributes']['class'] = _get_video_classes($view_mode);
        $variables['items'][$delta]['content']['#attributes']['class'][] = 'img-fluid';
      }
    }
  }

  if ($entity->getEntityTypeId() === 'paragraph') {
    // Alter field_d_main_title.
    if ($variables['element']['#field_name'] == 'field_d_main_title') {
      $variables['heading_tag'] = $entity->hasField('field_heading_type') && !$entity->get('field_heading_type')->isEmpty()
        ? $entity->get('field_heading_type')->getString()
        : NULL;
    }

    if ($variables['element']['#bundle'] === 'd_p_tiles' && $variables['element']['#field_name'] !== 'field_d_media_icon') {
      $featured_images = $entity->hasField('field_featured_images') && !$entity->get('field_featured_images')->isEmpty()
        ? $entity->get('field_featured_images')->getString()
        : NULL;
      $featured_images = empty($featured_images) ? [] : explode(',', (string)$featured_images);
      $featured_images = array_map('intval', $featured_images);
      foreach ($featured_images as $image_number) {
        if ($image_number > 0 && !empty($variables['items'][$image_number - 1]['content']['#content'])) {
          $variables['items'][$image_number - 1]['content']['#content']['#view_mode'] = 'tiles_gallery_fullscreen_featured';
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


  if ($field_name === 'field_d_product_media') {
    $single_items = [];
    $navigation_items = [];

    $items = Element::getVisibleChildren($variables['element']);
    foreach ($items as $key => $delta) {
      $item = $variables['element'][$delta];

      $item_single = $item;
      $item_single['#view_mode'] = 'd_product_gallery';
      $item_single['#cache']['keys'] = ['field_d_product_media_single', $delta];
      $single_items[] = $item_single;

      $item_navigation = $item;
      $item_navigation['#view_mode'] = 'd_product_gallery_navigation_item';
      $item_navigation['#cache']['keys'] = ['field_d_product_media_navigation', $delta];
      $navigation_items[] = $item_navigation;
    }

    $variables['single_items_list'] = $single_items;
    $variables['navigation_items_list'] = $navigation_items;
  }

  if ($field_name === 'field_media_oembed_video') {
    foreach ($variables['items'] as $delta => $item) {
      if (!empty($variables['items'][$delta]['content']['#attributes'])) {
        switch ($view_mode) {
          case 'gallery_item_fullscreen':
          case 'tiles_fullscreen':
              $variables['items'][$delta]['content']['#attributes']['width'] = '1280px';
              $variables['items'][$delta]['content']['#attributes']['height'] = '720px';
              break;
          case 'default':
          case 'd_product_gallery':
            $variables['items'][$delta]['content']['#attributes']['width'] = '618px';
            $variables['items'][$delta]['content']['#attributes']['height'] = '350px';
            break;
          case 'd_product_image':
            $variables['items'][$delta]['content']['#attributes']['width'] = '275px';
            $variables['items'][$delta]['content']['#attributes']['height'] = '135px';
            break;
        }
      }
    }
  }

  if ($field_name === 'field_uitgeversdeals' && $entity instanceof NodeInterface && $entity->getType() === 'uitgever') {
    $items = $variables['items'];

    $term_weights = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('status_licentie');
    foreach ($terms as $term) {
      $term_weights[$term->tid] = $term->weight;
    }

    usort($items, function ($a, $b) use ($term_weights) {
      $node_a = $a['content']['#node'] ?? NULL;
      $node_b = $b['content']['#node'] ?? NULL;

      if (!$node_a || !$node_b) {
        return [];
      }

      $term_a_id = $node_a->get('field_status_licentie')->target_id ?? null;
      $term_b_id = $node_b->get('field_status_licentie')->target_id ?? null;

      $weight_a = $term_weights[$term_a_id] ?? PHP_INT_MAX;
      $weight_b = $term_weights[$term_b_id] ?? PHP_INT_MAX;

      return $weight_a <=> $weight_b;
    });

    $variables['items'] = $items;
  }

  if ($field_name === 'field_status_licentie') {
    $items = &$variables['items'];

    foreach ($items as $delta => $item) {
      $term = $item['content']['#entity'] ?? NULL;

      if (!$item['attributes'] instanceof Attribute || !$term instanceof TermInterface) {
        continue;
      }

      $item['attributes']->addClass('status-licentie-' . $term->id());
    }
  }
}
