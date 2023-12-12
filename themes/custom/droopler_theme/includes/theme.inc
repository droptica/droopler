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
 * e.g.: {{ attach_library('droopler_theme/block') }}.
 */
function droopler_theme_library_info_build() {
  $theme_handler = \Drupal::service('theme_handler');
  $theme_path = $theme_handler->getTheme('droopler_theme')->getPath();

  $extensions = ['css', 'js'];
  $directory = $theme_path . '/build/components';
  $extensions = array_map('preg_quote', $extensions);
  $extensions = implode('|', $extensions);

  if (!is_dir($directory)) {
    return [];
  }

  $file_scan = \Drupal::service('file_system')->scanDirectory($directory, "/{$extensions}$/");
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
 * Implements hook_preprocess_HOOK() for page templates.
 */
function droopler_theme_preprocess_page(&$variables) {
  $variables['footer_primary_regions'] = [];

  foreach ($variables['page'] as $name => $data) {
    if (str_contains($name, 'footer_primary_') && !empty($data)) {
      $variables['footer_primary_regions'][] = $data;
    }
  }

  $variables['footer_primary_regions_count'] = count($variables['footer_primary_regions']);
}
