<?php

/**
 * @file
 * Paragraphs features API documentation.
 */

declare(strict_types = 1);

/**
 * Collects paragraph types to center its ckeditor content.
 *
 * @param array $paragraph_types
 *   Array of the paragraph types for which ckeditor content should be centered
 *   by default.
 */
function hook_d_p_centered_ckeditor_widget_paragraphs(array &$paragraph_types) {
  $paragraph_types[] = 'my_paragraph_type';
}

/**
 * Allows to modify structure of the settings form.
 *
 * The settings form is built from ParagraphSettings plugins,
 * so it might be work to check first for creating new plugin
 * class or override the existing one instead of using hook.
 * Each elements must be the valid form element.
 *
 * @param array $settings_form
 *   The settings form.
 */
function hook_d_settings_alter(array &$settings_form) {
  $settings_form['my_setting'] = [
    '#type' => 'textfield',
    '#title' => t('Custom CSS class'),
    '#default_value' => 'my-setting-class',
    '#weight' => 100,
  ];
}
