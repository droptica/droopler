<?php

declare(strict_types=1);

namespace Drupal\d_p_counters\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Hook implementations for the d_p_counters module.
 */
class Hooks {

  protected const int GRID_SIZE = 12;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_group_of_counters' => [
        'base hook' => 'paragraph',
      ],
      'paragraph__d_p_single_counter' => [
        'base hook' => 'paragraph',
      ],
      'field__paragraph__field_d_counter_reference__d_p_group_of_counters' => [
        'base hook' => 'field',
      ],
      'field__paragraph__field_d_number__d_p_single_counter' => [
        'base hook' => 'field',
      ],
      'field__paragraph__field_d_main_title__d_p_single_counter' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_group_of_counters.
   */
  #[Hook('preprocess_paragraph__d_p_group_of_counters')]
  public function preprocessParagraphDpGroupOfCounters(array &$variables): void {
    $variables['#attached']['library'][] = 'd_p_counters/d_p_counters';
  }

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if (($variables['element']['#entity_type'] ?? NULL) !== 'paragraph') {
      return;
    }
    if (($variables['element']['#bundle'] ?? NULL) !== 'd_p_group_of_counters') {
      return;
    }
    if (($variables['field_name'] ?? NULL) !== 'field_d_counter_reference') {
      return;
    }

    $paragraph = $variables['element']['#object'];
    $settings_field = ParagraphSettingsAccessor::field($paragraph);

    if ($settings_field !== NULL) {
      $columns = [
        'col-md-' => (int) $settings_field->getSettingValue(ParagraphSettingTypesInterface::COLUMN_COUNT_SETTING_NAME),
        'col-sm-' => (int) $settings_field->getSettingValue(ParagraphSettingTypesInterface::COLUMN_COUNT_TABLET_SETTING_NAME),
        'col-'    => (int) $settings_field->getSettingValue(ParagraphSettingTypesInterface::COLUMN_COUNT_MOBILE_SETTING_NAME),
      ];
      $classes = [];
      foreach ($columns as $key => $value) {
        if ($value > 0) {
          $classes[] = $key . (self::GRID_SIZE / $value);
        }
      }
      $variables['column_class'] = implode(' ', $classes);
    }

    foreach ($variables['items'] as &$item) {
      $item_setting_field = ParagraphSettingsAccessor::field($item['content']['#paragraph']);
      if ($item_setting_field !== NULL) {
        $item['attributes']->addClass($item_setting_field->getClasses());
      }
    }
  }

  /**
   * Implements hook_d_p_centered_ckeditor_widget_paragraphs().
   */
  #[Hook('d_p_centered_ckeditor_widget_paragraphs')]
  public function dpCenteredCkeditorWidgetParagraphs(array &$paragraph_types): void {
    $paragraph_types[] = 'd_p_group_of_counters';
  }

}
