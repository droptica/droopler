<?php

declare(strict_types=1);

namespace Drupal\d_p\Validation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\d_p\Helper\NestedArrayHelper;
use Drupal\geysir\Form\GeysirModalParagraphForm;

/**
 * Provides paragraph settings validators.
 *
 * Methods are static because Drupal Form API stores them as `#element_validate`
 * callbacks and invokes them without resolving a service.
 */
class ParagraphSettingsValidation {

  /**
   * Validate the column-count setting for a paragraph.
   */
  public static function validateColumnCount(array $element, FormStateInterface $form_state): void {
    $column_count_value = $form_state->getValue($element['#parents']);
    $form_object = $form_state->getFormObject();

    $paragraph_bundle = $form_object instanceof GeysirModalParagraphForm
      ? $form_object->getEntity()->bundle()
      : self::getParentParagraphBundleId($element, $form_state);

    if (!is_string($paragraph_bundle)) {
      return;
    }

    $validation_rules = $element['#plugin']->getValidationRulesDefinition()['column_count'] ?? NULL;
    if (empty($validation_rules)) {
      return;
    }

    $valid_number_of_columns = $validation_rules['bundle_allowed_values'][$paragraph_bundle]
      ?? $validation_rules['allowed_values'];
    if (in_array($column_count_value, $valid_number_of_columns, FALSE)) {
      return;
    }

    $form_state->setError(
      $element,
      new TranslatableMarkup('The allowed number of columns for @field is @column_number', [
        '@column_number' => implode(', ', $valid_number_of_columns),
        '@field' => $element['#title'],
      ]),
    );
  }

  /**
   * Resolve the parent paragraph bundle id from a nested form element.
   */
  protected static function getParentParagraphBundleId(array $element, FormStateInterface $form_state): ?string {
    $parents_reversed = array_reverse($element['#array_parents'], TRUE);
    $paragraph_subform_position = array_search('subform', $parents_reversed, TRUE);
    if ($paragraph_subform_position === FALSE) {
      return NULL;
    }

    $form_parents = array_slice(
      $element['#array_parents'],
      0,
      count($element['#array_parents']) - $paragraph_subform_position,
    );
    $parent_paragraph_form_element = NestedArrayHelper::getParentElement(
      $form_state->getCompleteForm(),
      $form_parents,
    );

    return $parent_paragraph_form_element['#paragraph_type'] ?? NULL;
  }

}
