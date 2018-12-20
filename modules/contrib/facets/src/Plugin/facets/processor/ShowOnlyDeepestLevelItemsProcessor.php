<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that only shows deepest level items.
 *
 * @FacetsProcessor(
 *   id = "show_only_deepest_level_items_processor",
 *   label = @Translation("Show only deepest item levels"),
 *   description = @Translation("Only show items that have no children."),
 *   stages = {
 *     "build" = 40
 *   }
 * )
 */
class ShowOnlyDeepestLevelItemsProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $id => $result) {
      if (!empty($result->getChildren())) {
        unset($results[$id]);
      }
    }
    return $results;
  }

}
