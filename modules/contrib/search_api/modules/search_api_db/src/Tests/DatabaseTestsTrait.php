<?php

namespace Drupal\search_api_db\Tests;

use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Provides some common helper methods for database tests.
 */
trait DatabaseTestsTrait {

  /**
   * Asserts that the given table exists and has a primary key.
   *
   * @param string $table
   *   The name of the table.
   * @param string|null $message
   *   (optional) The message to print for the assertion, or NULL to use an
   *   automatically generated one.
   */
  protected function assertHasPrimaryKey($table, $message = NULL) {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists($table), "Table $table exists.");

    if (!$message) {
      $message = "Table $table has a primary key.";
    }
    // The database layer doesn't support generic introspection into primary
    // keys. The simplest way to test whether a primary key exists is therefore
    // to try to create one and see whether that leads to an exception.
    try {
      $schema->addPrimaryKey($table, []);
      $this->assertTrue(FALSE, $message);
    }
    catch (SchemaObjectExistsException $e) {
      $this->assertTrue(TRUE, $message);
    }
    catch (\Exception $e) {
      // Trying to create a primary key with an empty fields list will probably
      // still throw an exception, so we catch that as well.
      $this->assertTrue(FALSE, $message);
    }
  }

  /**
   * Asserts that the given table exists and does not have a primary key.
   *
   * @param string $table
   *   The name of the table.
   * @param string|null $message
   *   (optional) The message to print for the assertion, or NULL to use an
   *   automatically generated one.
   */
  protected function assertNotHasPrimaryKey($table, $message = NULL) {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists($table), "Table $table exists.");

    if (!$message) {
      $message = "Table $table does not have a primary key.";
    }
    // The database layer doesn't support generic introspection into primary
    // keys. The simplest way to make sure a table does not have a primary key
    // is trying to drop it and checking the return value.
    $this->assertFalse($schema->dropPrimaryKey($table), $message);
  }

}
