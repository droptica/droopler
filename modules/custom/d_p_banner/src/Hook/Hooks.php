<?php

declare(strict_types=1);

namespace Drupal\d_p_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_p_banner module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_banner' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
