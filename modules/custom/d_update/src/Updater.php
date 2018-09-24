<?php

/**
 * @file
 * Contains \Drupal\d_update\Updater.
 */

namespace Drupal\d_update;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\d_update\ConfigManager;
use Drupal\d_update\UpdateChecklist;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\FileStorage;

/**
 * Helper class to update configuration.
 */
class Updater {

  use StringTranslationTrait;

  /**
   * Module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * Config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * D update config manager service.
   *
   * @var \Drupal\d_update\ConfigManager
   */
  protected $configManager;

  /**
   * Update Checklist service.
   *
   * @var \Drupal\d_update\UpdateChecklist
   */
  protected $checklist;

  /**
   * Constructs the Updater.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   Module installer service.
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   Config storage service.
   * @param \Drupal\d_update\ConfigManager $configManager
   *   D Update Config manager service.
   * @param \Drupal\d_update\UpdateChecklist $checklist
   *   Update Checklist service.
   */
  public function __construct(ModuleInstallerInterface $moduleInstaller,
                              StorageInterface $configStorage,
                              ConfigManager $configManager,
                              UpdateChecklist $checklist) {
    $this->moduleInstaller = $moduleInstaller;
    $this->configStorage = $configStorage;
    $this->configManager = $configManager;
    $this->checklist = $checklist;
  }

  /**
   * @return \Drupal\d_update\UpdateChecklist
   *   Returns the update checklist.
   */
  public function checklist() {
    return $this->checklist;
  }

  /**
   * Import a config file.
   *
   * @param string $module
   *  Module name.
   * @param string $name
   *  Config file name without .yml extension.
   * @param string $hash
   *   Hashed array with config data.
   *
   * @return bool
   *   Returns if config was imported successfully.
   */
  public function importConfig($module, $name, $hash) {
    $configPath = drupal_get_path('module', $module) . '/config/install';
    $source = new FileStorage($configPath);
    $data = $source->read($name);
    if (!$data || !$this->configManager->compare($name, $hash)) {
      return false;
    }

    return $this->configStorage->write($name, $data);
  }

  /**
   * Import many config files at once.
   *
   * @param array $configs
   *   Two dimensional array with structure "module_name" => ["config_file_name" => "config_hash"]
   *
   * @return bool
   *  Returns if all of the configs were imported successfully.
   */
  public function importConfigs(array $configs) {
    $status = [];
    foreach ($configs as $module => $config) {
      foreach ($config as $configName => $configHash) {
        $status[] = $this->importConfig($module, $configName, $configHash);
      }
    }

    return !in_array(false, $status);
  }

  /**
   * Install modules.
   *
   * @param array $modules
   *   Numeric array with module names.
   * @param bool $enableDependencies
   *   Should dependencies for modules be enabled.
   *
   * @return bool
   *   Returns if modules were installed successfully.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function installModules(array $modules, $enableDependencies = TRUE) {
    if (empty($modules) || !is_array($modules)) {
      return FALSE;
    }

    $moduleData = system_rebuild_module_data();
    $modules = array_combine($modules, $modules);
    if ($missing_modules = array_diff_key($modules, $moduleData)) {
      return FALSE;
    }

    return $this->moduleInstaller->install($modules, $enableDependencies);
  }

}
