<?php

declare(strict_types=1);

namespace Drupal\d_media\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_media module.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'd_media_video_embed' => [
        'variables' => [
          'attributes' => NULL,
          'spacer_attributes' => NULL,
        ],
      ],
      'd_media_document_embed' => [
        'variables' => [
          'link' => NULL,
          'name' => NULL,
        ],
      ],
      'd_media_canvas_image' => [
        'variables' => [
          'canvas_attributes' => NULL,
          'image' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter() for editor_image_dialog.
   *
   * Whitelists SVG uploads in the CKEditor image dialog by extending the
   * file_validate_extensions list to include `svg`.
   */
  #[Hook('form_editor_image_dialog_alter')]
  public function formEditorImageDialogAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form['fid']['#upload_validators']['file_validate_extensions'] = ['gif png jpg jpeg svg'];
  }

}
