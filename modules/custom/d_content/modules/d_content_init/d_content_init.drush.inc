<?php

/**
 * @file Contains the code to generate the custom drush commands.
 */
/**
 * Implements hook_drush_command().
 */
function d_content_init_drush_command() {
  $items = array();
  $items['recreate-content'] = [
    'description' => 'Removes all the content and recreates it again.',
  ];
  $items['test-media'] = [
    'description' => 'Tests media.',
  ];
  return $items;
}

/**
 * Callback for 'recreate-content' drush command.
 */
function drush_d_content_init_recreate_content() {
  d_content_init_recreate_content();
}

/**
 * Callback for 'test-media' drush command.
 */
function drush_d_content_init_test_media() {
  /** @var \Drupal\d_content_init\ContentInitManagerMedia $mm */
  $mm = Drupal::service('d_content_init.manager_media');

  //$file = drupal_get_path('module', 'd_content_init') . '/assets/ninja.png';
  //$m = $mm->createMediaImageFromFile($file, 'ninjas', 'Ninja');
  //echo $m->id();

}
