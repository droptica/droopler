<?php

namespace Drupal\d_p\Helper;

use Drupal\d_p\Plugin\Field\FieldWidget\SettingsWidget;
use Drupal\paragraphs\Entity\Paragraph;

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
  public function __construct(Paragraph $paragraph) {
    $this->analyzeParagraph($paragraph);
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
          $settings = json_decode($settings[0]['value']);
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
    return $this->hasModifiers() && property_exists($this->modifiers, $name) && !empty($this->modifiers->$name);
  }

  /**
   * Checks if specified class were added to custom classes setting.
   *
   * @param $class
   *
   * @return bool
   */
  public function hasClass($class) {
    if (!$this->hasModifier(SettingsWidget::CSS_CLASS_SETTING_NAME)) {
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
   * @param $name
   *
   * @return |null
   *   Modifier or NULL if not found or empty.
   */
  public function getModifier($name) {
    return $this->hasModifier($name) ? $this->modifiers->$name : NULL;
  }

  /**
   * Get custom class modifier.
   *
   * @return |null
   */
  public function getCustomClass() {
    return $this->getModifier(SettingsWidget::CSS_CLASS_SETTING_NAME);
  }

}
