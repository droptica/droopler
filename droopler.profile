<?php

/**
 * Implements hook_system_info_alter().
 */
function droopler_system_info_alter(array &$info, \Drupal\Core\Extension\Extension $file, $type) {
  $current_uri = \Drupal::request()->getRequestUri();
  if (strpos($current_uri, 'install.php') == FALSE && !empty($info['version']) && $info['version'] == 'DROOPLER_VERSION') {
    $drooplerInfo = \Drupal::service('extension.list.profile')->getExtensionInfo('droopler');
    $info['version'] = $drooplerInfo['version'] ?? '';
  }
}
