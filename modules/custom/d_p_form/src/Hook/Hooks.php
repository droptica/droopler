<?php

declare(strict_types=1);

namespace Drupal\d_p_form\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\d_p\Helper\ParagraphSettingsAccessor;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Hook implementations for the d_p_form module.
 */
class Hooks {

  /**
   * Implements hook_options_list_alter().
   */
  #[Hook('options_list_alter')]
  public function optionsListAlter(array &$options, array $context): void {
    // The "personal" contact form makes an error.
    $field_definition = $context['fieldDefinition'] ?? NULL;
    if ($field_definition instanceof FieldConfigInterface
      && $field_definition->id() === 'paragraph.d_p_form.field_d_forms'
    ) {
      unset($options['personal']);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_form.
   */
  #[Hook('preprocess_paragraph__d_p_form')]
  public function preprocessParagraphDpForm(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    $variables['d_p_form_placement'] = ParagraphSettingsAccessor::value(
      $paragraph,
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_FORM_LAYOUT,
    );
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_form' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
