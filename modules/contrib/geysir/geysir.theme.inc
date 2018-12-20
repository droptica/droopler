<?php

/**
 * @file
 * Geysir theming functionalities.
 */

use Drupal\Component\Utility\Html;

/**
 * Prepares variables for Geysir Field Paragraph wrapper templates.
 *
 * Default template: geysir-field-paragraph-wrapper.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: .
 */
function template_preprocess_geysir_field_paragraph_wrapper(array &$vars) {
  $element = $vars['element'];
  $paragraph = $element['#paragraph'];

  $vars['attributes']['data-geysir-paragraph-id'] = ($paragraph)? $paragraph->id() : 0;
  $vars['attributes']['class'][] = Html::cleanCssIdentifier('geysir-field-paragraph-wrapper');

  $vars['content'] = $element['#children'];
  $vars['links'] = $element['#geysir_field_paragraph_links'];
}
