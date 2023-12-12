<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for text alignment.
 */
interface TextAlignInterface {

  /**
   * Get text alignment.
   *
   * @return string
   *   Text alignment.
   */
  public function getTextAlign(): string;

}
