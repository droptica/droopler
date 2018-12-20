<?php

namespace Drupal\Tests\search_api\Kernel;

use Drupal\search_api\Plugin\views\field\SearchApiFieldTrait;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a field plugin for testing.
 *
 * Provides some additional public methods to simplify testing.
 *
 * @see \Drupal\Tests\search_api\Kernel\Views\ViewsFieldTraitTest
 */
class ViewsTestField extends FieldPluginBase {

  use SearchApiFieldTrait {
    addRetrievedProperty as public;
  }

  /**
   * Determines whether this field is active for the given row.
   *
   * To be able to test retrieval of properties from different types of (base)
   * entities, this implementation always return TRUE.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return bool
   *   TRUE if this field handler might produce output for the given row, FALSE
   *   otherwise.
   *
   * @see \Drupal\search_api\Plugin\views\field\SearchApiFieldTrait::isActiveForRow()
   */
  protected function isActiveForRow(ResultRow $row) {
    return TRUE;
  }

  /**
   * Sets the query object.
   *
   * @param \Drupal\search_api\Plugin\views\query\SearchApiQuery $query
   *   The query object.
   *
   * @return $this
   */
  public function setQuery(SearchApiQuery $query) {
    $this->query = $query;
    return $this;
  }

}
