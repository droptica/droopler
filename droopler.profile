<?php

/**
 * @file
 * The main profile file.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\Extension;

/**
 * Implements hook_system_info_alter().
 */
function droopler_system_info_alter(array &$info, Extension $file, $type) {
  $current_uri = \Drupal::request()->getRequestUri();
  if (strpos($current_uri, 'install.php') == FALSE && !empty($info['version']) && $info['version'] == 'DROOPLER_VERSION') {
    $drooplerInfo = \Drupal::service('extension.list.profile')->getExtensionInfo('droopler');
    $info['version'] = $drooplerInfo['version'] ?? '';
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function droopler_form_install_configure_form_alter(array &$form, FormStateInterface $form_state) {
  $form['update_notifications']['enable_update_status_module']['#description'] = t('By enabling the update notifications you are encouraging Droopler authors to further development of the distribution.');
}
