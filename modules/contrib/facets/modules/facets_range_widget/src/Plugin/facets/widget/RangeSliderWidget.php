<?php

namespace Drupal\facets_range_widget\Plugin\facets\widget;

use Drupal\facets\FacetInterface;

/**
 * The range slider widget.
 *
 * @FacetsWidget(
 *   id = "range_slider",
 *   label = @Translation("Range slider"),
 *   description = @Translation("A widget that shows a range slider."),
 * )
 */
class RangeSliderWidget extends SliderWidget {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);

    $active = $facet->getActiveItems();
    $facet_settings = &$build['#attached']['drupalSettings']['facets']['sliders'][$facet->id()];

    $facet_settings['range'] = TRUE;
    $facet_settings['url'] = reset($facet_settings['urls']);

    unset($facet_settings['value']);
    unset($facet_settings['urls']);

    $min = $facet_settings['min'];
    $max = $facet_settings['max'];
    $facet_settings['values'] = [isset($active[0][0]) ? (float) $active[0][0] : $min, isset($active[0][1]) ? (float) $active[0][1] : $max];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function isPropertyRequired($name, $type) {
    if ($name === 'range_slider' && $type === 'processors') {
      return TRUE;
    }
    if ($name === 'show_only_one_result' && $type === 'settings') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'range';
  }

}
