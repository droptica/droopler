<?php

namespace Drupal\d_p\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\d_p\Plugin\Field\FieldWidget\SettingsWidget;

/**
 * Provides field item list class for configuration storage field type.
 *
 * @package Drupal\d_p\Plugin\Field
 */
class ConfigurationStorageFieldItemList extends FieldItemList implements ConfigurationStorageFieldItemListInterface {

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
  public function hasClasses(): bool {
    return (bool) count($this->getClasses());
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

    return in_array((string) $classes, $this->getClasses());
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    $classes = explode(self::CSS_CLASS_DELIMITER, $this->getClassesValue()) ?: [];
    $this->appendDefaultClasses($classes);

    return $classes;
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
   * {@inheritdoc}
   */
  public function addClass(string $value): ConfigurationStorageFieldItemListInterface {
    $classes = $this->getClasses();
    $classes[] = $value;

    $this->setClasses($classes);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeClass(string $value): ConfigurationStorageFieldItemListInterface {
    if ($this->hasClass($value)) {
      $classes = $this->getClasses();

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
      $classes = $this->getClasses();
      $classes[array_search($old_value, $classes)] = $new_value;

      $this->setClasses($classes);
    }

    return $this;
  }

  /**
   * Setter for CSS classes.
   *
   * @param array $classes
   *   Classes to be set.
   */
  protected function setClasses(array $classes): void {
    $values = $this->getValue();
    $values->{ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME} = array_unique($classes);

    $this->setEncodedValue($values);
  }

  /**
   * Adds default classes to classes set.
   *
   * @param array $classes
   *   Default classes to be populated.
   */
  protected function appendDefaultClasses(array &$classes) {
    // Add default classes if not present.
    // @todo: We should have a better way to populate the default values.
    $defaults = SettingsWidget::getModifierDefaults();
    foreach ($defaults as $modifier) {
      foreach ($modifier['options'] as $option) {
        if (in_array($option, $classes)) {
          continue 2;
        }
      }
      $classes[] = $modifier['default'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSettingValue(string $setting_name): bool {
    return in_array($setting_name, $this->getValue()->$setting_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingValue(string $setting_name, $default = NULL) {
    return $this->getValue()->$setting_name ?? $default;
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
   *   Values to be set.
   */
  protected function setEncodedValue($values) {
    $this->setValue(json_encode($values), TRUE);
  }

}
