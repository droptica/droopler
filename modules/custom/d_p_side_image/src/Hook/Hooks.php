<?php

declare(strict_types=1);

namespace Drupal\d_p_side_image\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Hook implementations for the d_p_side_image module.
 */
class Hooks {

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_side_image.
   */
  #[Hook('preprocess_paragraph__d_p_side_image')]
  public function preprocessParagraphDpSideImage(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    $image_side = (string) ParagraphSettingsAccessor::value(
      $paragraph,
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT,
      '',
    );
    if ($image_side === '') {
      return;
    }

    $variables['d_p_side_image_attributes'] = new Attribute([
      'class' => ['image-side-' . $image_side],
    ]);

    [$image_class, $text_class] = match ($image_side) {
      'left-wide', 'right-wide' => ['col-md-6 col-lg-7', 'col-md-6 col-lg-5'],
      default => ['col-md-6', 'col-md-6'],
    };

    $variables['image_class'] = $image_class;
    $variables['text_class'] = $text_class;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_side_image' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
