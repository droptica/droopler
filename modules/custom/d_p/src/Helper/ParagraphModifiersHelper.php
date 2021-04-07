<?php

namespace Drupal\d_p\Helper;

use Drupal\d_p\Plugin\Field\FieldWidget\SettingsWidget;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ParagraphModifiersHelper.
 *
 * @package Drupal\d_p\Helper
 *
 * @deprecated in droopler:8.x-2.2 and is removed from droopler:8.x-2.3.
 * As this is working on the particular field instance,
 * we have unified and moved all the methods directly to the field list class:
 * Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface
 *
 * @see https://www.drupal.org/project/droopler/issues/3180465
 */
class ParagraphModifiersHelper {

  /**
   * @var \stdClass
   * Settings object.
   */
  protected $modifiers;

  /**
   * @var string
   * Name of the field with settings.
   */
  protected $settingsFieldName;

  /**
   * ParagraphModifiersHelper constructor.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph to be analyzed.
   */
  public function __construct(Paragraph $paragraph = NULL) {
    if ($paragraph) {
      $this->analyzeParagraph($paragraph);
    }
  }

  /**
   * Analyze paragraph fields searching for field with settings.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   Paragraph to analyze.
   */
  public function analyzeParagraph(Paragraph $paragraph) {
    $fieldsDefs = $paragraph->getFieldDefinitions();
    /**
     * @var string $fieldName
     * @var \Drupal\field\Entity\FieldConfig $field
     */
    foreach ($fieldsDefs as $fieldName => $field) {
      if ($field->getType() == 'field_p_configuration_storage') {
        $settings = $paragraph->get($fieldName)->getValue();
        if (!empty($settings)) {
          $this->settingsFieldName = $fieldName;
          $this->modifiers = $settings;
        }
      }
    }
  }

  /**
   * Checks if any modifiers were found during paragraph analyze.
   *
   * @return bool
   */
  public function hasModifiers() {
    return !empty($this->modifiers);
  }

  /**
   * Checks if custom classes are set.
   *
   * @return bool
   */
  public function hasCustomClasses() {
    return $this->hasModifier(SettingsWidget::CSS_CLASS_SETTING_NAME);
  }

  /**
   * Checks if specified modifier were set.
   *
   * @param $name
   *
   * @return bool
   */
  public function hasModifier($name) {
    /*
     * TODO: In the future we'll probably have simplified structure of d_settings field - in that case only one method would be sufficient for checking if the modifier is set.
     */
    return $this->hasClass($name) || $this->checkPropertyExists($name);
  }

  /**
   * Sets a new modifier.
   *
   * @param $name
   *   Name of the new modifier to be set.
   * @param null $value
   *   Optional value for the new modifier.
   */
  public function setModifier($name, $value = NULL) {
    if (empty($value)) {
      $classes = $this->getModifier(SettingsWidget::CSS_CLASS_SETTING_NAME) ?? '';
      $classesSet = explode(' ', $classes);
      $classesSet[] = $name;

      $this->modifiers->{SettingsWidget::CSS_CLASS_SETTING_NAME} = implode(' ', $classesSet);
    } else {
      $this->modifiers->$name = $value;
    }
  }

  /**
   * Removes specified modifier.
   *
   * @param $name
   *   Name of the modifier to be removed.
   */
  public function removeModifier($name) {
    if ($this->hasClass($name)) {
      $classes = $this->getModifier(SettingsWidget::CSS_CLASS_SETTING_NAME) ?? '';
      $classesSet = explode(' ', $classes);

      unset($classesSet[array_search($name, $classesSet)]);

      $this->modifiers->{SettingsWidget::CSS_CLASS_SETTING_NAME} = implode(' ', array_unique($classesSet));
    } elseif ($this->checkPropertyExists($name)) {
      unset($this->modifiers->$name);
    }
  }

  /**
   * Replaces specified modifier with new one.
   *
   * @param $name
   *   Name of modifier to be replaced.
   * @param $newName
   *   Name of the new modifier to be set.
   * @param null $newValue
   *   Optional value set for the new modifier.
   */
  public function replaceModifier($name, $newName, $newValue = NULL) {
    if ($this->hasModifier($name)) {
      $this->removeModifier($name);
      $this->setModifier($newName, $newValue);
    }
  }

  /**
   * Checks if specified class were added to custom classes setting.
   *
   * @param $class
   *
   * @return bool
   */
  public function hasClass($class) {
    if (!$this->checkPropertyExists(SettingsWidget::CSS_CLASS_SETTING_NAME)) {
      return FALSE;
    }

    $classesSet = explode(' ', $this->getModifier(SettingsWidget::CSS_CLASS_SETTING_NAME));

    return in_array($class, $classesSet);
  }

  /**
   * Returns name of field containing settings.
   *
   * @return string
   */
  public function getSettingsFieldName() {
    return $this->settingsFieldName;
  }

  /**
   * Returns modifier if available.
   *
   * @param mixed $name
   *   Modifier name.
   * @param mixed|null $default
   *   Default value to use.
   *
   * @return mixed|null
   *   Modifier value or default if not found or empty.
   */
  public function getModifier($name, $default = NULL) {
    return $this->modifiers->$name ?? $default;
  }

  /**
   * Returns paragraph modifiers encoded in d_settings field format.
   *
   * @return false|string
   */
  public function getModifiersEncoded() {
    return json_encode($this->modifiers);
  }

  /**
   * Get custom class modifier.
   *
   * @return |null
   */
  public function getCustomClass() {
    $classes = $this->getModifier(SettingsWidget::CSS_CLASS_SETTING_NAME);

    // Add default classes if not present.
    $defaults = SettingsWidget::getModifierDefaults();
    foreach ($defaults as $modifier) {
      foreach ($modifier['options'] as $option) {
        if (strpos($classes, $option) !== FALSE) {
          continue 2;
        }
      }
      $classes .= ' ' . $modifier['default'];
    }

    return $classes;
  }

  /**
   * Method checks if currently analyzed paragraph has specified property in d_settings field.
   *
   * @param $name
   *   Name of property to be checked.
   *
   * @return bool
   *   TRUE if is set, FALSE if not.
   */
  protected function checkPropertyExists($name) {
    if (!$this->hasModifiers() ||
      !property_exists($this->modifiers, $name) ||
      empty($this->modifiers->$name) ) {
      return FALSE;
    }

    return TRUE;
  }
}
