<?php
/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Droopler based themes when admin theme is not.
 *
 * @see ./includes/settings.inc
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function droopler_dorg_subtheme_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {

}


/**
 * Implements hook_library_info_alter().
 */
function droopler_dorg_subtheme_library_info_alter(&$libraries, $extension) {
  $a=2;
}
