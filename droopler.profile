<?php


/**
 * @inheritDoc
 */
function droopler_modules_installed($modules) {
  $updater = \Drupal::service('d_update');
  $module_handler = \Drupal::service('module_handler');
  $configs = [];
  if (in_array('comment', $modules)) {
    if ($module_handler->moduleExists('d_blog')) {
      $configs['d_blog'] = [
        'core.entity_view_display.node.blog_post.default' => '',
      ];
    }
    $updater->importConfigs($configs, TRUE);
  }
}
