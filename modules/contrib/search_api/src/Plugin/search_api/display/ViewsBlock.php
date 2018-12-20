<?php

namespace Drupal\search_api\Plugin\search_api\display;

/**
 * Represents a Views block display.
 *
 * @SearchApiDisplay(
 *   id = "views_block",
 *   views_display_type = "block",
 *   deriver = "Drupal\search_api\Plugin\search_api\display\ViewsDisplayDeriver"
 * )
 */
class ViewsBlock extends ViewsDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    // There can be more than one block rendering the display. If any block is
    // rendered, we return TRUE.
    $plugin_id = 'views_block:' . $this->pluginDefinition['view_id'] . '-' . $this->pluginDefinition['view_display'];
    $blocks = $this->getEntityTypeManager()
      ->getStorage('block')
      ->loadByProperties(['plugin' => $plugin_id]);
    foreach ($blocks as $block) {
      if ($block->access('view')) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
