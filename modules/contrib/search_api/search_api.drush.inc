<?php

/**
 * @file
 * Drush commands for Search API.
 */

use Drupal\search_api\ConsoleException;
use Drupal\search_api\Utility\CommandHelper;

/**
 * Implements hook_drush_command().
 */
function search_api_drush_command() {
  $items = [];

  $index['index_id'] = dt('The machine name of an index');
  $server['server_id'] = dt('The machine name of a server');

  $items['search-api-list'] = [
    'description' => 'List all search indexes.',
    'examples' => [
      'drush search-api-list' => dt('List all search indexes.'),
      'drush sapi-l' => dt('Alias to list all search indexes.'),
    ],
    'aliases' => ['sapi-l'],
  ];

  $items['search-api-enable'] = [
    'description' => 'Enable one or more disabled search indexes.',
    'examples' => [
      'drush search-api-enable node_index' => dt('Enable the search index with the ID @name.', ['@name' => 'node_index']),
      'drush sapi-en node_index' => dt('Alias to enable the search index with the ID @name.', ['@name' => 'node_index']),
    ],
    'arguments' => $index,
    'aliases' => ['sapi-en'],
  ];

  $items['search-api-enable-all'] = [
    'description' => 'Enable all disabled search indexes.',
    'examples' => [
      'drush search-api-enable-all' => dt('Enable all disabled indexes.'),
      'drush sapi-ena' => dt('Alias to enable all disabled indexes.'),
    ],
    'arguments' => [],
    'aliases' => ['sapi-ena'],
  ];

  $items['search-api-disable'] = [
    'description' => 'Disable one or more enabled search indexes.',
    'examples' => [
      'drush search-api-disable node_index' => dt('Disable the search index with the ID @name.', ['@name' => 'node_index']),
      'drush sapi-dis node_index' => dt('Alias to disable the search index with the ID @name.', ['@name' => 'node_index']),
    ],
    'arguments' => $index,
    'aliases' => ['sapi-dis'],
  ];

  $items['search-api-disable-all'] = [
    'description' => 'Disable all enabled search indexes.',
    'examples' => [
      'drush search-api-disable-all' => dt('Disable all enabled indexes.'),
      'drush sapi-disa' => dt('Alias to disable all enabled indexes.'),
    ],
    'arguments' => [],
    'aliases' => ['sapi-disa'],
  ];

  $items['search-api-status'] = [
    'description' => 'Show the status of one or all search indexes.',
    'examples' => [
      'drush search-api-status' => dt('Show the status of all search indexes.'),
      'drush sapi-s' => dt('Alias to show the status of all search indexes.'),
      'drush sapi-s node_index' => dt('Show the status of the search index with the ID @name.', ['@name' => 'node_index']),
    ],
    'arguments' => $index,
    'aliases' => ['sapi-s'],
  ];

  $items['search-api-index'] = [
    'description' => 'Index items for one or all enabled search indexes.',
    'examples' => [
      'drush search-api-index' => dt('Index all items for all enabled indexes.'),
      'drush sapi-i' => dt('Alias to index all items for all enabled indexes.'),
      'drush sapi-i node_index' => dt('Index all items for the index with the ID @name.', ['@name' => 'node_index']),
      'drush sapi-i node_index 100' => dt('Index a maximum number of @limit items for the index with the ID @name.', ['@limit' => 100, '@name' => 'node_index']),
      'drush sapi-i node_index 100 10' => dt('Index a maximum number of @limit items (@batch_size items per batch run) for the index with the ID @name.', ['@limit' => 100, '@batch_size' => 10, '@name' => 'node_index']),
    ],
    'options' => [
      'limit' => dt('The number of items to index. Set to 0 to index all items. Defaults to 0 (index all).'),
      'batch-size' => dt('The number of items to index per batch run. Set to 0 to index all items at once. Defaults to the "!batch_size_label" setting of the index.', ['!batch_size_label' => dt('Cron batch size')]),
    ],
    'arguments' => $index,
    'aliases' => ['sapi-i'],
  ];

  $items['search-api-reset-tracker'] = [
    'description' => 'Force reindexing of one or all search indexes, without deleting existing index data.',
    'examples' => [
      'drush search-api-reindex' => dt('Schedule all search indexes for reindexing.'),
      'drush sapi-r' => dt('Alias to schedule all search indexes for reindexing .'),
      'drush sapi-r node_index' => dt('Schedule the search index with the ID @name for reindexing.', ['@name' => 'node_index']),
    ],
    'options' => [
      'entity-types' => [
        'description' => dt('List of entity type ids to reset tracker for.'),
        'example_value' => 'user,node',
      ],
    ],
    'arguments' => $index,
    'aliases' => [
      'search-api-mark-all',
      'search-api-reindex',
      'sapi-r',
    ],
  ];

  $items['search-api-clear'] = [
    'description' => 'Clear one or all search indexes and mark them for reindexing.',
    'examples' => [
      'drush search-api-clear' => dt('Clear all search indexes.'),
      'drush sapi-c' => dt('Alias to clear all search indexes.'),
      'drush sapi-c node_index' => dt('Clear the search index with the ID @name.', ['@name' => 'node_index']),
    ],
    'arguments' => $index,
    'aliases' => ['sapi-c'],
  ];

  $items['search-api-search'] = [
    'description' => 'Search for a keyword or phrase in a given index.',
    'examples' => [
      'drush search-api-search node_index title' => dt('Search for "title" inside the "node_index" index.'),
      'drush sapi-search node_index title' => dt('Alias to search for "title" inside the "node_index" index.'),
    ],
    'arguments' => $index + [
      'keyword' => dt('The keyword to look for.'),
    ],
    'aliases' => ['sapi-search'],
  ];

  $items['search-api-server-list'] = [
    'description' => 'List all search servers.',
    'examples' => [
      'drush search-api-server-list' => dt('List all search servers.'),
      'drush sapi-sl' => dt('Alias to list all search servers.'),
    ],
    'aliases' => ['sapi-sl'],
  ];

  $items['search-api-server-enable'] = [
    'description' => 'Enable a search server.',
    'examples' => [
      'drush search-api-server-e my_solr_server' => dt('Enable the @server search server.', ['@server' => 'my_solr_server']),
      'drush sapi-se my_solr_server' => dt('Alias to enable the @server search server.', ['@server' => 'my_solr_server']),
    ],
    'arguments' => $server,
    'aliases' => ['sapi-se'],
  ];

  $items['search-api-server-disable'] = [
    'description' => 'Disable a search server.',
    'examples' => [
      'drush search-api-server-disable' => dt('Disable the @server search server.', ['@server' => 'my_solr_server']),
      'drush sapi-sd' => dt('Alias to disable the @server search server.', ['@server' => 'my_solr_server']),
    ],
    'arguments' => $server,
    'aliases' => ['sapi-sd'],
  ];

  $items['search-api-server-clear'] = [
    'description' => 'Clear all search indexes on the search server and mark them for reindexing.',
    'examples' => [
      'drush search-api-server-clear' => dt('Clear all search indexes on the search server @server.', ['@server' => 'my_solr_server']),
      'drush sapi-sc' => dt('Alias to clear all search indexes on the search server @server.', ['@server' => 'my_solr_server']),
    ],
    'arguments' => $server,
    'aliases' => ['sapi-sc'],
  ];

  $items['search-api-set-index-server'] = [
    'description' => 'Set the search server used by a given index.',
    'examples' => [
      'drush search-api-set-index-server default_node_index my_solr_server' => dt('Set the @index index to used the @server server.', ['@index' => 'default_node_index', '@server' => 'my_solr_server']),
      'drush sapi-sis default_node_index my_solr_server' => dt('Alias to set the @index index to used the @server server.', ['@index' => 'default_node_index', '@server' => 'my_solr_server']),
    ],
    'arguments' => $index + $server,
    'aliases' => ['sapi-sis'],
  ];

  return $items;
}

/**
 * Prints a list of all search indexes.
 */
function drush_search_api_list() {
  $command_helper = _search_api_drush_command_helper();
  $rows[] = [
    dt('ID'),
    dt('Name'),
    dt('Server'),
    dt('Type'),
    dt('Status'),
    dt('Limit'),
  ];
  $rows += $command_helper->indexListCommand();
  foreach ($rows as &$row) {
    $row['types'] = is_array($row['types']) ? implode(', ', $row['types']) : $row['types'];
    $row['typeNames'] = is_array($row['types']) ? implode(', ', $row['typeNames']) : $row['types'];
  }
  drush_print_table($rows);
}

/**
 * Enables one or more search indexes.
 *
 * @param string|null $index_id
 *   The ID of a search index to enable. Or NULL (only used internally) to
 *   enable all disabled indexes.
 */
function drush_search_api_enable($index_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->enableIndexCommand([$index_id]);
  }
  catch (ConsoleException $exception) {
    drush_set_error($exception->getMessage());
  }
}

/**
 * Enables all search indexes.
 */
function drush_search_api_enable_all() {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->enableIndexCommand();
  }
  catch (ConsoleException $exception) {
    drush_set_error($exception->getMessage());
  }
}

/**
 * Disables one or more search indexes.
 *
 * @param string|null $index_id
 *   The ID of a search index to disable. Or NULL (only used internally) to
 *   disable all enabled indexes.
 */
function drush_search_api_disable($index_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->disableIndexCommand([$index_id]);
  }
  catch (ConsoleException $exception) {
    drush_set_error($exception->getMessage());
  }
}

/**
 * Disables all search indexes.
 */
function drush_search_api_disable_all() {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->disableIndexCommand();
  }
  catch (ConsoleException $exception) {
    drush_set_error($exception->getMessage());
  }
}

/**
 * Displays the status of one or all search indexes.
 *
 * @param string|null $index_id
 *   (optional) The ID of the search index whose status should be displayed, or
 *   NULL to display the status of all search indexes.
 */
function drush_search_api_status($index_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  $rows[] = [
    dt('ID'),
    dt('Name'),
    dt('% Complete'),
    dt('Indexed'),
    dt('Total'),
  ];
  $rows += $command_helper->indexStatusCommand([$index_id]);

  drush_print_table($rows);
}

/**
 * Indexes items.
 *
 * @param string|null $index_id
 *   (optional) The index ID for which items should be indexed, or NULL to index
 *   items on all indexes.
 */
function drush_search_api_index($index_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  $limit = drush_get_option('limit');
  $batch_size = drush_get_option('batch-size');

  $batch_set = $command_helper->indexItemsToIndexCommand([$index_id], $limit, $batch_size);
  if ($batch_set) {
    drush_backend_batch_process();
  }
}

/**
 * Schedules a search index for reindexing.
 *
 * @param string|null $index_id
 *   (optional) The index ID for which items should be reindexed, or NULL to
 *   reindex all search indexes.
 */
function drush_search_api_reset_tracker($index_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  $entity_types = drush_get_option_list('entity-types');
  $command_helper->resetTrackerCommand([$index_id], $entity_types);
}

/**
 * Clears a search index.
 *
 * @param string|null $index_id
 *   (optional) The ID of the search index which should be cleared, or NULL to
 *   clear all search indexes.
 */
function drush_search_api_clear($index_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  $command_helper->clearIndexCommand([$index_id]);
}

/**
 * Executes a simple keyword search and displays the results in a table.
 *
 * @param string $index_id
 *   The ID of the index being searched.
 * @param string $keyword
 *   The search keyword.
 */
function drush_search_api_search($index_id, $keyword) {
  $command_helper = _search_api_drush_command_helper();
  $rows = $command_helper->searchIndexCommand($index_id, $keyword);
  drush_print_table($rows);
}

/**
 * Lists all available search servers.
 */
function drush_search_api_server_list() {
  $command_helper = _search_api_drush_command_helper();
  $rows[] = [
    dt('ID'),
    dt('Name'),
    dt('Status'),
  ];

  try {
    $rows += $command_helper->serverListCommand();
  }
  catch (ConsoleException $exception) {
    drush_print($exception->getMessage());
  }

  drush_print_table($rows);
}

/**
 * Enables a search server.
 *
 * @param string $server_id
 *   The ID of the server to enable.
 */
function drush_search_api_server_enable($server_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->enableServerCommand($server_id);
  }
  catch (ConsoleException $exception) {
    drush_print($exception->getMessage());
  }
}

/**
 * Disables a search server.
 *
 * @param string $server_id
 *   The ID of the server to disable.
 */
function drush_search_api_server_disable($server_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->disableServerCommand($server_id);
  }
  catch (ConsoleException $exception) {
    drush_print($exception->getMessage());
  }
}

/**
 * Clears all search indexes on the server and marks them for reindexing.
 *
 * @param string $server_id
 *   The ID of the server to clear all search indexes.
 */
function drush_search_api_server_clear($server_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->clearServerCommand($server_id);
  }
  catch (ConsoleException $exception) {
    drush_print($exception->getMessage());
  }
}

/**
 * Sets the server for a given index.
 *
 * @param string $index_id
 *   The ID of the index whose server should be changed.
 * @param string $server_id
 *   The ID of the new server for the index.
 */
function drush_search_api_set_index_server($index_id = NULL, $server_id = NULL) {
  $command_helper = _search_api_drush_command_helper();
  try {
    $command_helper->setIndexServerCommand($index_id, $server_id);
  }
  catch (ConsoleException $exception) {
    drush_print($exception->getMessage());
  }
}

/**
 * Returns an instance of the command helper.
 *
 * @return \Drupal\search_api\Utility\CommandHelper
 *   An instance of the command helper class.
 */
function _search_api_drush_command_helper() {
  $command_helper = new CommandHelper(\Drupal::entityTypeManager(), \Drupal::moduleHandler(), 'dt');
  $command_helper->setLogger(\Drupal::logger('search_api'));
  return $command_helper;
}
