<?php

namespace Drupal\d_color\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'd_color' field type.
 *
 * @FieldType(
 *   id = "d_color_field_type",
 *   label = @Translation("Color"),
 *   description = @Translation("Color"),
 *   default_widget = "d_color_field_widget_default",
 *   default_formatter = "d_color_field_formatter_default"
 * )
 */
class ColorFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'color' => [
          'type' => 'varchar',
          'length' => '7',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $color_field = $this->get('color');
    $color_value = $color_field ? $color_field->getValue() : NULL;

    return $color_value === NULL || $color_value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['color'] = DataDefinition::create('string')->setLabel(t('Color'));

    return $properties;
  }

}
