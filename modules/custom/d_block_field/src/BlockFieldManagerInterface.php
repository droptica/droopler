<?php

declare(strict_types = 1);

namespace Drupal\d_block_field;

/**
 * Provides an interface defining a BLock field manager.
 */
interface BlockFieldManagerInterface {

  /**
   * Get sorted listed of supported block definitions.
   *
   * @return array
   *   An associative array of supported block definitions.
   */
  public function getBlockDefinitions();

}
