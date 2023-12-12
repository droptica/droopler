<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing column settings.
 */
interface ColumnCountInterface {

  /**
   * Gets column settings.
   *
   * @return array
   *   Array with column settings.
   */
  public function getColumnSettings(): array;

  /**
   * Gets mobile breakpoint size in pixels.
   *
   * @return int
   *   Mobile breakpoint size in pixels.
   */
  public function getMobileBreakpointSize(): int;

  /**
   * Gets tablet breakpoint size in pixels.
   *
   * @return int
   *   Tablet breakpoint size in pixels.
   */
  public function getTabletBreakpointSize(): int;

}
