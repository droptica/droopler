<?php

/**
 * @file
 * Contains \Drupal\d_update\Updater.
 */

namespace Drupal\d_update;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\d_update\ConfigCompare;
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
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * D update config compare service.
   *
   * @var \Drupal\d_update\ConfigCompare
   */
  protected $configCompare;

  /**
   * Config manager service.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
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
   *   Entity type manager service.
   * @param \Drupal\d_update\ConfigCompare $config_compare
   *   D Update Config compare service.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Config manager service.
   * @param \Drupal\d_update\UpdateChecklist $checklist
   *   Update Checklist service.
   */
  public function __construct(ModuleInstallerInterface $module_installer,
                              StorageInterface $config_storage,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigCompare $config_compare,
                              ConfigManagerInterface $config_manager,
                              UpdateChecklist $checklist) {
    $this->moduleInstaller = $module_installer;
    $this->configStorage = $config_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->configCompare = $config_compare;
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
    if (!$data) {
      $this->getLogger('d_update')->error('Cannot find file for %config', [
        '%config' => $name,
      ]);
      return FALSE;
    }
    if (!$this->configCompare->compare($name, $hash)) {
      $this->getLogger('d_update')->warning('Detected changes in %config, aborting import...', [
        '%config' => $name,
      ]);
      return FALSE;
    }

    if (preg_match('/^(field|core.entity_form_display|core.entity_view_display|core.entity_view_mode)\./', $name)) {
      // If this is field config, handle it properly.
      $entity_type = $this->configManager->getEntityTypeIdByName($name);
      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type);

      // Try to load the existing config.
      $existing = $storage->loadByProperties(array('id' => $data['id']));
      if (!empty($existing)) {
        // Set the proper UUID to avoid conflicts.
        $data['uuid'] = $existing[$data['id']]->uuid();
      }

      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
      $entity = $storage->createFromStorageRecord($data);

      // If we need an update, we have to inform the storage about it.
      if (!empty($existing)) {
        $entity->original = $existing[$data['id']];
        $entity->enforceIsNew(FALSE);
      }

      // Do the update.
      try {
        $entity->save();
        $this->getLogger('d_update')->info('Successfully imported field config %config', [
          '%config' => $name,
        ]);
        return TRUE;
      }
      catch (EntityStorageException $e) {
        $this->getLogger('d_update')->error('Error while importing field config %config', [
          '%config' => $name,
        ]);
        return FALSE;
      }
    }
    else {
      // Otherwise use plain config storage.
      try {
        $this->configStorage->write($name, $data);
        $this->getLogger('d_update')->info('Successfully imported config %config', [
          '%config' => $name,
        ]);
        return TRUE;
      }
      catch (StorageException $e) {
        $this->getLogger('d_update')->error('Error while importing config %config', [
          '%config' => $name,
        ]);
        return FALSE;
      }
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
