<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;

/**
 * Plugin implementation of the 'Single media' formatter.
 *
 * @FieldFormatter(
 *   id = "d_media_single_media",
 *   label = @Translation("Single media"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SingleMediaFormatter extends MediaThumbnailFormatter {

  /**
   * {@inheritdoc}
   */
  public function getEntitiesToView(FieldItemListInterface $items, $langcode) {
    $entities = parent::getEntitiesToView($items, $langcode);
    $entity = reset($entities);

    return $entity ? [$entity] : [];
  }

}
