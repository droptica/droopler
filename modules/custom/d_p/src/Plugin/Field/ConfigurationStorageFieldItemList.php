<?php

namespace Drupal\d_p\Plugin\Field;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\d_p\ParagraphSettingSelectInterface;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Provides field item list class for configuration storage field type.
 *
 * @package Drupal\d_p\Plugin\Field
 */
class ConfigurationStorageFieldItemList extends FieldItemList implements ConfigurationStorageFieldItemListInterface {
  use LoggerChannelTrait;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Paragraph setting plugin manager.
   *
   * @var \Drupal\d_p\ParagraphSettingPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    $this->pluginManager = \Drupal::service('d_p.paragraph_settings.plugin.manager');
    $this->logger = $this->getLogger('d_p');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = parent::getValue();

    return $value[0] ?? new \StdClass();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    return $this->toEncodedValue($value);
  }

  /**
   * {@inheritdoc}
   */
  public function hasClasses(): bool {
    return (bool) count($this->getClassesArrayValue());
  }

  /**
   * {@inheritdoc}
   */
  public function hasClass($classes): bool {
    if (is_array($classes)) {
      foreach ($classes as $class) {
        if ($this->hasClass($class)) {
          return TRUE;
        }
      }
      return FALSE;
    }

    return in_array((string) $classes, $this->getClassesArrayValue());
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    $classes = $this->getClassesArrayValue();

    $this->processDefaultClasses($classes);

    return array_unique(array_filter($classes));
  }

  /**
   * Get value containing classes only.
   *
   * @return string
   *   Classes string.
   */
  protected function getClassesValue() {
    return $this->getValue()->{ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME} ?? '';
  }

  /**
   * Get classes value as array.
   *
   * @return array
   *   Array of CSS classes.
   */
  protected function getClassesArrayValue(): array {
    $classes = $classes_value = $this->getClassesValue();

    if (is_object($classes_value)) {
      $classes = get_object_vars($classes_value);
    }
    elseif (is_string($classes_value)) {
      $classes = explode(self::CSS_CLASS_DELIMITER, $classes_value);
    }
    elseif (!is_array($classes)) {
      $classes = [];
    }

    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  public function addClass(string $value): ConfigurationStorageFieldItemListInterface {
    $classes = $this->getClassesArrayValue();
    $classes[] = $value;

    $this->setClasses($classes);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeClass(string $value): ConfigurationStorageFieldItemListInterface {
    if ($this->hasClass($value)) {
      $classes = $this->getClassesArrayValue();

      unset($classes[array_search($value, $classes)]);

      $this->setClasses($classes);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceClass(string $old_value, string $new_value): ConfigurationStorageFieldItemListInterface {
    if ($this->hasClass($old_value)) {
      $classes = $this->getClassesArrayValue();
      $classes[array_search($old_value, $classes)] = $new_value;

      $this->setClasses($classes);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setClasses(array $classes): void {
    $values = $this->getValue();
    $values->{ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME} = implode(self::CSS_CLASS_DELIMITER, array_unique($classes));

    $this->setEncodedValue($values);
  }

  /**
   * Process default classes on classes set.
   *
   * @param array $classes
   *   Classes stored in the field value.
   */
  protected function processDefaultClasses(array &$classes) {
    $defaults = $this->getStorageItemDefaultClasses(ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME);
    foreach ($defaults as $modifier) {
      $existing_classes = array_intersect($modifier['options'], $classes);
      // Add default classes if any value from options is not present.
      if (empty($existing_classes)) {
        $classes[] = $modifier['default'];
      }
      elseif (count($existing_classes) > 1) {
        // Filter out the default value in case it was added accidentally.
        if (in_array($modifier['default'], $existing_classes)) {
          unset($classes[array_search($modifier['default'], $classes)]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSettingValue(string $setting_name): bool {
    return !is_null($this->getSettingValue($setting_name));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingValue(string $setting_name, $default = NULL) {
    return $this->getValue()->$setting_name ?? $this->getStorageItemDefaultValue($setting_name) ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingValue(string $name, $value): ConfigurationStorageFieldItemListInterface {
    $values = $this->getValue();
    $values->$name = $value;

    $this->setEncodedValue($values);

    return $this;
  }

  /**
   * Set value as JSON encoded string.
   *
   * @param mixed $values
   *   The values to be set.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function setEncodedValue($values) {
    $this->setValue($this->toEncodedValue($values), TRUE);
  }

  /**
   * Convert given values to a field value with encoded data.
   *
   * @param mixed $value
   *   The values to be set.
   *
   * @return array[]
   *   The field value.
   */
  protected function toEncodedValue($value): array {
    return [
      [
        'value' => json_encode($value),
      ],
    ];
  }

  /**
   * Getter for storage item default value.
   *
   * @param string $storage_id
   *   The storage id.
   *
   * @return mixed|null
   *   The default value.
   */
  protected function getStorageItemDefaultValue(string $storage_id) {
    try {
      return $this->loadStorageItemById($storage_id)->getDefaultValue();
    }
    catch (PluginException $exception) {
      return NULL;
    }
  }

  /**
   * Getter for storage item default classes.
   *
   * @param string $storage_id
   *   The storage id.
   *
   * @return array
   *   The default classes.
   */
  protected function getStorageItemDefaultClasses(string $storage_id): array {
    $defaults = [];

    try {
      $plugin = $this->loadStorageItemById($storage_id);
      /** @var \Drupal\d_p\ParagraphSettingInterface[] $plugins */
      $class_plugins = $plugin->getChildrenPlugins();

      foreach ($class_plugins as $plugin) {
        if ($plugin instanceof ParagraphSettingSelectInterface) {
          $defaults[] = [
            'options' => array_keys($plugin->getOptions()),
            'default' => $plugin->getDefaultValue(),
          ];
        }
      }
    }
    catch (PluginException $exception) {
      $this->logger->error($exception->getMessage());
    }

    return $defaults;
  }

  /**
   * Loads storage item.
   *
   * @param string $storage_id
   *   The storage id (plugin id).
   *
   * @return \Drupal\d_p\ParagraphSettingInterface|object
   *   The plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function loadStorageItemById(string $storage_id) {
    return $this->pluginManager->getPluginById($storage_id);
  }

}
