<?php

declare(strict_types=1);

namespace Drupal\d_p_tiles\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Hook implementations for the d_p_tiles module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_tiles' => [
        'base hook' => 'paragraph',
      ],
      'field__paragraph__field_d_media_image__d_p_tiles' => [
        'base hook' => 'field',
      ],
      'media__d_image__tiles_gallery_fullscreen_featured' => [
        'base hook' => 'media',
      ],
      'media__d_image__d_tiles_gallery_fullscreen' => [
        'base hook' => 'media',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_tiles.
   */
  #[Hook('preprocess_paragraph__d_p_tiles')]
  public function preprocessParagraphDpTiles(array &$variables): void {
    $variables['#attached']['library'][] = 'd_p_tiles/masonry';
    $variables['#attached']['library'][] = 'd_p_tiles/d_p_tiles';
  }

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if (($variables['element']['#entity_type'] ?? NULL) !== 'paragraph') {
      return;
    }
    if (($variables['element']['#bundle'] ?? NULL) !== 'd_p_tiles') {
      return;
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['element']['#object'];

    match ($variables['field_name'] ?? '') {
      'field_d_main_title', 'field_d_long_text' => $variables['attributes']['class'][] = 'container',
      'field_d_media_image' => $this->markFeaturedImages($variables, $paragraph),
      default => NULL,
    };
  }

  /**
   * Implements hook_d_p_centered_ckeditor_widget_paragraphs().
   */
  #[Hook('d_p_centered_ckeditor_widget_paragraphs')]
  public function dpCenteredCkeditorWidgetParagraphs(array &$paragraph_types): void {
    $paragraph_types[] = 'd_p_tiles';
  }

  /**
   * Switch selected gallery images to the featured view mode.
   */
  protected function markFeaturedImages(array &$variables, ParagraphInterface $paragraph): void {
    $variables['wrapper_attributes'] = new Attribute();

    $featured_images_setting = (string) ParagraphSettingsAccessor::value(
      $paragraph,
      ParagraphSettingTypesInterface::PARAGRAPH_FEATURED_IMAGES,
      '',
    );
    if ($featured_images_setting === '') {
      return;
    }

    foreach (array_map('intval', explode(',', $featured_images_setting)) as $image_number) {
      if ($image_number > 0 && !empty($variables['items'][$image_number - 1])) {
        $variables['items'][$image_number - 1]['content']['#view_mode'] = 'tiles_gallery_fullscreen_featured';
      }
    }
  }

}
