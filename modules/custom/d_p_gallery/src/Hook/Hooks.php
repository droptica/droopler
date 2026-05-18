<?php

declare(strict_types=1);

namespace Drupal\d_p_gallery\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_p_gallery module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_gallery' => [
        'base hook' => 'paragraph',
      ],
      'field__paragraph__field_d_media_image__d_p_gallery' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_d_p_centered_ckeditor_widget_paragraphs().
   */
  #[Hook('d_p_centered_ckeditor_widget_paragraphs')]
  public function dpCenteredCkeditorWidgetParagraphs(array &$paragraph_types): void {
    $paragraph_types[] = 'd_p_gallery';
  }

}
