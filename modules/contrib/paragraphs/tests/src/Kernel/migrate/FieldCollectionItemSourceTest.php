<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs\Traits\FieldCollectionSourceData;

/**
 * Test the field_collection_item source plugin.
 *
 * @covers \Drupal\paragraphs\Plugin\migrate\source\d7\FieldCollectionItem
 * @group paragraphs
 */
class FieldCollectionItemSourceTest extends MigrateSqlSourceTestBase {
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
        'item_id' => '1',
        'revision_id' => '1',
        'field_name' => 'field_field_collection_field',
        'bundle' => 'field_collection_field',
        'archived' => '0',
        'field_text' => [
          0 => [
            'value' => 'FCID1R1 text',
          ],
        ],
      ],
      [
        'item_id' => '2',
        'revision_id' => '3',
        'field_name' => 'field_field_collection_field',
        'bundle' => 'field_collection_field',
        'archived' => '0',
        'field_text' => [
          0 => [
            'value' => 'FCID2R3 text',
          ],
        ],
      ],

    ];
    return $data;
  }

}
