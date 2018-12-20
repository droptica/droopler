<?php

namespace Drupal\facets_custom_widget\Plugin\facets\widget;

use Drupal\facets\Widget\WidgetPluginBase;

/**
 * Test widget.
 *
 * @FacetsWidget(
 *   id = "widget_date_qt",
 *   label = @Translation("Widget with date query type"),
 *   description = @Translation("Widget with date query type"),
 * )
 */
class WidgetDateQT extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'date';
  }

}
