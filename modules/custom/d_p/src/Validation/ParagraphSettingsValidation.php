<?php

declare(strict_types = 1);

namespace Drupal\d_p\Validation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\d_p\Helper\NestedArrayHelper;
use Drupal\paragraphs_edit\Form\ParagraphEditForm;

/**
 * Provides paragraph settings validator.
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
    $column_count_value = $form_state->getValue($element['#parents']);
    $paragraph_bundle = self::getParentParagraphBundleId($element, $form_state);

    if (!is_string($paragraph_bundle)) {
      return;
    }

    $validation_rules = $element['#plugin']->getValidationRulesDefinition()['column_count'] ?? NULL;

    if (empty($validation_rules)) {
      return;
    }

    $valid_number_of_columns = $validation_rules['bundle_allowed_values'][$paragraph_bundle] ?? $validation_rules['allowed_values'];
    if (!in_array($column_count_value, $valid_number_of_columns)) {
      $form_state->setError(
        $element,
        (new TranslatableMarkup('The allowed number of columns for @field is @column_number', [
          '@column_number' => implode(', ', $valid_number_of_columns),
          '@field' => $element['#title'],
        ]))->render()
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
    $parents_reversed = array_reverse($element['#array_parents'], TRUE);

    if ($paragraph_subform_position = array_search('subform', $parents_reversed)) {
      $form_parents = array_slice($element['#array_parents'], 0, $paragraph_subform_position);
      $parent_paragraph_form_element = NestedArrayHelper::getParentElement($form_state->getCompleteForm(), $form_parents);

      return $parent_paragraph_form_element['#paragraph_type'] ?? NULL;
    }

    // The above method does not work for the frontend editing module, so we
    // try to get paragraph type id from the callback object.
    $build_info = $form_state->getBuildInfo();
    if (isset($build_info['callback_object']) && $build_info['callback_object'] instanceof ParagraphEditForm) {
      return $build_info['callback_object']->getEntity()->bundle();
    }

    return NULL;
  }

}
