<?php

declare(strict_types=1);

namespace Drupal\d_social_media\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_social_media module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   *
   * @todo Replace this theme with a RenderElement class.
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'd_social_media' => [
        'variables' => [
          'links' => [],
        ],
      ],
    ];
  }

}
