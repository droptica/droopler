<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field;

use Drupal\d_p\Enum\ParagraphSettingInterface;

/**
 * Provides interface for configuration storage field item list.
 */
interface ConfigurationStorageFieldItemListInterface {

  const CSS_CLASS_DELIMITER = ' ';

  /**
   * Check if the given class exists in classes.
   *
   * @param string|array $class_name
   *   Class name or list of classes to be searched.
   *
   * @return bool
   *   True if class was found, false otherwise.
   */
  public function hasClass($class_name): bool;

  /**
   * Check if there are any classes set.
   *
   * @return bool
   *   True if there is at least a single class, false otherwise.
   */
  public function hasClasses(): bool;

  /**
   * Getter for CSS classes.
   *
   * @return array
   *   List of css classes.
   */
  public function getClasses();

  /**
   * Add class to classes set.
   *
   * @param string $value
   *   Class to be added.
   *
   * @return $this
   */
  public function addClass(string $value): self;

  /**
   * Remove class from classes set.
   *
   * @param string $value
   *   Class to be removed.
   *
   * @return $this
   */
  public function removeClass(string $value): self;

  /**
   * Replace given class with a new value.
   *
   * @param string $old_value
   *   Old class to be replaced.
   * @param string $new_value
   *   New class to be set.
   *
   * @return $this
   */
  public function replaceClass(string $old_value, string $new_value): self;

  /**
   * Setter for CSS classes.
   *
   * @param array $classes
   *   Classes to be set.
   */
  public function setClasses(array $classes): void;

  /**
   * Check if the given setting exists.
   *
   * @param \Drupal\d_p\Enum\ParagraphSettingInterface $setting_name
   *   Setting name to be searched.
   *
   * @return bool
   *   True if setting was found, false otherwise.
   */
  public function hasSettingValue(ParagraphSettingInterface $setting_name): bool;

  /**
   * Returns given setting value if available.
   *
   * @param \Drupal\d_p\Enum\ParagraphSettingInterface $setting_name
   *   Setting name.
   * @param mixed|null $default
   *   Default value to be used.
   *
   * @return mixed|null
   *   Setting value or default if not found or empty.
   */
  public function getSettingValue(ParagraphSettingInterface $setting_name, $default = NULL);

  /**
   * Setter for setting value.
   *
   * @param \Drupal\d_p\Enum\ParagraphSettingInterface $name
   *   Setting name.
   * @param mixed $value
   *   Setting value.
   *
   * @return $this
   */
  public function setSettingValue(ParagraphSettingInterface $name, $value): self;

}
