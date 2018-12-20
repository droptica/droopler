<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that removes all results when the set has only 1 item.
 *
 * @FacetsProcessor(
 *   id = "hide_1_result_facet",
 *   label = @Translation("Hide facet with 1 result"),
 *   description = @Translation("When the facet has only one result, it will be hidden"),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class HideOnlyOneItemProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    if (count($results) !== 1) {
      return $results;
    }

    /** @var \Drupal\facets\Result\Result $result */
    $result = reset($results);

    return $result->isActive() ? $results : [];
  }

}
