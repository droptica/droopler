<?php

namespace Drupal\features;

/**
 * Defines an extended interface for extension storages.
 */
interface FeaturesExtensionStoragesByDirectoryInterface extends FeaturesExtensionStoragesInterface {

  /**
   * Returns a list of all configuration available from extensions.
   *
   * This method was made public late in the 8.x-3.x cycle and so is not
   * included in the interface.
   *
   * @param string $prefix
   *   (optional) The prefix to search for. If omitted, all configuration object
   *   names that exist are returned.
   *
   * @return array
   *   An array with configuration item names as keys and configuration
   *   directories as values.
   */
  public function listAllByDirectory($prefix = '');

}
