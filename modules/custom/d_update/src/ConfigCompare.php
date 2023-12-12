<?php

declare(strict_types = 1);

namespace Drupal\d_update;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Config Compare service.
 */
class ConfigCompare implements ConfigCompareInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config compare constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function configExists($config_name) {
    return !$this->getConfig($config_name)->isNew();
  }

  /**
   * {@inheritdoc}
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
