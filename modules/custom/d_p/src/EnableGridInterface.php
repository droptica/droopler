<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for Enable grid.
 */
interface EnableGridInterface {

  /**
   * Check if field 'Enable grid' is checked.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function hasEnabledGrid(): bool;

}
