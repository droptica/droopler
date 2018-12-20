<?php

/**
 * @file
 * Contains hook implementations for testing Database Search.
 */

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\search_api\Query\QueryInterface;

/**
 * Implements hook_search_api_db_query_alter().
 */
function search_api_test_db_search_api_db_query_alter(SelectInterface &$db_query, QueryInterface $query) {
  if ($query->getOption('search_api_test_db_search_api_db_query_alter')) {
    $db_query->where('0 = 1');
  }
}
