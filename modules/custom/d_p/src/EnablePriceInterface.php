<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for enable price.
 */
interface EnablePriceInterface {

  /**
   * Check if field 'Enable price' is checked.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function hasEnabledPrice(): bool;

  /**
   * Check if field 'Show the price in the sidebar' is checked.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function hasPriceInSidebar(): bool;

}
