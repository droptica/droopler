<?php

namespace Drupal\d_update;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Config Compare service.
 *
 * @package Drupal\d_update
 */
class ConfigCompare {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ConfigCompare constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

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
    $config_storage = $this->getConfig($config_name);

    if ($config_storage->isNew()) {
      return FALSE;
    }

    $config = $config_storage->getRawData();

    unset($config['uuid']);
    unset($config['lang']);
    unset($config['langcode']);
    unset($config['icon_default']);
    $config_string = serialize($config);

    return md5($config_string);
  }

  /**
   * Check if the given config exists.
   *
   * @param string $config_name
   *   Full name of the config, eg node.type.content_page.
   *
   * @return bool
   *   True if the given config exists, false otherwise.
   */
  public function configExists($config_name) {
    return !$this->getConfig($config_name)->isNew();
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

  /**
   * Gets config object for the given config name.
   *
   * @param string $config_name
   *   Full name of the config, eg node.type.content_page.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Config object.
   */
  protected function getConfig($config_name) {
    return $this->configFactory->get($config_name);
  }

}
