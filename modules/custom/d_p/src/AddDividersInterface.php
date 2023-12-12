<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing add-dividers settings.
 */
interface AddDividersInterface {

  /**
   * Check if items have dividers between them.
   *
   * @return bool
   *   TRUE if have dividers, FALSE otherwise.
   */
  public function hasDividers(): bool;

}
