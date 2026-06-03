<?php

declare(strict_types=1);

namespace Drupal\d_p_node\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_p_node module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'node__blog_post__d_small_box' => [
        'base hook' => 'node',
      ],
      'node__content_page__d_small_box' => [
        'base hook' => 'node',
      ],
      'node__d_product__d_small_box' => [
        'base hook' => 'node',
      ],
      'paragraph__d_p_node__default' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
