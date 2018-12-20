<?php

namespace Drupal\search_api\Plugin\views\filter;

/**
 * Defines a filter for filtering on fulltext fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_text")
 */
class SearchApiText extends SearchApiString {

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();

    $operators['=']['title'] = $this->t('contains');
    $operators['!=']['title'] = $this->t("doesn't contain");

    $supported_operators = [
      '=' => 1,
      '!=' => 1,
      'empty' => 1,
      'not empty' => 1,
    ];
    $operators = array_intersect_key($operators, $supported_operators);

    return $operators;
  }

}
