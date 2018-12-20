<?php

namespace Drupal\features;

/**
 * Wraps FeaturesInstallStorage to support multiple configuration
 * directories.
 */
class FeaturesExtensionStoragesByDirectory extends FeaturesExtensionStorages implements FeaturesExtensionStoragesByDirectoryInterface {

  /**
   * {@inheritdoc}
   */
  public function listAllByDirectory($prefix = '') {
    if (!isset($this->configurationLists[$prefix])) {
      $this->configurationLists[$prefix] = [];
      foreach ($this->extensionStorages as $directory => $extension_storage) {
        $this->configurationLists[$prefix] += array_fill_keys($extension_storage->listAll($prefix), $directory);
      }
    }
    return $this->configurationLists[$prefix];
  }

}
