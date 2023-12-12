<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing full width paragraph setting.
 */
interface FullWidthInterface {

  /**
   * Check if carousel is full width.
   *
   * @return bool
   *   TRUE if full width, FALSE otherwise.
   */
  public function isFullWidth(): bool;

}
