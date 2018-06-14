<?php

namespace Drupal\paragraphs;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

/**
 * Render controller for paragraphs.
 */
class ParagraphViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list) {
    $build_list = parent::buildMultiple($build_list);

    // Allow enabled behavior plugin to alter the rendering.
    foreach (Element::children($build_list) as $key) {
      $build = $build_list[$key];
      $display = EntityViewDisplay::load('paragraph.' . $build['#paragraph']->bundle() . '.' . $build['#view_mode']) ?: EntityViewDisplay::load('paragraph.' . $build['#paragraph']->bundle() . '.default');
      $paragraph_type = $build['#paragraph']->getParagraphType();

      // In case we use paragraphs type with no fields the EntityViewDisplay
      // might not be available yet.
      if (!$display) {
        $display = EntityViewDisplay::create([
          'targetEntityType' => 'paragraph',
          'bundle' => $build['#paragraph']->bundle(),
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }

      foreach ($paragraph_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_value) {
        $plugin_value->view($build_list[$key], $build['#paragraph'], $display, $build['#view_mode']);
      }
      $build_list[$key]['#attached']['library'][] = 'paragraphs/drupal.paragraphs.unpublished';
    }

    return $build_list;
  }

}
