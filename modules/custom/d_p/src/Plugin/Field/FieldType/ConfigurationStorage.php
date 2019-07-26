<?php

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_p_settings' field type.
 *
 * @FieldType(
 *   id = "field_p_configuration_storage",
 *   label = @Translation("Configuration storage"),
 *   module = "d_p",
 *   description = @Translation("Configuration storage"),
 *   default_widget = "field_d_p_set_settings",
 *   default_formatter = "string"
 * )
 */
class ConfigurationStorage extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Config Settings'));

    return $properties;
  }

}
