<?php

declare(strict_types=1);

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * Plugin implementation for the d_document_embed formatter.
 *
 * @FieldFormatter(
 *   id = "d_document_embed",
 *   label = @Translation("Document embed"),
 *   field_types = {
 *     "file",
 *   }
 * )
 */
class DocumentEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {
      $entity = $item->getEntity();
      if (!$entity instanceof MediaInterface) {
        continue;
      }
      $view = $item->view();
      $file = $view['#file'] ?? NULL;
      if (!$file instanceof FileInterface) {
        continue;
      }

      $elements[$delta] = [
        '#theme' => 'd_media_document_embed',
        '#link' => $file->createFileUrl(FALSE),
        '#name' => $entity->getName(),
      ];
    }
    return $elements;
  }

}
