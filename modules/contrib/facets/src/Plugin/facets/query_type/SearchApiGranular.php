<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypeRangeBase;
use Drupal\facets\Result\Result;

/**
 * Basic support for numeric facets grouping by a granularity value.
 *
 * Requires the facet widget to set configuration value keyed with
 * granularity.
 *
 * @FacetsQueryType(
 *   id = "search_api_granular",
 *   label = @Translation("Numeric query with set granularity"),
 * )
 */
class SearchApiGranular extends QueryTypeRangeBase {

  /**
   * {@inheritdoc}
   */
  public function calculateRange($value) {
    return [
      'start' => $value,
      'stop' => (int) $value + $this->getGranularity(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // If there were no results or no query object, we can't do anything.
    if (empty($this->results)) {
      return $this->facet;
    }

    $supportedFeatures = array_flip($this->query
      ->getIndex()
      ->getServerInstance()
      ->getBackend()
      ->getSupportedFeatures());

    // Range grouping is supported.
    if (isset($supportedFeatures['search_api_granular'])) {
      $query_operator = $this->facet->getQueryOperator();
      $facet_results = [];
      foreach ($this->results as $result) {
        if ($result['count'] || $query_operator == 'or') {
          $result_filter = trim($result['filter'], '"');
          $facet_results[] = new Result($this->facet, $result_filter, $result_filter, $result['count']);
        }
      }
      $this->facet->setResults($facet_results);

      return $this->facet;
    }

    return parent::build();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateResultFilter($value) {
    assert($this->getGranularity() > 0);

    $min_value = (int) $this->getMinValue();
    $max_value = $this->getMaxValue();
    $granularity = $this->getGranularity();

    if ($value < $min_value || (!empty($max_value) && $value > ($max_value + $granularity - 1))) {
      return FALSE;
    }

    return [
      'display' => $value - fmod($value - $min_value, $this->getGranularity()),
      'raw' => $value - fmod($value - $min_value, $this->getGranularity()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFacetOptions() {
    return $this->facet->getProcessors()['granularity_item']->getConfiguration()
      + parent::getFacetOptions();
  }

  /**
   * Looks at the configuration for this facet to determine the granularity.
   *
   * Default behaviour an integer for the steps that the facet works in.
   *
   * @return int
   *   If not an integer the inheriting class needs to deal with calculations.
   */
  protected function getGranularity() {
    return $this->facet->getProcessors()['granularity_item']->getConfiguration()['granularity'];
  }

  /**
   * Looks at the configuration for this facet to determine the min value.
   *
   * Default behaviour an integer for the minimum value of the facets.
   *
   * @return mixed
   *   It can be a number or an empty value.
   */
  protected function getMinValue() {
    return $this->facet->getProcessors()['granularity_item']->getConfiguration()['min_value'];
  }

  /**
   * Looks at the configuration for this facet to determine the max value.
   *
   * Default behaviour an integer for the maximum value of the facets.
   *
   * @return mixed
   *   It can be a number or an empty value.
   */
  protected function getMaxValue() {
    return $this->facet->getProcessors()['granularity_item']->getConfiguration()['max_value'];
  }

}
