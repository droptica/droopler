<?php

namespace Drupal\d_content_init\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactory;

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
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * ConfigUpdate constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger factory.
   */
  public function __construct(ConfigFactory $configFactory, ModuleHandler $moduleHandler, LoggerChannelFactory $loggerFactory) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->loggerFactory = $loggerFactory;
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
  public function getConfigsFilesNames($moduleName, $path, $regex) {
    $configsFileNames = [];
    try {
      $filesNames = scandir($this->getConfigsPath($moduleName, $path));
      foreach ($filesNames as $fileName) {
        if (preg_match($regex, $fileName) === 1) {
          $configsFileNames[] = substr($fileName, 0, -4);
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory
        ->get('d_content_init')
        ->error('Unable to load configuration files names: ' . $e->getMessage());
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
      $subthemeConfigName = $this->getSubthemeConfigName($themeConfigName, $theme);
      $newConfig = $this->configFactory->getEditable($subthemeConfigName);
      // Check if the configuration is new.
      if ($newConfig->isNew()) {
        $newConfig->setData($storage->read($themeConfigName));
        $newConfig->set('theme', $theme)
          ->set('dependencies.theme', [$theme])
          ->set('id', $this->getConfigId($themeConfigName, $theme))
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
    $parts = explode('.', $config);
    if (is_array($parts) && !empty($parts)) {
      return $parts;
    }
    throw new \RuntimeException('Invalid config name: ' . $config);
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
  public function getSubthemeConfigName($config, $theme) {
    $parts = $this->getConfigNameParts($config);
    // Add active theme name to config.
    if (isset($parts[2])) {
      $parts[2] = $theme . '_' . $parts[2];
    }
    else {
      $parts[2] = $theme;
    }

    return implode('.', $parts);
  }

  /**
   * Get config id from config name for active theme.
   *
   * @param string $configName
   *   Config name.
   * @param string $theme
   *   Theme name.
   *
   * @return string
   *   Config id.
   */
  public function getConfigId($configName, $theme) {
    return $theme . '_' . $this->configFactory->getEditable($configName)->get('id');
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
    $configs = $this->getConfigsFilesNames($moduleName, $path, $regex);
    try {
      $this->createBlocksConfigs($configs, $this->getConfigsPath($moduleName, $path));
    }
    catch (\Exception $e) {
      $this->loggerFactory
        ->get('d_content_init')
        ->error('Create configs for active theme failed: ' . $e->getMessage());
    }
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
    $configs = $this->getConfigsFilesNames($moduleName, $path, $regex);
    foreach ($configs as $themeConfigName) {
      try {
        $subthemeConfigName = $this->getSubthemeConfigName($themeConfigName, $theme);
        $configToDelete = $this->configFactory->getEditable($subthemeConfigName);
        if (!$configToDelete->isNew()) {
          $configToDelete->delete();
        }
      }
      catch (\Exception $e) {
        $this->loggerFactory
          ->get('d_content_init')
          ->error('Delete config failed: ' . $e->getMessage());
      }
    }
  }

}
