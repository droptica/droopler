<?php

namespace Drupal\d_update;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Config\StorageInterface;
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
   * @param \Drupal\d_update\UpdateChecklist $checklist
   *   Update Checklist service.
   */
  public function __construct(ModuleInstallerInterface $moduleInstaller, StorageInterface $configStorage, UpdateChecklist $checklist) {
    $this->moduleInstaller = $moduleInstaller;
    $this->configStorage = $configStorage;
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
   *
   * @return bool
   *   Returns if config was imported successfully.
   */
  public function importConfig($module, $name) {
    $config_path = drupal_get_path('module', $module) . '/config/install';
    $source = new FileStorage($config_path);

    return $this->configStorage->write($name, $source->read($name));
  }

  /**
   * Import many config files at once.
   *
   * @param array $configs
   *   List of configs with structure "config_file_name" => "module_name"
   *
   * @return bool
   *  Returns if all of the configs were imported successfully.
   */
  public function importConfigs(array $configs) {
    $status = [];
    foreach ($configs as $config => $module) {
      $status[] = $this->importConfig($module, $config);
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
    return $this->moduleInstaller->install($modules, $enableDependencies);
  }

}
