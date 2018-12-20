<?php

namespace Drupal\search_api_test_no_ui\Plugin\search_api\backend;

use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides a test backend that should be hidden from the UI.
 *
 * @SearchApiBackend(
 *   id = "search_api_test_no_ui",
 *   label = @Translation("No UI backend"),
 *   no_ui = true,
 * )
 */
class NoUi extends BackendPluginBase {

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
  }

}
