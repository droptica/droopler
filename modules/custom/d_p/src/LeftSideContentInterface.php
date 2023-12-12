<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for left side content.
 */
interface LeftSideContentInterface {

  /**
   * Check if field 'left side content' is checked.
   *
   * @return bool
   *   TRUE if full width, FALSE otherwise.
   */
  public function hasLeftSideContent(): bool;

}
