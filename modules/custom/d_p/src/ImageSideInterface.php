<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Trait for managing image side settings.
 */
interface ImageSideInterface {

  /**
   * Gets image side option.
   *
   * @return string|null
   *   Image side option value, defaults to NULL.
   */
  public function getImageSide(): ?string;

}
