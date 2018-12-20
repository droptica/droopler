<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by amount.
 *
 * @FacetsProcessor(
 *   id = "count_widget_order",
 *   label = @Translation("Sort by count"),
 *   description = @Translation("Sorts the widget results by count."),
 *   default_enabled = TRUE,
 *   stages = {
 *     "sort" = 30
 *   }
 * )
 */
class CountWidgetOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {
    if ($a->getCount() == $b->getCount()) {
      return 0;
    }
    return ($a->getCount() < $b->getCount()) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['sort' => 'DESC'];
  }

}
