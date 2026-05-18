<?php

declare(strict_types=1);

namespace Drupal\d_p_side_tiles\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\d_p\Service\ParentParagraphService;

/**
 * Hook implementations for the d_p_side_tiles module.
 */
class Hooks {

  public function __construct(
    protected readonly ParentParagraphService $parentParagraphService,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_side_tiles.
   */
  #[Hook('preprocess_paragraph__d_p_side_tiles')]
  public function preprocessParagraphDpSideTiles(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    $variables['tiles_side'] = ParagraphSettingsAccessor::value(
      $paragraph,
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_SIDE_TILES_LAYOUT,
    );

    $variables['#attached']['library'][] = 'd_p_tiles/masonry';
    $variables['#attached']['library'][] = 'd_p_tiles/d_p_tiles';

    $variables['tiles_wrapper_attributes'] = new Attribute();
    $variables['images_side_attributes'] = new Attribute();
    $variables['content_side_attributes'] = new Attribute();
    $variables['content_fields_attributes'] = new Attribute();
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_side_tiles' => [
        'base hook' => 'paragraph',
      ],
      'field__paragraph__field_d_media_image__d_p_side_tiles' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_colorbox_formatter().
   */
  #[Hook('preprocess_colorbox_formatter')]
  public function preprocessColorboxFormatter(array &$variables): void {
    $variables['attributes']['class'][] = 'd-tiles-item';

    $field_definition = $variables['item']->getFieldDefinition();
    $paragraph_field_id = $this->parentParagraphService->getParentParagraphFieldId($variables['entity']);

    if ($field_definition->id() === 'paragraph.d_p_side_tiles.field_d_media_image'
      || ($paragraph_field_id && $paragraph_field_id === 'paragraph.d_p_tiles.field_d_media_image')
    ) {
      $variables['attributes']['data-cbox-title'] = $variables['attributes']['title'];
      $variables['attributes']['title'] = '';
    }
  }

}
