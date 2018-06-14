<?php
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('path_redirect', array(
  'fields' => array(
    'rid' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ),
    'source' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ),
    'redirect' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ),
    'query' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ),
    'fragment' => array(
      'type' => 'varchar',
      'length' => 50,
      'not null' => FALSE,
    ),
    'language' => array(
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ),
    'type' => array(
      'type' => 'int',
      'size' => 'small',
      'not null' => TRUE,
    ),
    'last_used' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
  ),
  'primary key' => array('rid'),
  'unique keys' => array('source_language' => array('source', 'language')),
  'mysql_character_set' => 'utf8',
));


$connection->insert('path_redirect')
  ->fields(array(
    'rid',
    'source',
    'redirect',
    'query',
    'fragment',
    'language',
    'type',
    'last_used',
  ))
  ->values(array(
    'rid' => 5,
    'source' => 'test/source/url',
    'redirect' => 'test/redirect/url',
    'query' => NULL,
    'fragment' => NULL,
    'language' => '',
    'type' => 301,
    'last_used' => 1449497138,
  ))
  ->values(array(
    'rid' => 7,
    'source' => 'test/source/url2',
    'redirect' => 'http://test/external/redirect/url',
    'query' => 'foo=bar&biz=buz',
    'fragment' => NULL,
    'language' => 'en',
    'type' => 302,
    'last_used' => 1449497139,
  ))
  ->execute();

$connection->schema()->createTable('system', array(
  'fields' => array(
    'filename' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'owner' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'throttle' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'bootstrap' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'schema_version' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '-1',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'info' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'filename',
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('system')
  ->fields(array(
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ))
  ->values(array(
    'filename' => 'modules/contrib/path_redirect/path_redirect.module',
    'name' => 'path_redirect',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7000',
    'weight' => '0',
    'info' => 'a:10:{s:4:"name";s:13:"Path Redirect";s:11:"description";s:51:"Allows users to redirect from old URLs to new URLs.";s:7:"package";s:5:"Other";s:7:"version";s:3:"6.0";s:4:"core";s:3:"6.x";s:7:"project";s:13:"path_redirect";s:9:"datestamp";s:10:"1347989995";s:12:"dependencies";a:0:{}s:10:"dependents";a:0:{}s:3:"php";s:5:"4.3.5";}',
  ))
  ->execute();
