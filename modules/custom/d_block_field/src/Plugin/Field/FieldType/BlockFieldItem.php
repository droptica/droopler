<?php

declare(strict_types = 1);

namespace Drupal\d_block_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\d_block_field\BlockFieldItemInterface;

/**
 * Plugin implementation of the 'd_block_field' field type.
 *
 * @FieldType(
 *   id = "d_block_field",
 *   label = @Translation("Block (plugin)"),
 *   description = @Translation("Stores an instance of a configurable or custom block."),
 *   category = @Translation("Reference"),
 *   default_widget = "d_block_field_default",
 *   default_formatter = "d_block_field",
 * )
 */
class BlockFieldItem extends FieldItemBase implements BlockFieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'plugin_ids' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'plugin_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['plugin_id'] = DataDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setRequired(TRUE);

    $properties['settings'] = MapDataDefinition::create()
      ->setLabel(t('Settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'plugin_id' => [
          'description' => 'The block plugin id',
          'type' => 'varchar',
          'length' => 255,
        ],
        'settings' => [
          'description' => 'Serialized array of settings for the block.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => ['plugin_id' => ['plugin_id']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('plugin_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'settings' => [],
      ];
    }
    // Unserialize the values.
    if (is_string($values['settings'])) {
      $values['settings'] = unserialize($values['settings'], ['allowed_classes' => FALSE]);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock() {
    if (empty($this->plugin_id)) {
      return NULL;
    }

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
    $block_instance = $block_manager->createInstance($this->plugin_id, $this->settings ?? []);

    $plugin_definition = $block_instance->getPluginDefinition();

    // Don't return broken block plugin instances.
    if ($plugin_definition['id'] == 'broken') {
      return NULL;
    }

    // Don't return broken block content instances.
    if ($plugin_definition['id'] == 'block_content') {
      $uuid = $block_instance->getDerivativeId();
      if (!\Drupal::service('entity.repository')->loadEntityByUuid('block_content', $uuid)) {
        return NULL;
      }
    }

    return $block_instance;
  }

  /**
   * Validates plugin_ids table select element.
   */
  public static function validatePluginIds(array &$element, FormStateInterface $form_state, &$complete_form) {
    $value = array_filter($element['#value']);
    if (array_keys($element['#options']) == array_keys($value)) {
      $form_state->setValueForElement($element, []);
    }
    else {
      $form_state->setValueForElement($element, $value);
    }
    return $element;
  }

}
