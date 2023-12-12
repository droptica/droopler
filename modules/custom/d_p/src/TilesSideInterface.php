<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Trait for managing tiles side settings.
 */
interface TilesSideInterface {

  /**
   * Gets tiles side option.
   *
   * @return string|null
   *   Tiles side option value, defaults to NULL.
   */
  public function getTilesSide(): ?string;

}
