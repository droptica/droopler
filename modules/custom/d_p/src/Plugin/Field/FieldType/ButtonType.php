<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of ButtonType.
 *
 * @FieldType(
 *   id = "button_type",
 *   label = @Translation("Button type"),
 *   default_formatter = "string",
 *   default_widget = "button_type_widget",
 * )
 */
class ButtonType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'button_type' => [
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
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['button_type'] = DataDefinition::create('string')
      ->setLabel(t('Button type'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('button_type')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return $this->get('button_type')->getValue();
  }

}
