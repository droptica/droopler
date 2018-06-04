<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs\Traits\FieldCollectionSourceData;

/**
 * Test the field_collection_type source plugin.
 *
 * @covers \Drupal\paragraphs\Plugin\migrate\source\d7\FieldCollectionType
 * @group paragraphs
 */
class FieldCollectionTypeSourceTest extends MigrateSqlSourceTestBase {
  use FieldCollectionSourceData;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'paragraphs'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $data = $this->getSourceData();
    $data[0]['expected_results'] = [
      [
        'id' => '1',
        'field_name' => 'field_field_collection_field',
        'module' => 'field_collection',
        'active' => '1',
        'data' => 'serialized field collection field data',
        'name' => 'Field collection field',
        'bundle' => 'field_collection_field',
        'description' => '',
      ],
    ];
    return $data;
  }

}
