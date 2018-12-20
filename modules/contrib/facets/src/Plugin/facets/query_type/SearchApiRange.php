<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides support for range facets within the Search API scope.
 *
 * This is the default implementation that works with all backends.
 *
 * @FacetsQueryType(
 *   id = "search_api_range",
 *   label = @Translation("Range"),
 * )
 */
class SearchApiRange extends QueryTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;

    // Only alter the query when there's an actual query object to alter.
    if (!empty($query)) {
      $operator = $this->facet->getQueryOperator();
      $field_identifier = $this->facet->getFieldIdentifier();
      $exclude = $this->facet->getExclude();

      if ($query->getProcessingLevel() === QueryInterface::PROCESSING_FULL) {
        // Set the options for the actual query.
        $options = &$query->getOptions();
        $options['search_api_facets'][$field_identifier] = $this->getFacetOptions();
      }

      // Add the filter to the query if there are active values.
      $active_items = $this->facet->getActiveItems();

      if (count($active_items)) {
        $filter = $query->createConditionGroup($operator, ['facet:' . $field_identifier]);
        foreach ($active_items as $value) {
          $filter->addCondition($field_identifier, $value, $exclude ? 'NOT BETWEEN' : 'BETWEEN');
        }
        $query->addConditionGroup($filter);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();

    if (!empty($this->results)) {
      $facet_results = [];
      foreach ($this->results as $result) {
        if ($result['count'] || $query_operator == 'or') {
          $count = $result['count'];
          $result_filter = trim($result['filter'], '"');
          $result = new Result($this->facet, $result_filter, $result_filter, $count);
          $facet_results[] = $result;
        }
      }
      $this->facet->setResults($facet_results);
    }
    return $this->facet;
  }

}
