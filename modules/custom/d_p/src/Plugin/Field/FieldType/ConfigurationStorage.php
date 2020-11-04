<?php

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemListInterface;
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
  public function getValue() {
    $values = parent::getValue();

    return json_decode($values['value'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    return $value === NULL || $value === '' || $value === [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Config Settings'));

    return $properties;
  }

  /**
   * Gets the settings field from a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Fieldable entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   Field, defaults to null.
   */
  public static function getSettingsFieldFromEntity(FieldableEntityInterface $entity):? FieldItemListInterface {
    /** @var \Drupal\field\FieldConfigInterface[] $fiels_definitions */
    $fiels_definitions = $entity->getFieldDefinitions();

    foreach ($fiels_definitions as $field_name => $field) {
      if ($field->getType() == 'field_p_configuration_storage') {
        return $entity->$field_name;
      }
    }

    return NULL;
  }

}
