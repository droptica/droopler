<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz.
 *
 * Used for testing the search_api_db_update_8102() update.
 *
 * @see \Drupal\search_api_db\Tests\Update\SearchApiDbUpdate8102Test
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// The update hook needs the server config, though only the "database" config
// setting is actually relevant.
$server_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/../../../search_api_db_defaults/config/optional/search_api.server.default_server.yml'));

foreach ($server_configs as $server_config) {
  $connection->insert('config')
    ->fields([
      'collection' => '',
      'name' => 'search_api.server.' . $server_config['id'],
      'data' => serialize($server_config),
    ])
    ->execute();
}

foreach ([1, 2] as $i) {
  $name = "index_$i";
  $table = "search_api_db_$name";
  $value = [
    'server' => 'default_server',
    'index_table' => $table,
  ];
  $connection->insert('key_value')
    ->fields([
      'collection' => 'search_api_db.indexes',
      'name' => $name,
      'value' => serialize($value),
    ])
    ->execute();

  $definition = [
    'name' => $table,
    'module' => 'search_api_db',
    'fields' => [
      'item_id' => [
        'type' => 'varchar',
        'length' => 150,
        'description' => 'The primary identifier of the item',
        'not null' => TRUE,
      ],
    ],
  ];
  if ($i === 2) {
    $definition['primary key'] = ['item_id'];
  }
  $connection->schema()->createTable($table, $definition);
}
