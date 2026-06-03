<?php

declare(strict_types=1);

namespace Drupal\d_lang_dropdown\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_lang_dropdown module.
 */
class Hooks {

  /**
   * Implements hook_block_view_alter().
   */
  #[Hook('block_view_alter')]
  public function blockViewAlter(array &$build, BlockPluginInterface $block): void {
    if (isset($build['#base_plugin_id']) && $build['#base_plugin_id'] === 'language_block') {
      $build['#attached']['library'][] = 'd_lang_dropdown/d_lang_dropdown';
    }
  }

}
