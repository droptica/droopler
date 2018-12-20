<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Result\Result;

/**
 * A processor that orders the results by active state.
 *
 * @FacetsProcessor(
 *   id = "active_widget_order",
 *   label = @Translation("Sort by active state"),
 *   description = @Translation("Sorts the widget results by active state."),
 *   default_enabled = TRUE,
 *   stages = {
 *     "sort" = 20
 *   }
 * )
 */
class ActiveWidgetOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {
    if ($a->isActive() == $b->isActive()) {
      return 0;
    }
    return ($a->isActive()) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['sort' => 'DESC'];
  }

}
