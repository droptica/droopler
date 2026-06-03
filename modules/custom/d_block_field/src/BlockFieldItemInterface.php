<?php

declare(strict_types=1);

namespace Drupal\d_block_field;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for the block field item.
 */
interface BlockFieldItemInterface extends FieldItemInterface {

  /**
   * Resolve and return the block plugin instance for this field item.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface|null
   *   The block instance, or NULL if the plugin id is empty, the plugin
   *   is `broken`, or it's a `block_content` whose UUID no longer resolves.
   */
  public function getBlock(): ?BlockPluginInterface;

}
