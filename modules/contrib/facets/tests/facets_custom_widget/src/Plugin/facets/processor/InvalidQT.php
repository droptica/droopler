<?php

namespace Drupal\facets_custom_widget\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * The URL processor handler triggers the actual url processor.
 *
 * @FacetsProcessor(
 *   id = "invalid_qt",
 *   label = @Translation("Invalid Query type"),
 *   description = @Translation("TEST invalid query type"),
 *   stages = {
 *     "pre_query" = 50
 *   }
 * )
 */
class InvalidQT extends ProcessorPluginBase implements PreQueryProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return '51_pegasi_b';
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    // This can be empty for this test implementation.
  }

}
