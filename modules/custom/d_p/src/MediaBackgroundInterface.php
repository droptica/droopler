<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Trait for manage media background field.
 */
interface MediaBackgroundInterface {

  /**
   * Checks if entity has media background.
   *
   * @return bool
   *   TRUE if entity has media background and it's not empty, FALSE otherwise.
   */
  public function hasMediaBackground(): bool;

}
