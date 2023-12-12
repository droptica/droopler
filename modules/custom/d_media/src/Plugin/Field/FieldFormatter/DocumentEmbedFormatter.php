<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation for the d_document_embed formatter.
 *
 * @FieldFormatter(
 *   id = "d_document_embed",
 *   label = @Translation("Document embed"),
 * field_types = {
 *   "file",
 *   }
 * )
 */
class DocumentEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\media\Entity\Media $entity */
      $entity = $item->getEntity();
      $name = $entity->getName();
      /** @var \Drupal\file\Entity\File $file */
      $file = $item->view()['#file'];
      $fileUrl = $file->createFileUrl(FALSE);
      $elements[$delta] = [
        '#theme' => 'd_media_document_embed',
        '#link' => $fileUrl,
        '#name' => $name,
      ];
    }

    return $elements;
  }

}
