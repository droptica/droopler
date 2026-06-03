<?php

declare(strict_types=1);

namespace Drupal\d_mautic\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_mautic module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'd_mautic_form_embed' => [
        'variables' => [
          'formUrl' => NULL,
        ],
      ],
      'paragraph__d_mautic' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
