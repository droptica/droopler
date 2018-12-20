<?php

namespace Drupal\search_api_db\Tests\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\search_api_db\Tests\DatabaseTestsTrait;

/**
 * Tests whether search_api_db_update_8102() works correctly.
 *
 * @group search_api
 * @group legacy
 *
 * @see https://www.drupal.org/node/2884451
 */
class SearchApiDbUpdate8102Test extends UpdatePathTestBase {

  use DatabaseTestsTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // We need to manually set our entity types as "installed".
    $entity_type_ids = [
      'search_api_index',
      'search_api_server',
      'search_api_task'
    ];
    foreach ($entity_type_ids as $entity_type_id) {
      $entity_type = \Drupal::getContainer()
        ->get('entity_type.manager')
        ->getDefinition($entity_type_id);
      \Drupal::getContainer()
        ->get('entity_type.listener')
        ->onEntityTypeCreate($entity_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/search-api-db-base.php',
      __DIR__ . '/../../../tests/fixtures/update/search-api-db-update-8102.php',
    ];
  }

  /**
   * Tests whether search_api_db_update_8102() works correctly.
   *
   * @see https://www.drupal.org/node/2884451
   */
  public function testUpdate8102() {
    $this->assertNotHasPrimaryKey('search_api_db_index_1');
    $this->assertHasPrimaryKey('search_api_db_index_2');

    $this->runUpdates();

    $this->assertHasPrimaryKey('search_api_db_index_1');
    $this->assertHasPrimaryKey('search_api_db_index_2');
  }

}
