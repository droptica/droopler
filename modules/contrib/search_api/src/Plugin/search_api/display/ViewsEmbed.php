<?php

namespace Drupal\search_api\Plugin\search_api\display;

/**
 * Represents a Views embed display.
 *
 * @SearchApiDisplay(
 *   id = "views_embed",
 *   views_display_type = "embed",
 *   deriver = "Drupal\search_api\Plugin\search_api\display\ViewsDisplayDeriver"
 * )
 */
class ViewsEmbed extends ViewsDisplayBase {}
