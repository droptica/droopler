<?php

namespace Drupal\facets_query_processor\Plugin\facets\url_processor;

use Drupal\facets\Plugin\facets\url_processor\QueryString;

/**
 * Query string URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "dummy_query",
 *   label = @Translation("Dummy query"),
 *   description = @Translation("Dummy for testing.")
 * )
 */
class DummyQuery extends QueryString {

  /**
   * A string that separates the filters in the query string.
   *
   * @var string
   */
  protected $separator = '||';

}
