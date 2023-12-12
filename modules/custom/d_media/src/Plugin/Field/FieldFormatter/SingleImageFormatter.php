<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'Single Image' formatter.
 *
 * @FieldFormatter(
 *   id = "d_media_single_image",
 *   label = @Translation("Single Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SingleImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function getEntitiesToView(FieldItemListInterface $items, $langcode) {
    $entities = parent::getEntitiesToView($items, $langcode);

    return $entities ? reset($entities) : $entities;
  }

}
