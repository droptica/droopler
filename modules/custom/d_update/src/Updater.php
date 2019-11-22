<?php

/**
 * @file
 * Contains \Drupal\d_update\Updater.
 */

namespace Drupal\d_update;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\d_update\ConfigManager;
use Drupal\d_update\UpdateChecklist;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper class to update configuration.
 */
class Updater {

  use StringTranslationTrait;
  use LoggerChannelTrait;

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
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   Module installer service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   Config storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Config storage service.
   * @param \Drupal\d_update\ConfigManager $config_manager
   *   D Update Config manager service.
   * @param \Drupal\d_update\UpdateChecklist $checklist
   *   Update Checklist service.
   */
  public function __construct(ModuleInstallerInterface $module_installer,
                              StorageInterface $config_storage,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigManager $config_manager,
                              UpdateChecklist $checklist) {
    $this->moduleInstaller = $module_installer;
    $this->configStorage = $config_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
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
    if (!$data || !$this->configManager->compare($name, $hash)) {
      return FALSE;
    }

    if (preg_match('/^field\.storage\./', $name)) {
      // If this is field storage, save it via field_storage_config.
      $this->getLogger('d_update')->info('Creating field storage %config', [
        '%config' => $name,
      ]);
      return $this->entityTypeManager->getStorage('field_storage_config')
        ->create($data)
        ->save();
    }
    else if (preg_match('/^field\.field\./', $name)) {
      // If this is field instance, save it via field_config.
      $this->getLogger('d_update')->info('Creating field instance %config', [
        '%config' => $name,
      ]);
      return $this->entityTypeManager->getStorage('field_config')
        ->create($data)
        ->save();
    }
    else {
      // Otherwise use plain config storage.
      $this->getLogger('d_update')->info('Importing config  %config', [
        '%config' => $name,
      ]);
      return $this->configStorage->write($name, $data);
    }

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
