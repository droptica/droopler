<?php

namespace Drupal\search_api\Plugin\views\filter;

/**
 * Provides filtering on the datasource.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_datasource")
 */
class SearchApiDatasource extends SearchApiOptions {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [];

    $index = $this->getIndex();
    if ($index) {
      foreach ($index->getDatasources() as $datasource_id => $datasource) {
        $this->valueOptions[$datasource_id] = $datasource->label();
      }
    }

    return $this->valueOptions;
  }

}
