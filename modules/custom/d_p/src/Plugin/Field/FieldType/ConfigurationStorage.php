<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\d_p\Exception\MissingConfigurationStorageFieldException;
use Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface;

/**
 * Plugin implementation of the 'field_p_settings' field type.
 *
 * @FieldType(
 *   id = "field_p_configuration_storage",
 *   label = @Translation("Configuration storage"),
 *   module = "d_p",
 *   description = @Translation("Configuration storage"),
 *   default_widget = "field_d_p_set_settings",
 *   default_formatter = "string",
 *   list_class = "\Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemList"
 * )
 */
class ConfigurationStorage extends FieldItemBase implements ConfigurationStorageInterface {

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

    return json_decode($values['value'] ?? '');
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param object|array|string|null $values
   *   A property values.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If a property is updated from a parent object, set it as FALSE to
   *   avoid being notified again.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function setValue($values, $notify = TRUE): void {
    $config_value = [
      'value' => '',
    ];

    if (is_object($values)) {
      $config_value['value'] = json_encode($values);
    }

    if (is_string($values)) {
      $config_value['value'] = $values;
    }

    if (is_array($values) && isset($values['value'])) {
      $config_value = $values;
    }

    parent::setValue($config_value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    return $value === NULL;
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
   * {@inheritdoc}
   */
  public static function getSettingsFieldFromEntity(FieldableEntityInterface $entity): ?ConfigurationStorageFieldItemListInterface {
    /** @var \Drupal\field\FieldConfigInterface[] $fiels_definitions */
    $fiels_definitions = $entity->getFieldDefinitions();

    foreach ($fiels_definitions as $field_name => $field) {
      if ($field->getType() == 'field_p_configuration_storage') {
        return $entity->$field_name;
      }
    }

    throw new MissingConfigurationStorageFieldException(sprintf(
      "No instance of configuration storage found on entity %s of bundle %s",
      $entity->getEntityType()->id(),
      $entity->bundle()
    ));
  }

}
