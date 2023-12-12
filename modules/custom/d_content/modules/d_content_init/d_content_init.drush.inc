<?php

/**
 * @file
 * Contains the code to generate the custom drush commands.
 */

declare(strict_types = 1);

/**
 * Implements hook_drush_command().
 */
function d_content_init_drush_command() {
  $items = [];
  $items['recreate-content'] = [
    'description' => 'Removes all the content and recreates it again.',
  ];
  return $items;
}

/**
 * Callback for 'recreate-content' drush command.
 */
function drush_d_content_init_recreate_content() {
  d_content_init_recreate_content();
}
