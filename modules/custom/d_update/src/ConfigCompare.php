<?php

namespace Drupal\d_update;

/**
 * Config Compare service.
 *
 * @package Drupal\d_update
 */
class ConfigCompare {

  /**
   * Generates hash for the specified config.
   *
   * @param string $config_name
   *   Full name of the config, eg node.type.content_page.
   *
   * @return bool|string
   *   Returns hash or false if there is no config with provided name.
   */
  public function generateHashFromDatabase($config_name) {
    $config = \Drupal::config($config_name)->getRawData();
    if (empty($config)) {
      return FALSE;
    }
    unset($config['uuid']);
    unset($config['lang']);
    unset($config['langcode']);
    unset($config['icon_default']);
    $config_string = serialize($config);

    return md5($config_string);
  }

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
  public function compare($config_name, $hash = NULL) {
    return $this->generateHashFromDatabase($config_name) == $hash;
  }

}
