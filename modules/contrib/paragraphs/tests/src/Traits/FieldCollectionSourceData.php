<?php

namespace Drupal\Tests\paragraphs\Traits;

/**
 * Provide data to the field collection source plugin tests.
 */
trait FieldCollectionSourceData {

  /**
   * Provides a source data array for the source tests.
   *
   * @return array
   *   The source data
   */
  protected function getSourceData() {
    $data = [];

    $data[]['source_data'] = [
      'field_collection_item' => [
        [
          'item_id' => '1',
          'revision_id' => '1',
          'field_name' => 'field_field_collection_field',
          'archived' => '0',
        ],
        [
          'item_id' => '2',
          'revision_id' => '3',
          'field_name' => 'field_field_collection_field',
          'archived' => 0,
        ],
      ],
      'field_collection_item_revision' => [
        [
          'item_id' => '1',
          'revision_id' => '1',
        ],
        [
          'item_id' => '2',
          'revision_id' => '2',
        ],
        [
          'item_id' => '2',
          'revision_id' => '3',
        ],
      ],
      'field_config' => [
        [
          'id' => '1',
          'field_name' => 'field_field_collection_field',
          'type' => 'field_collection',
          'module' => 'field_collection',
          'active' => '1',
          'data' => 'serialized field collection field data',
          'translatable' => '1',
        ],
      ],
      'field_config_instance' => [
        [
          'field_name' => 'field_text',
          'entity_type' => 'field_collection_item',
          'bundle' => 'field_field_collection_field',
          'data' => 'Serialized Instance Data',
          'deleted' => '0',
          'field_id' => '1',
        ],
      ],
      'field_revision_field_text' => [
        [
          'entity_type' => 'field_collection_item',
          'bundle' => 'field_field_collection_field',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'FCID1R1 text',
        ],
        [
          'entity_type' => 'field_collection_item',
          'bundle' => 'field_field_collection_field',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'FCID2R2 text',
        ],
        [
          'entity_type' => 'field_collection_item',
          'bundle' => 'field_field_collection_field',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '3',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'FCID2R3 text',
        ],
      ],
    ];
    return $data;

  }

}
