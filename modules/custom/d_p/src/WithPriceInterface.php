<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing with-price settings.
 */
interface WithPriceInterface {

  /**
   * Check if price should be shown.
   *
   * @return bool
   *   TRUE if yes, FALSE otherwise.
   */
  public function isWithPrice(): bool;

}
