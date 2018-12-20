<?php

namespace Drupal\Tests\paragraphs\Traits;

/**
 * Provide data to the paragraphs source plugin tests.
 */
trait ParagraphsSourceData {

  /**
   * Provides a source data array for the source tests.
   *
   * @return array
   *   The source data
   */
  protected function getSourceData() {
    $data = [];

    $data[]['source_data'] = [
      'paragraphs_bundle' => [
        [
          'bundle' => 'paragraphs_field',
          'name' => 'Paragraphs Field',
          'locked' => '1',
        ],
      ],
      'field_config_instance' => [
        [
          'field_name' => 'field_text',
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'data' => 'Serialized Instance Data',
          'deleted' => '0',
          'field_id' => '1',
        ],
      ],
      'field_config' => [
        [
          'id' => '1',
          'field_name' => 'field_text',
          'translatable' => '1',
        ],
      ],
      'field_revision_field_text' => [
        [
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'PID1R1 text',
        ],
        [
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'PID2R2 text',
        ],
        [
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '3',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'PID2R3 text',
        ],
      ],
      'paragraphs_item' => [
        [
          'item_id' => '1',
          'revision_id' => '1',
          'field_name' => 'field_paragraphs_field',
          'bundle' => 'paragraphs_field',
          'archived' => '0',
        ],
        [
          'item_id' => '2',
          'revision_id' => '3',
          'field_name' => 'field_paragraphs_field',
          'bundle' => 'paragraphs_field',
          'archived' => 0,
        ],
      ],
      'paragraphs_item_revision' => [
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
    ];
    return $data;
  }

}
