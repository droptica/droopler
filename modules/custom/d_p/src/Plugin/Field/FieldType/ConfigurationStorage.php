<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\d_p\Exception\MissingConfigurationStorageFieldException;
use Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface;

/**
 * Plugin implementation of the 'field_p_configuration_storage' field type.
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
   * Stored field type id, kept here as a typed constant to avoid magic strings.
   */
  public const string FIELD_TYPE = 'field_p_configuration_storage';

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
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
   *
   * Returns a decoded `\stdClass` (or NULL when storage is empty), so consumers
   * can read configuration via property access.
   */
  public function getValue() {
    $values = parent::getValue();
    return json_decode($values['value'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE): void {
    $config_value = ['value' => ''];

    if (is_object($values)) {
      $config_value['value'] = json_encode($values);
    }
    elseif (is_string($values)) {
      $config_value['value'] = $values;
    }
    elseif (is_array($values) && isset($values['value'])) {
      $config_value = $values;
    }

    parent::setValue($config_value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->get('value')->getValue() === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'value' => DataDefinition::create('string')->setLabel(t('Config Settings')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSettingsFieldFromEntity(FieldableEntityInterface $entity): ConfigurationStorageFieldItemListInterface {
    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
      if ($field_definition->getType() === self::FIELD_TYPE) {
        /** @var \Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface $field */
        $field = $entity->get($field_name);
        return $field;
      }
    }

    throw new MissingConfigurationStorageFieldException(sprintf(
      'No instance of configuration storage found on entity %s of bundle %s.',
      $entity->getEntityType()->id(),
      $entity->bundle(),
    ));
  }

}
