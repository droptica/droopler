<?php

declare(strict_types=1);

namespace Drupal\d_p_side_by_side\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\field\Entity\FieldConfig;

/**
 * Hook implementations for the d_p_side_by_side module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_side_by_side' => [
        'base hook' => 'paragraph',
      ],
      'field__field_d_p_sbs_items' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type, string $bundle): void {
    if ($entity_type->id() !== 'paragraph') {
      return;
    }
    $field = $fields['field_d_p_sbs_items'] ?? NULL;
    if (!$field instanceof FieldConfig) {
      return;
    }

    $field->addConstraint('AllItemsRequired', [
      'number' => $field->getFieldStorageDefinition()->getCardinality(),
      'name' => $field->label(),
    ]);
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_side_by_side.
   *
   * Checks if side by side contains a non-empty header.
   */
  #[Hook('preprocess_paragraph__d_p_side_by_side')]
  public function preprocessParagraphDpSideBySide(array &$variables): void {
    $variables['has_header'] = isset($variables['content']['field_d_long_text'][0])
      || isset($variables['content']['field_d_main_title'][0]);
  }

}
