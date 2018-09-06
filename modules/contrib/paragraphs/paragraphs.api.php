<?php

/**
 * @file
 * Hooks and documentation related to paragraphs module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in
 * \Drupal\paragraphs\Annotation\ParagraphsBehavior.
 *
 * @param $paragraphs_behavior
 *   The array of paragraphs behavior plugins, keyed on the
 *   machine-readable plugin name.
 */
function hook_paragraphs_behavior_info_alter(&$paragraphs_behavior) {
  // Set a new label for the my_layout plugin instead of the one
  // provided in the annotation.
  $paragraphs_behavior['my_layout']['label'] = t('New label');
}

/**
 * Alter paragraphs widget.
 *
 * @param array $widget_actions
 *   Array with actions and dropdown widget actions.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - widget: The widget plugin instance.
 *   - items: The field values, as a
 *     \Drupal\Core\Field\FieldItemListInterface object.
 *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
 *   - element: A form element array containing basic properties for the widget.
 *   - form_state: The current state of the form.
 *   - paragraphs_entity: the paragraphs entity for this widget. Might be
 *     unsaved, if we have just added a new item to the widget.
 *   - is_translating: Boolean if the widget is translating.
 *   - allow_reference_changes: Boolean if changes to structure are OK.
 */
function hook_paragraphs_widget_actions_alter(array &$widget_actions, array &$context) {
}

/**
 * @} End of "addtogroup hooks".
 */
