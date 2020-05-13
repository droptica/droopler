<?php

/**
 * @file
 * Paragraphs features API documentation.
 */

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
