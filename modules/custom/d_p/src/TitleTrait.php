<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Trait for managing title field.
 *
 * @see \Drupal\d_p\TitleInterface.
 */
trait TitleTrait {

  /**
   * {@inheritdoc}
   */
  public function getTitle(): ?string {
    if (!$this->hasField('field_d_main_title')) {
      return NULL;
    }

    return $this->get('field_d_main_title')->getString();
  }

}
