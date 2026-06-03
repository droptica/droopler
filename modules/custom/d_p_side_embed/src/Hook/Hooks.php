<?php

declare(strict_types=1);

namespace Drupal\d_p_side_embed\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Hook implementations for the d_p_side_embed module.
 */
class Hooks {

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_side_embed.
   */
  #[Hook('preprocess_paragraph__d_p_side_embed')]
  public function preprocessParagraphDpSideEmbed(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    $variables['embed_side'] = ParagraphSettingsAccessor::value(
      $paragraph,
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_EMBED_LAYOUT,
    );

    $field_embed = $paragraph->get('field_d_embed')->get(0);
    if ($field_embed !== NULL) {
      $embed = $field_embed->getValue();
      $variables['embed'] = $embed['value'];
    }

    $variables['embed_side_attributes'] = new Attribute();
    $variables['content_side_attributes'] = new Attribute();
    $variables['content_fields_attributes'] = new Attribute();
    $variables['d_p_side_embed_wrapper_attributes'] = new Attribute();

    $variables['#attached']['library'][] = 'd_p_side_embed/d_p_side_embed';
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_side_embed' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
