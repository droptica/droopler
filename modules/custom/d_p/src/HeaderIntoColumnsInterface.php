<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing header into columns paragraph setting.
 */
interface HeaderIntoColumnsInterface {

  /**
   * Check if header into columns.
   *
   * @return bool
   *   TRUE if full width, FALSE otherwise.
   */
  public function isHeaderIntoColumns(): bool;

}
