<?php

namespace Drupal\facets\Processor;

use Drupal\facets\Result\Result;

/**
 * Processor runs before the renderable array is created.
 */
interface SortProcessorInterface {

  /**
   * Orders results and return the new order of results.
   *
   * @param \Drupal\facets\Result\Result $a
   *   First result which should be compared.
   * @param \Drupal\facets\Result\Result $b
   *   Second result which should be compared.
   *
   * @return int
   *   -1, 0, or 1 depending which result
   */
  public function sortResults(Result $a, Result $b);

}
