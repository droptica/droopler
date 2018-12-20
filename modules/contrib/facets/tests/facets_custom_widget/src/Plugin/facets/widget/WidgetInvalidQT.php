<?php

namespace Drupal\facets_custom_widget\Plugin\facets\widget;

use Drupal\facets\Widget\WidgetPluginBase;

/**
 * Test widget.
 *
 * @FacetsWidget(
 *   id = "widget_invalid_qt",
 *   label = @Translation("Widget with invalid query type"),
 *   description = @Translation("Widget with invalid query type"),
 * )
 */
class WidgetInvalidQT extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'kepler';
  }

}
