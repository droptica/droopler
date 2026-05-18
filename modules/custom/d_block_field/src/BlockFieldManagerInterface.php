<?php

declare(strict_types=1);

namespace Drupal\d_block_field;

/**
 * Provides an interface for the Block field manager.
 */
interface BlockFieldManagerInterface {

  /**
   * Sorted, context-aware list of block plugin definitions.
   *
   * @return array<string, array<string, mixed>>
   *   Plugin definitions keyed by plugin id, sorted by core's block manager.
   */
  public function getBlockDefinitions(): array;

}
