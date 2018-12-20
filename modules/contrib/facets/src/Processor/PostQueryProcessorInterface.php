<?php

namespace Drupal\facets\Processor;

use Drupal\facets\FacetInterface;

/**
 * Processor runs after the query was executed.
 */
interface PostQueryProcessorInterface extends ProcessorInterface {

  /**
   * Runs after the query was executed.
   *
   * Uses the query results and can alter those results, for example a
   * ValueCallbackProcessor. If results are being changed, this processor should
   * handle saving itself.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet that's being changed.
   */
  public function postQuery(FacetInterface $facet);

}
