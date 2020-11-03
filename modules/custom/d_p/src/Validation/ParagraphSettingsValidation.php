<?php

namespace Drupal\d_p\Validation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\d_p\Helper\NestedArrayHelper;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Provides paragraph settings validator.
 *
 * @package Drupal\d_p\Validation
 */
class ParagraphSettingsValidation {

  /**
   * Validate field column count.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateColumnCount(array $element, FormStateInterface $form_state) {
    $column_count_value = $form_state->getValue(array_merge($element['#parents']));
    $paragraph_bundle = self::getParentParagraphBundleId($element, $form_state);

    if (is_null($paragraph_bundle)) {
      return;
    }

    $validation_rules = $element['#d_settings']['validation']['column_count'] ?? [];

    $valid_number_of_columns = $validation_rules['bundle_allowed_values'][$paragraph_bundle] ?: $validation_rules['allowed_values'];
    if (!in_array($column_count_value, $valid_number_of_columns)) {
      $form_state->setError(
        $element,
        new TranslatableMarkup('The allowed number of columns for @field is @column_number', [
          '@column_number' => implode(', ', $valid_number_of_columns),
          '@field' => $element['#title'],
        ])
      );
    }

  }

  /**
   * Get paragraph bundle id from form element parent.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return string|null
   *   Paragraph bundle id, defaults to null.
   */
  protected static function getParentParagraphBundleId(array $element, FormStateInterface $form_state): ?string {
    $settings_field_name = ParagraphSettingTypesInterface::SETTINGS_FIELD_NAME;
    $parent_paragraph_position = array_search($settings_field_name, $element['#array_parents']);
    $form_parents = array_slice($element['#array_parents'], 0, $parent_paragraph_position - 1);

    $parent_paragraph_form_element = NestedArrayHelper::getParentElement($form_state->getCompleteForm(), $form_parents);

    return $parent_paragraph_form_element['#paragraph_type'] ?? NULL;
  }

}