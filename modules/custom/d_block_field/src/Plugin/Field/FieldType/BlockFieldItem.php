<?php

declare(strict_types=1);

namespace Drupal\d_block_field\Plugin\Field\FieldType;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
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
 *   category = "reference",
 *   default_widget = "d_block_field_default",
 *   default_formatter = "d_block_field",
 * )
 */
class BlockFieldItem extends FieldItemBase implements BlockFieldItemInterface {

  /**
   * Plugin id reserved by core's BrokenBlock fallback plugin.
   */
  protected const string BROKEN_BLOCK_ID = 'broken';

  /**
   * Plugin id of the block_content derivative wrapper.
   */
  protected const string BLOCK_CONTENT_ID = 'block_content';

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
      'plugin_categories' => [],
      'plugin_categories_exclude' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName(): string {
    return 'plugin_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];
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
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'plugin_id' => [
          'description' => 'The block plugin id',
          'type'        => 'varchar',
          'length'      => 255,
        ],
        'settings' => [
          'description' => 'Serialized array of settings for the block.',
          'type'        => 'blob',
          'size'        => 'big',
          'serialize'   => TRUE,
        ],
      ],
      'indexes' => ['plugin_id' => ['plugin_id']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $options = [];
    foreach ($this->getBlockManager()->getCategories() as $category) {
      $category_name = (string) $category;
      $options[$category_name] = $category_name;
    }

    $element = [];
    $element['plugin_categories'] = [
      '#title'         => $this->t('Plugins categories'),
      '#description'   => $this->t('Leave empty to allow all plugin categories.'),
      '#type'          => 'checkboxes',
      '#options'       => $options,
      '#default_value' => $this->getSetting('plugin_categories'),
    ];
    $element['plugin_categories_exclude'] = [
      '#title'         => $this->t('Exclude selected categories'),
      '#description'   => $this->t('If unchecked, only plugins from selected categories will be available. If checked, plugins from selected categories will be excluded.'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('plugin_categories_exclude'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('plugin_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE): void {
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'settings' => [],
      ];
      if (is_string($values['settings'])) {
        $values['settings'] = unserialize($values['settings'], ['allowed_classes' => FALSE]);
      }
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getBlock(): ?BlockPluginInterface {
    if (empty($this->plugin_id)) {
      return NULL;
    }

    $block_instance = $this->getBlockManager()->createInstance($this->plugin_id, $this->settings ?? []);
    if (!$block_instance instanceof BlockPluginInterface) {
      return NULL;
    }

    $plugin_definition = $block_instance->getPluginDefinition();
    if (($plugin_definition['id'] ?? NULL) === self::BROKEN_BLOCK_ID) {
      return NULL;
    }

    if (($plugin_definition['id'] ?? NULL) === self::BLOCK_CONTENT_ID) {
      $uuid = $block_instance->getDerivativeId();
      if ($uuid === NULL || $this->getEntityRepository()->loadEntityByUuid('block_content', $uuid) === NULL) {
        return NULL;
      }
    }

    return $block_instance;
  }

  /**
   * Lazy accessor for the block manager.
   *
   * FieldItem subclasses can't use constructor DI — the typed data manager
   * instantiates them with a fixed signature.
   */
  protected function getBlockManager(): BlockManagerInterface {
    return \Drupal::service('plugin.manager.block');
  }

  /**
   * Lazy accessor for the entity repository.
   */
  protected function getEntityRepository(): EntityRepositoryInterface {
    return \Drupal::service('entity.repository');
  }

}
