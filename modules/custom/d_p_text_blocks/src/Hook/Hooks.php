<?php

declare(strict_types=1);

namespace Drupal\d_p_text_blocks\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Hook implementations for the d_p_text_blocks module.
 */
class Hooks {

  protected const int GRID_SIZE = 12;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_group_of_text_blocks' => [
        'base hook' => 'paragraph',
      ],
      'paragraph__d_p_single_text_block' => [
        'base hook' => 'paragraph',
      ],
      'field__field_d_p_tb_block_reference' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if (($variables['element']['#entity_type'] ?? NULL) !== 'paragraph') {
      return;
    }
    if (($variables['element']['#bundle'] ?? NULL) !== 'd_p_group_of_text_blocks') {
      return;
    }
    if (($variables['field_name'] ?? NULL) !== 'field_d_p_tb_block_reference') {
      return;
    }

    $setting_field = ParagraphSettingsAccessor::field($variables['element']['#object']);
    if ($setting_field === NULL) {
      return;
    }

    $columns = [
      'lg' => (int) $setting_field->getSettingValue(ParagraphSettingTypesInterface::COLUMN_COUNT_SETTING_NAME),
      'sm' => (int) $setting_field->getSettingValue(ParagraphSettingTypesInterface::COLUMN_COUNT_TABLET_SETTING_NAME),
      'xs' => (int) $setting_field->getSettingValue(ParagraphSettingTypesInterface::COLUMN_COUNT_MOBILE_SETTING_NAME),
    ];

    if ($setting_field->hasClass('with-grid')) {
      $this->setColumnsSizes($variables['items'], $columns);
      return;
    }

    $classes = [];
    foreach ($columns as $breakpoint => $value) {
      if ($value <= 0) {
        continue;
      }
      $breakpoint_string = $breakpoint === 'xs' ? '' : $breakpoint . '-';
      $classes[] = 'col-' . $breakpoint_string . (self::GRID_SIZE / $value);
    }
    $variables['column_class'] = implode(' ', $classes);
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_single_text_block.
   */
  #[Hook('preprocess_paragraph__d_p_single_text_block')]
  public function preprocessParagraphDpSingleTextBlock(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    $setting_field = ParagraphSettingsAccessor::field($paragraph);
    $variables['with_price'] = $setting_field !== NULL && $setting_field->hasClass('with-price');

    // If a background image is set we override the layout to full-width.
    $field_image = $paragraph->get('field_d_media_background')->first();
    if (!empty($field_image)) {
      $variables['image_class'] = $variables['text_class'] = 'col-md-12';
    }
  }

  /**
   * Implements hook_d_p_centered_ckeditor_widget_paragraphs().
   */
  #[Hook('d_p_centered_ckeditor_widget_paragraphs')]
  public function dpCenteredCkeditorWidgetParagraphs(array &$paragraph_types): void {
    $paragraph_types[] = 'd_p_group_of_text_blocks';
  }

  /**
   * Set columns sizes for defined breakpoints on a grid layout.
   *
   * @param array<int, array<string, mixed>> $items
   *   Single block text items array.
   * @param array<string, int> $column_count
   *   Number of columns per breakpoint (`lg`, `sm`, `xs`).
   */
  protected function setColumnsSizes(array $items, array $column_count): void {
    $total_items = count($items);
    foreach ($column_count as $breakpoint => $items_per_row) {
      if ($items_per_row <= 0) {
        continue;
      }
      foreach ($items as $idx => $list_item) {
        /** @var \Drupal\Core\Template\Attribute $item_attributes */
        $item_attributes = $list_item['attributes'];
        $class_prefix = 'col-' . ($breakpoint === 'xs' ? '' : $breakpoint . '-');
        if (($idx + 1) % $items_per_row === 0) {
          $item_attributes->addClass($breakpoint . '-grid-row-end-item');
        }
        $remainder = $total_items % $items_per_row;
        if ($remainder !== 0 && $idx >= $total_items - $remainder) {
          $item_attributes->addClass($class_prefix . '6');
          continue;
        }
        $item_attributes->addClass($class_prefix . self::GRID_SIZE / $items_per_row);
      }
    }
  }

}
