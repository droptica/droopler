<?php

/**
 * @file
 * Custom theme hooks.
 */

declare(strict_types = 1);

/**
 * Implements hook_library_info_build().
 *
 * Automatically creates components libraries (Not visible in theme's
 * libraries.yml file). You can directly use the component name as a library,
 * e.g.: {{ attach_library('STARTERKIT/block') }}.
 */
function STARTERKIT_library_info_build() {
  $theme_handler = \Drupal::service('theme_handler');
  $theme_path = $theme_handler->getTheme('STARTERKIT')->getPath();

  $directory = $theme_path . '/build/components';
  if (!is_dir($directory)) {
    return [];
  }

  $extensions = ['css', 'js'];
  $extensions = array_map('preg_quote', $extensions);
  $extensions = implode('|', $extensions);

  $file_scan = \Drupal::service('file_system')->scanDirectory($directory, "/{$extensions}$/", ['nomask' => '/extends/']);
  $libraries = [];
  foreach ($file_scan as $file) {
    $parts = explode('.', $file->filename);
    $extension = end($parts);
    switch ($extension) {
      case 'css':
        $libraries[$file->name][$extension] = [
          'component' => [
            '/' . $file->uri => [],
          ],
        ];
        break;

      case 'js':
        $libraries[$file->name][$extension] = [
          '/' . $file->uri => [],
        ];
        break;
    }
  }

  return $libraries;
}

/**
 * Implements hook_library_info_alter().
 *
 * Automatically extends existing components libraries if the file with the
 * same name as the library exists in the build/components/extends directory.
 */
function STARTERKIT_library_info_alter(&$libraries, $extension) {
  $theme_handler = \Drupal::service('theme_handler');
  $theme_path = $theme_handler->getTheme('STARTERKIT')->getPath();

  $directory = $theme_path . '/build/components/extends';
  if (!is_dir($directory)) {
    return [];
  }

  $extensions = ['css', 'js'];
  $extensions = array_map('preg_quote', $extensions);
  $extensions = implode('|', $extensions);

  $file_scan = \Drupal::service('file_system')->scanDirectory($directory, "/{$extensions}$/");
  foreach ($file_scan as $file) {
    $file_name = $file->name;
    $file_parts = explode('.', $file->filename);
    $extension = end($file_parts);

    if (isset($libraries[$file_name])) {
      switch ($extension) {
        case 'css':
          $libraries[$file_name][$extension]['component']['/' . $file->uri] = [];
          break;

        case 'js':
          $libraries[$file_name][$extension]['/' . $file->uri] = [];
          break;
      }
    }
  }
}
