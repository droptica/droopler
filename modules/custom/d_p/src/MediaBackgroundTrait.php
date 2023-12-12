<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Trait for manage media background field.
 *
 * @see \Drupal\d_p\MediaBackgroundInterface.
 */
trait MediaBackgroundTrait {

  /**
   * {@inheritdoc}
   */
  public function hasMediaBackground(): bool {
    if (!$this->hasField('field_d_media_background')) {
      return FALSE;
    }

    $media_field = $this->get('field_d_media_background');

    if ($media_field->isEmpty()) {
      return FALSE;
    }

    return TRUE;
  }

}
