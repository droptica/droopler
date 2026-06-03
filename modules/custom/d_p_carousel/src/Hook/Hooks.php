<?php

declare(strict_types=1);

namespace Drupal\d_p_carousel\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;

/**
 * Hook implementations for the d_p_carousel module.
 */
class Hooks {

  protected const int BREAKPOINT_SM = 992;
  protected const int BREAKPOINT_XS = 540;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_carousel' => [
        'base hook' => 'paragraph',
      ],
      'paragraph__d_p_carousel_item' => [
        'base hook' => 'paragraph',
      ],
      'field__field_d_p_cs_item_reference' => [
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
    if (($variables['element']['#bundle'] ?? NULL) !== 'd_p_carousel') {
      return;
    }
    if (($variables['field_name'] ?? NULL) !== 'field_d_p_cs_item_reference') {
      return;
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['element']['#object'];

    $setting_field = ParagraphSettingsAccessor::field($paragraph);
    if ($setting_field === NULL) {
      return;
    }

    $columns = [
      'md' => ParagraphSettingTypesInterface::COLUMN_COUNT_SETTING_NAME,
      'sm' => ParagraphSettingTypesInterface::COLUMN_COUNT_TABLET_SETTING_NAME,
      'xs' => ParagraphSettingTypesInterface::COLUMN_COUNT_MOBILE_SETTING_NAME,
    ];

    $columns_values = [];
    foreach ($columns as $breakpoint => $config_name) {
      $columns_values['columns_' . $breakpoint] = $setting_field->getSettingValue($config_name);
    }

    $variables['#attached']['drupalSettings']['d_p_carousel'][$paragraph->id()] = $columns_values;
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_carousel.
   */
  #[Hook('preprocess_paragraph__d_p_carousel')]
  public function preprocessParagraphDpCarousel(array &$variables): void {
    $variables['#attached']['library'][] = 'd_p_carousel/slick';
    $variables['#attached']['library'][] = 'd_p_carousel/d_p_carousel';
    $variables['#attached']['drupalSettings']['d_p_carousel']['sm'] = self::BREAKPOINT_SM;
    $variables['#attached']['drupalSettings']['d_p_carousel']['xs'] = self::BREAKPOINT_XS;
    $variables['attributes']['data-id'][] = $variables['paragraph']->id();
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_carousel_item.
   */
  #[Hook('preprocess_paragraph__d_p_carousel_item')]
  public function preprocessParagraphDpCarouselItem(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem|null $link */
    $link = $paragraph->get('field_d_cta_link')->first();
    if ($link !== NULL) {
      $variables['has_link'] = TRUE;
      $variables['attributes']['href'][] = $link->getUrl()->toString();
    }
    else {
      $variables['has_link'] = FALSE;
    }
  }

  /**
   * Implements hook_d_p_centered_ckeditor_widget_paragraphs().
   */
  #[Hook('d_p_centered_ckeditor_widget_paragraphs')]
  public function dpCenteredCkeditorWidgetParagraphs(array &$paragraph_types): void {
    $paragraph_types[] = 'd_p_carousel';
    $paragraph_types[] = 'd_p_carousel_item';
  }

}
