<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing image width settings.
 */
interface ImageWidthInterface {

  /**
   * Gets image width option.
   *
   * @return string|null
   *   Image width option value, defaults to NULL.
   */
  public function getImageWidth(): ?string;

}
