<?php

/**
 * @file
 * Theme and preprocess functions for forms.
 */

declare(strict_types = 1);

/**
 * Implements hook_preprocess_HOOK().
 */
function droopler_theme_preprocess_input__submit(&$variables) {
  if (isset($variables['element']['#button_type'])) {
    $variables['button_type'] = $variables['element']['#button_type'];
  }
}
