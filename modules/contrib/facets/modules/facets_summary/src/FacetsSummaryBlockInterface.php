<?php

namespace Drupal\facets_summary;

/**
 * Provides an interface for facet summary blocks.
 */
interface FacetsSummaryBlockInterface {

  /**
   * Returns the facets_summary entity associated with this derivative.
   *
   * @return \Drupal\facets_summary\FacetsSummaryInterface
   *   The facets_summary entity.
   */
  public function getEntity();

}
