<?php

declare(strict_types = 1);

namespace Drupal\d_update;

/**
 * Provides an interface for configuration comparison.
 */
interface ConfigCompareInterface {

  /**
   * Generates hash for the specified config.
   *
   * @param string $config_name
   *   Full name of the config, eg node.type.content_page.
   *
   * @return bool|string
   *   Returns hash or false if there is no config with provided name.
   */
  public function generateHashFromDatabase($config_name);

  /**
   * Check if the given config exists.
   *
   * @param string $config_name
   *   Full name of the config, eg node.type.content_page.
   *
   * @return bool
   *   True if the given config exists, false otherwise.
   */
  public function configExists($config_name);

  /**
   * Compares config name hash wit provided hash.
   *
   * @param string $config_name
   *   Full name of the config, eg node.type.content_page.
   * @param string $hash
   *   Optional argument with hash.
   *
   * @return bool
   *   Returns true if hashes are the same or hash was not provided, false on
   *   different hashes.
   */
  public function compare($config_name, $hash = NULL);

}
