<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz.
 *
 * Can be used for setting up a base Search API DB installation.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Set the schema versions.
$versions = [
  'search_api' => 8104,
  'search_api_db' => 8101,
];
foreach ($versions as $name => $version) {
  $connection->insert('key_value')
    ->fields([
      'collection' => 'system.schema',
      'name' => $name,
      'value' => serialize($version),
    ])
    ->execute();
}

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['search_api'] = 0;
$extensions['module']['search_api_db'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Install the default configuration.
$configs['search_api.settings'] = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../config/install/search_api.settings.yml'));
$configs['search_api_db.settings'] = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/search_api_db.settings.yml'));
foreach ($configs as $name => $config) {
  $data = $connection->insert('config')
    ->fields([
      'name' => $name,
      'data' => serialize($config),
      'collection' => '',
    ])
    ->execute();
}
