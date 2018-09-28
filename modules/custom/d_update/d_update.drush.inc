<?php

/**
 * @file
 * File declaring drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function d_update_drush_command() {
  $commands['generate-config-hash'] = [
    'description' => 'Generates hash for entered config',
    'aliases' => ['gch'],
    'options' => [
      'config' => 'Config name',
    ],
    'examples' => [
      'drush gch node.type.content_page' => 'Generates hash for node type article',
    ],
  ];

  return $commands;
}

/**
 * Drush command logic for generating config hash.
 */
function drush_d_update_generate_config_hash($opt = NULL) {
  $check = \Drupal::hasService('d_update.config_manager');
  if ($check == FALSE) {
    drush_print(dt('Service to generate hash is missing.'));
    return;
  }
  $hash = \Drupal::service('d_update.config_manager')->generateHashFromDatabase($opt);
  drush_print(dt('Hash for config @opt: ' . $hash, ['@opt' => $opt]));
}
