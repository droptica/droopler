<?php

/**
 * @file
 * Drush hooks.
 */

/**
 * Implements hook_drush_sql_sync_sanitize().
 *
 * Sanitize usernames and passwords that belong to subscribers.
 */
function d_p_subscribe_file_drush_sql_sync_sanitize($site) {
  $site_settings = drush_sitealias_get_record($site);
  $databases = sitealias_get_databases_from_record($site_settings);

  // Check if table name has to be wrapped.
  $wrap_table_name = (bool) drush_get_option('db-prefix');
  if ($wrap_table_name) {
    $table = '{{d_p_subscribe_file}}';
  } else {
    $table = 'd_p_subscribe_file';
  }

  // Define queries compatible with all database types supported by Drush.
  $db_driver = $databases['default']['default']['driver'];
  if ($db_driver == 'pgsql') {
    $query_mail = "UPDATE {$table} SET `mail` = CONCAT(LEFT(md5(random()::text), 8), '@example.localhost');";
    $query_name = "UPDATE {$table} SET `name` = LEFT(md5(random()::text), 8);";
  }
  elseif ($db_driver == 'mssql') {
    $query_mail = "UPDATE {$table} SET `mail` = CONCAT(LEFT(NEWID(), 8), '@example.localhost');";
    $query_name = "UPDATE {$table} SET `name` = LEFT(NEWID(), 8);";
  }
  else {
    $query_mail = "UPDATE {$table} SET `mail` = CONCAT(LEFT(UUID(), 8), '@example.localhost');";
    $query_name = "UPDATE {$table} SET `name` = LEFT(UUID(), 8);";
  }

  // Register queries.
  drush_sql_register_post_sync_op('sanitise_subscriber_mail', dt('Sanitise file subscriber\'s e-mail.'), $query_mail);
  drush_sql_register_post_sync_op('sanitise_subscriber_name', dt('Sanitise file subscriber\'s name.'), $query_name);
}
