<?php

namespace Drupal\d_content_init\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Class ConfigUpdate.
 *
 * @package Drupal\d_commerce\Services
 */
class ConfigUpdate {

  /**
   * Configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Manages modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * ConfigUpdate constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module handler.
   */
  public function __construct(ConfigFactory $configFactory, ModuleHandler $moduleHandler) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Searches for all files in the given path that contain in the name the
   * value specified in the variable $configName. Then, in the configurations
   * found, it changes the value of the theme option to the default theme.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   Directory path with configs.
   * @param string $configName
   *   String to search in configuration file names.
   *
   * @return array
   *   Array of configs.
   */
  public function getConfigs($moduleName, $path, $configName) {
    $configs = [];
    $dir = $this->moduleHandler->getModule($moduleName)->getPath();
    $files = scandir($dir . $path);
    foreach ($files as $file) {
      if (strpos($file, $configName) !== FALSE) {
        $configs[] = substr($file, 0, -4);
      }
    }
    return $configs;
  }

  /**
   * Set default theme for configs.
   *
   * @param array $configs
   *   Array of configuration file names.
   */
  public function setTheme(array $configs) {
    $theme = $this->configFactory->get('system.theme')->get('default');
    foreach ($configs as $config) {
      $this->configFactory->getEditable($config)
        ->set('theme', $theme)
        ->set('dependencies.theme', $theme)
        ->save();
    }
  }

  /**
   * Changes the value of the theme option to the default theme.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   Directory path with configs.
   * @param string $configName
   *   String to search in configuration file names.
   */
  public function updateThemeInConfigs($moduleName, $path, $configName) {
    $this->setTheme($this->getConfigs($moduleName, $path, $configName));
  }

  /**
   * Delete configs.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   Directory path with configs.
   * @param string $configName
   *   String to search in configuration file names.
   */
  public function deleteConfigs($moduleName, $path, $configName) {
    $configs = $this->getConfigs($moduleName, $path, $configName);
    foreach ($configs as $config) {
      $this->configFactory->getEditable($config)->delete();
    }
  }

}
