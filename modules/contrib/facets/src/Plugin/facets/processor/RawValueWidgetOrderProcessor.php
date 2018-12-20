<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by raw value.
 *
 * @FacetsProcessor(
 *   id = "raw_value_widget_order",
 *   label = @Translation("Sort by raw value"),
 *   description = @Translation("Sorts the widget results by raw value."),
 *   stages = {
 *     "sort" = 50
 *   }
 * )
 */
class RawValueWidgetOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {
    return strnatcasecmp($a->getRawValue(), $b->getRawValue());
  }

}
