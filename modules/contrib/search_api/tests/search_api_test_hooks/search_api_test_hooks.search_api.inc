<?php

/**
 * @file
 * Tests all the hooks defined by the Search API module.
 */

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\ServerInterface;

/**
 * Implements hook_search_api_backend_info_alter().
 */
function search_api_test_hooks_search_api_backend_info_alter(array &$backend_info) {
  $backend_info['search_api_test']['label'] = 'Slims return';
}

/**
 * Implements hook_search_api_datasource_info_alter().
 */
function search_api_test_hooks_search_api_datasource_info_alter(array &$infos) {
  if (isset($infos['entity:node'])) {
    $infos['entity:node']['label'] = 'Distant land';
  }
}

/**
 * Implements hook_search_api_parse_mode_info_alter().
 */
function search_api_test_hooks_search_api_parse_mode_info_alter(array &$parse_modes) {
  $parse_modes['direct']['label'] = 'Song for My Father';
}

/**
 * Implements hook_search_api_server_features_alter().
 */
function search_api_test_hooks_search_api_server_features_alter(array &$features, ServerInterface $server) {
  if ($server->id() == 'webtest_server') {
    $features[] = 'welcome_to_the_jungle';
  }
}

/**
 * Implements hook_search_api_processor_info_alter().
 */
function search_api_test_hooks_search_api_processor_info_alter(array &$processors) {
  $processors['content_access']['label'] = 'Mystic bounce';
}

/**
 * Implements hook_search_api_tracker_info_alter().
 */
function search_api_test_hooks_search_api_tracker_info_alter(array &$tracker_info) {
  $tracker_info['search_api_test']['label'] = 'Good luck';
}

/**
 * Implements hook_search_api_displays_alter().
 */
function search_api_test_hooks_search_api_displays_alter(array &$displays) {
  if (isset($displays['views_page:search_api_test_view__page_1'])) {
    $displays['views_page:search_api_test_view__page_1']['label'] = 'Some funny label for testing';
  }
}

/**
 * Implements hook_search_api_index_items_alter().
 */
function search_api_test_hooks_search_api_index_items_alter(IndexInterface $index, array &$items) {
  unset($items['entity:node/1:en']);
  \Drupal::messenger()->addStatus('Stormy');
}

/**
 * Implements hook_search_api_items_indexed().
 */
function search_api_test_hooks_search_api_items_indexed(IndexInterface $index, array $item_ids) {
  \Drupal::messenger()->addStatus('Please set me at ease');
}

/**
 * Implements hook_search_api_query_alter().
 */
function search_api_test_hooks_search_api_query_alter(QueryInterface &$query) {
  \Drupal::messenger()->addStatus('Funky blue note');
  \Drupal::messenger()->addStatus("Search id: {$query->getSearchId(FALSE)}");
  $query->addTag('andrew_hill');
}

/**
 * Implements hook_search_api_results_alter().
 */
function search_api_test_hooks_search_api_results_alter(ResultSetInterface &$results) {
  \Drupal::messenger()->addStatus('Stepping into tomorrow');
}

/**
 * Implements hook_search_api_index_reindex().
 */
function search_api_test_hooks_search_api_index_reindex(IndexInterface $index, $clear = FALSE) {
  \Drupal::messenger()->addStatus('Montara');
}

/**
 * Implements hook_search_api_data_type_info_alter().
 */
function search_api_test_hooks_search_api_data_type_info_alter(array &$data_type_definitions) {
  if (isset($data_type_definitions['text'])) {
    $data_type_definitions['text']['label'] = 'Peace/Dolphin dance';
  }
}

/**
 * Implements hook_search_api_field_type_mapping_alter().
 */
function search_api_test_hooks_search_api_field_type_mapping_alter(array &$mapping) {
  $mapping['datetime_iso8601'] = FALSE;
  $mapping['timestamp'] = FALSE;
}
