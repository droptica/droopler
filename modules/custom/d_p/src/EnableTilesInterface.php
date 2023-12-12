<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for enable tiles.
 */
interface EnableTilesInterface {

  /**
   * Check if field 'Enable tiles' is checked.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function hasEnabledTiles(): bool;

}
