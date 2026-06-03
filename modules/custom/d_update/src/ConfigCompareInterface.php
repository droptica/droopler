<?php

declare(strict_types=1);

namespace Drupal\d_update;

/**
 * Provides an interface for configuration fingerprinting and comparison.
 *
 * The fingerprint is a hash of the current active configuration with volatile
 * keys (`uuid`, `lang`, `langcode`, `icon_default`) stripped. It lets update
 * hooks detect whether a configuration has been customised by the site
 * operator before importing a new revision.
 */
interface ConfigCompareInterface {

  /**
   * Generate a fingerprint hash for the given configuration name.
   *
   * @param string $config_name
   *   Full config name, e.g. `node.type.content_page`.
   *
   * @return string|false
   *   Hash of the active configuration, or FALSE when no config exists.
   */
  public function generateHashFromDatabase(string $config_name): string|false;

  /**
   * Check whether a configuration exists in the active store.
   */
  public function configExists(string $config_name): bool;

  /**
   * Compare a configuration's fingerprint with a previously-generated hash.
   *
   * @param string $config_name
   *   Full config name, e.g. `node.type.content_page`.
   * @param string|null $hash
   *   Expected hash. NULL or empty string matches anything.
   *
   * @return bool
   *   TRUE when the hashes match (or no expected hash was provided),
   *   FALSE otherwise.
   */
  public function compare(string $config_name, ?string $hash = NULL): bool;

}
