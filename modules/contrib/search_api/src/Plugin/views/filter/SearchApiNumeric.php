<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;

/**
 * Defines a filter for filtering on numeric values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_numeric")
 */
class SearchApiNumeric extends NumericFilter {

  use SearchApiFilterTrait;

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();
    unset($operators['regular_expression']);
    return $operators;
  }

}
