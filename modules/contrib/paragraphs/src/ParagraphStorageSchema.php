<?php

namespace Drupal\paragraphs;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Extends the paragraphs schema handler.
 */
class ParagraphStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['paragraphs_item_field_data']['indexes'] += array(
      'paragraphs__parent_fields' => array('parent_type', 'parent_id', 'parent_field_name'),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    // Setting the initial value to 1 when we add a 'status' field.
    // @todo this is a workaround for https://www.drupal.org/node/2346019
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    if ($storage_definition->getName() == 'status') {
      $schema['fields']['status']['initial'] = 1;
    }

    if ($storage_definition->getName() == 'behavior_settings') {
      $schema['fields']['behavior_settings']['initial'] = serialize([]);
    }
    return $schema;
  }
}
