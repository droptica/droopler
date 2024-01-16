<?php

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
      'plugin_categories' => [],
      'plugin_categories_exclude' => FALSE,
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
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $categories = \Drupal::service('plugin.manager.block')->getCategories();
    foreach ($categories as $category) {
      $category_name = (string) $category;
      $options[$category_name] = $category_name;
    }

    $element['plugin_categories'] = [
      '#title' => $this->t('Plugins categories'),
      '#description' => $this->t('Leave empty to allow all plugin categories.'),
      '#type' => 'checkboxes',
      '#options' => $options ?? [],
      '#default_value' => $this->getSetting('plugin_categories'),
    ];
    $element['plugin_categories_exclude'] = [
      '#title' => $this->t('Exclude selected categories'),
      '#description' => $this->t('If unchecked, only plugins from selected categories will be available. If checked, plugins from selected categories will be excluded.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('plugin_categories_exclude'),
    ];

    return $element;
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
    $block_instance = $block_manager->createInstance($this->plugin_id, $this->settings);

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

}
