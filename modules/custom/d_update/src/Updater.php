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
  protected $module_installer;

  /**
   * Config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $config_storage;

  /**
   * D update config manager service.
   *
   * @var \Drupal\d_update\ConfigManager
   */
  protected $config_manager;

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
  public function __construct(ModuleInstallerInterface $module_installer,
                              StorageInterface $config_storage,
                              ConfigManager $config_manager,
                              UpdateChecklist $checklist) {
    $this->module_installer = $module_installer;
    $this->config_storage = $config_storage;
    $this->config_manager = $config_manager;
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
    $config_path = drupal_get_path('module', $module) . '/config/install';
    $source = new FileStorage($config_path);
    $data = $source->read($name);
    if (!$data || !$this->config_manager->compare($name, $hash)) {
      return FALSE;
    }

    return $this->config_storage->write($name, $data);
  }

  /**
   * Import many config files at once.
   *
   * @param array $configs
   *   Two dimensional array with structure "module_name" =>
   *   ["config_file_name" => "config_hash"]
   *
   * @return bool
   *  Returns if all of the configs were imported successfully.
   */
  public function importConfigs(array $configs) {
    $status = [];
    foreach ($configs as $module => $config) {
      foreach ($config as $config_name => $config_hash) {
        $status[] = $this->importConfig($module, $config_name, $config_hash);
      }
    }

    return !in_array(FALSE, $status);
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
  public function installModules(array $modules, $enable_dependencies = TRUE) {
    if (empty($modules) || !is_array($modules)) {
      return FALSE;
    }

    $module_data = system_rebuild_module_data();
    $modules = array_combine($modules, $modules);
    if ($missing_modules = array_diff_key($modules, $module_data)) {
      return FALSE;
    }

    return $this->moduleInstaller->install($modules, $enable_dependencies);
  }

}
