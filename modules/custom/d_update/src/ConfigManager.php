<?php

/**
 * @file
 * Contains \Drupal\d_update\ConfigManager.
 */

namespace Drupal\d_update;

/**
 * Config Manager service.
 *
 * @package Drupal\d_update
 */
class ConfigManager {

  /**
   * Generates hash for the specified config.
   *
   * @param string $configName
   *   Full name of the config, eg node.type.content_page.
   *
   * @return bool|string
   *   Returns hash or false if there is no config with provided name.
   */
  public function generateHashFromDatabase($configName) {
    $config = \Drupal::config($configName)->getRawData();
    if (empty($config)) {
      return FALSE;
    }

    unset($config['uuid']);
    unset($config['lang']);
    unset($config['langcode']);
    $configString = serialize($config);

    return md5($configString);
  }

  /**
   * Compares config name hash wit provided hash.
   *
   * @param string $configName
   *   Full name of the config, eg node.type.content_page.
   * @param string $hash
   *   Optional argument with hash.
   *
   * @return bool
   *   Returns true if hashes are the same or hash was not provided, false on different hashes.
   */
  public function compare($configName, $hash = NULL) {
    if (empty($hash)) {
      return TRUE;
    }
    else {
      return $this->generateHashFromDatabase($configName) == $hash;
    }
  }

}
