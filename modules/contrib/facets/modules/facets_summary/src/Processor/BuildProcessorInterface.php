<?php

namespace Drupal\facets_summary\Processor;

use Drupal\facets_summary\FacetsSummaryInterface;

/**
 * Processor runs before the renderable array is created.
 */
interface BuildProcessorInterface extends ProcessorInterface {

  /**
   * Alter the items in the summary before creating the renderable array.
   *
   * @param \Drupal\facets_summary\FacetsSummaryInterface $facet
   *   The facet being changed.
   * @param array $build
   *   The render array.
   * @param \Drupal\facets\FacetInterface[] $facets
   *   The facets that are available.
   *
   * @return array
   *   The render array.
   */
  public function build(FacetsSummaryInterface $facet, array $build, array $facets);

}
