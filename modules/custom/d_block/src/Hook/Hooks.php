<?php

declare(strict_types=1);

namespace Drupal\d_block\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;

/**
 * Hook implementations for the d_block module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_block' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_block.
   */
  #[Hook('preprocess_paragraph__d_p_block')]
  public function preprocessParagraphDpBlock(array &$variables): void {
    $variables['content_attributes'] = new Attribute([
      'class' => ['content'],
    ]);

    $setting_field = ParagraphSettingsAccessor::field($variables['paragraph']);
    if ($setting_field === NULL || !$setting_field->hasClass('full-width')) {
      $variables['content_attributes']->addClass('container');
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for the d_p_block field block.
   */
  #[Hook('preprocess_field__paragraph__field_block__d_p_block')]
  public function preprocessFieldParagraphFieldBlockDpBlock(array &$variables): void {
    foreach ($variables['items'] as &$item) {
      $item['content']['#title_attributes'] = [
        'class' => [
          'container',
          'text-center',
        ],
      ];
    }
  }

  /**
   * Implements hook_preprocess_block().
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    if (isset($variables['elements']['#title_attributes'])) {
      $variables['title_attributes'] = NestedArray::mergeDeep(
        $variables['title_attributes'],
        $variables['elements']['#title_attributes']
      );
    }
  }

}
