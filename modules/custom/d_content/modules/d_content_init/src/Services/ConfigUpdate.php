<?php

namespace Drupal\d_content_init\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\FileStorage;
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
   * value specified in the variable $configName.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   The path to the directory with the configuration for the module.
   * @param string $regex
   *   Regular expresion pattern to search in configuration file names.
   *
   * @return array
   *   Array of configs names.
   */
  public function getConfigsFilesName($moduleName, $path, $regex) {
    $configsFileNames = [];
    $filesNames = scandir($this->getConfigsPath($moduleName, $path));
    foreach ($filesNames as $fileName) {
      if (preg_match($regex, $fileName) === 1) {
        $configsFileNames[] = substr($fileName, 0, -4);
      }
    }
    return $configsFileNames;
  }

  /**
   * Create configurations for block for active theme.
   *
   * @param array $configs
   *   Array of configs.
   * @param string $path
   *   The path to the directory with the configuration for the module.
   */
  public function createBlocksConfigs(array $configs, $path) {
    $theme = $this->configFactory->get('system.theme')->get('default');
    $storage = new FileStorage($path);
    foreach ($configs as $themeConfigName) {
      $subthemeConfigName = $this->getConfigName($themeConfigName, $theme);
      $newConfig = $this->configFactory->getEditable($subthemeConfigName);
      // Check if the configuration is new.
      if ($newConfig->isNew()) {
        $newConfig->setData($storage->read($themeConfigName));
        // TODO: Add correct id for block config.
        $newConfig->set('theme', $theme)
          ->set('dependencies.theme', [$theme])
          ->set('id', $this->getConfigId($subthemeConfigName))
          ->save();
      }
    }
  }

  /**
   * Get config name parts.
   *
   * @param string $config
   *   Configuration file names.
   *
   * @return array
   *   Config name parts exploded by dots.
   */
  public function getConfigNameParts($config) {
    return explode('.', $config);

  }

  /**
   * Get config name for active theme.
   *
   * @param string $config
   *   Configuration file names.
   * @param string $theme
   *   Active theme name.
   *
   * @return string
   *   Config name for theme.
   */
  public function getConfigName($config, $theme) {
    $parts = $this->getConfigNameParts($config);
    // Add active theme name to config.
    $parts[2] = $theme . '_' . $parts[2];
    return implode('.', $parts);
  }

  /**
   * Get config id from config name for active theme.
   *
   * @param string $config
   *   Config name.
   *
   * @return string
   *   Config id.
   */
  public function getConfigId($config) {
    $parts = $this->getConfigNameParts($config);
    unset($parts[0]);
    unset($parts[1]);
    return implode('.', $parts);
  }

  /**
   * Get the directory with configurations for the selected module.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   The path to the directory with the configuration for the module.
   *
   * @return string
   *   Path do directory with configs.
   */
  public function getConfigsPath($moduleName, $path) {
    if ($this->moduleHandler->moduleExists($moduleName)) {
      $dir = $this->moduleHandler->getModule($moduleName)->getPath() . $path;
      if (file_exists($dir)) {
        return $dir;
      }
      throw new \RuntimeException('The directory for the module configurations does not exist!');
    }
  }

  /**
   * Create new blocks configuration for active theme.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   The path to the directory with the configuration for the module.
   * @param string $regex
   *   Regular expresion pattern to search in configuration file names.
   */
  public function importConfigs($moduleName, $path, $regex) {
    $configs = $this->getConfigsFilesName($moduleName, $path, $regex);
    $this->createBlocksConfigs($configs, $this->getConfigsPath($moduleName, $path));

  }

  /**
   * Delete configs.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $path
   *   The path to the directory with the configuration for the module.
   * @param string $regex
   *   Regular expresion pattern to search in configuration file names.
   */
  public function deleteConfigs($moduleName, $path, $regex) {
    $theme = $this->configFactory->get('system.theme')->get('default');
    $configs = $this->getConfigsFilesName($moduleName, $path, $regex);
    foreach ($configs as $themeConfigName) {
      $configToDelete = $this->configFactory->getEditable($this->getConfigName($themeConfigName, $theme));
      if (!$configToDelete->isNew()) {
        $configToDelete->delete();
      }
    }
  }

}
