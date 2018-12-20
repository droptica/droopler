<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides support for string facets within the Search API scope.
 *
 * This is the default implementation that works with all backends and data
 * types. While you could use this query type for every data type, other query
 * types will usually be better suited for their specific data type.
 *
 * For example, the SearchApiDate query type will handle its input as a DateTime
 * value, while this class would only be able to work with it as a string.
 *
 * @FacetsQueryType(
 *   id = "search_api_string",
 *   label = @Translation("String"),
 * )
 */
class SearchApiString extends QueryTypePluginBase {

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
          $filter->addCondition($this->facet->getFieldIdentifier(), $value, $exclude ? '<>' : '=');
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
          $result_filter = $result['filter'];
          if ($result_filter[0] === '"') {
            $result_filter = substr($result_filter, 1);
          }
          if ($result_filter[strlen($result_filter) - 1] === '"') {
            $result_filter = substr($result_filter, 0, -1);
          }
          $count = $result['count'];
          $result = new Result($this->facet, $result_filter, $result_filter, $count);
          $facet_results[] = $result;
        }
      }
      $this->facet->setResults($facet_results);
    }

    return $this->facet;
  }

}
