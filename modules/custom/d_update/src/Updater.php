<?php

declare(strict_types = 1);

namespace Drupal\d_update;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\DiffArray;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\block\Entity\Block;
use Drupal\d_p\Helper\NestedArrayHelper;

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
   * @var \Drupal\d_update\ConfigCompareInterface
   */
  protected $configCompare;

  /**
   * Config manager service.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Update Checklist service.
   *
   * @var \Drupal\d_update\UpdateChecklist
   */
  protected $checklist;

  /**
   * Modules Extensions List service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ExtensionPathResolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * Constructs the Updater.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   Module installer service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   Config storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\d_update\ConfigCompareInterface $config_compare
   *   D Update Config compare service.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Config manager service.
   * @param \Drupal\d_update\UpdateChecklist $checklist
   *   Update Checklist service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   Update Module Extension List service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   */
  public function __construct(
    ModuleInstallerInterface $module_installer,
    StorageInterface $config_storage,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigCompareInterface $config_compare,
    ConfigManagerInterface $config_manager,
    UpdateChecklist $checklist,
    ModuleExtensionList $module_extension_list,
    ConfigFactoryInterface $config_factory,
    ExtensionPathResolver $extension_path_resolver
  ) {
    $this->moduleInstaller = $module_installer;
    $this->configStorage = $config_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->configCompare = $config_compare;
    $this->configManager = $config_manager;
    $this->checklist = $checklist;
    $this->moduleExtensionList = $module_extension_list;
    $this->configFactory = $config_factory;
    $this->logger = $this->getLogger('d_update');
    $this->extensionPathResolver = $extension_path_resolver;
  }

  /**
   * Returns the update checklist.
   *
   * @return \Drupal\d_update\UpdateChecklist
   *   Returns the update checklist.
   */
  public function checklist() {
    return $this->checklist;
  }

  /**
   * Import a config file if the module exists.
   *
   * The method tries to read config files from the modules' 'install' or
   * 'optional' directories, if the config has been found and the module
   * exists - the config is imported.
   *
   * @param string $source
   *   Module/theme name.
   * @param string $name
   *   Config file name without .yml extension.
   * @param string $hash
   *   Hashed array with config data.
   *
   * @return bool
   *   TRUE if the config was imported successfully or the module does not
   *   exist, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function importConfig($source, $name, $hash) {
    $data = $this->readConfigFromFile($source, $name, 'install');

    if (empty($data)) {
      $data = $this->readConfigFromFile($source, $name, 'optional');
    }

    if (empty($data)) {
      $this->logger
        ->error('Cannot find file for %config', ['%config' => $name]);

      return FALSE;
    }

    // Check if the module exists.
    try {
      $this->moduleExtensionList->getExtensionInfo($source);
    }
    catch (UnknownExtensionException $exception) {
      $this->logger->warning('The specified extensions %extension could not be found or is not installed. Configuration import skipped.', ['%extension' => $source]);

      return TRUE;
    }

    return $this->createConfig($name, $data, $hash);
  }

  /**
   * Reads config file data from directory based on source and type.
   *
   * @param string $source
   *   Module/theme name.
   * @param string $name
   *   Config file name without extension.
   * @param string $source_directory
   *   Specify if file should be looked inside optional or install.
   *
   * @return array|bool
   *   The configuration data stored for the configuration object name. If no
   *   configuration data exists for the given name, FALSE is returned.
   */
  public function readConfigFromFile($source, $name, $source_directory) {
    $source_info = $this->getSourceInformation($source);
    $config_path = $this->extensionPathResolver->getPath($source_info['source_type'], $source_info['source']) . '/config';
    $source = new FileStorage($config_path . '/' . $source_directory);

    return $source->read($name);
  }

  /**
   * Returns array with source name and source_type.
   *
   * @param string $source
   *   Module/theme name.
   *
   * @return array
   *   Array containing source_type and source name.
   */
  protected function getSourceInformation($source) {
    // Parameter $source equal to "foo" means a module, "theme/foo"
    // means a theme.
    $source_type = 'module';
    $parts = explode('/', $source);
    if (count($parts) == 2) {
      $source_type = $parts[0];
      $source = $parts[1];
    }
    return [
      'source_type' => $source_type,
      'source' => $source,
    ];
  }

  /**
   * Import many config files at once.
   *
   * @param array $configs
   *   Two dimensional array with structure "theme_or_module_name" =>
   *   ["config_file_name" => "config_hash"].
   *
   * @return bool
   *   Returns if all of the configs were imported successfully.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function importConfigs(array $configs) {
    $status = [];
    foreach ($configs as $source => $config) {
      foreach ($config as $config_name => $config_hash) {
        $status[] = $this->importConfig($source, $config_name, $config_hash);
      }
    }

    return !in_array(FALSE, $status);
  }

  /**
   * Install modules.
   *
   * @param array $modules
   *   Numeric array with module names.
   * @param bool $enable_dependencies
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

    $module_data = $this->moduleExtensionList->getList();
    $modules = array_combine($modules, $modules);
    if (array_diff_key($modules, $module_data)) {
      return FALSE;
    }

    return $this->moduleInstaller->install($modules, $enable_dependencies);
  }

  /**
   * Method creates new instance of existing blocks inside another theme.
   *
   * @param string $subthemeName
   *   Name of the subtheme to place block into.
   * @param array $configs
   *   List of blocks configs to instantiate.
   */
  public function instantiateBlocksForSubtheme($subthemeName, array $configs) {
    foreach ($configs as $baseThemeConfigs) {
      foreach ($baseThemeConfigs as $configName => $hash) {
        $baseConfig = $this->configFactory->get($configName)->getRawData();
        unset($baseConfig['uuid']);
        $baseConfig['id'] = $baseConfig['id'] . '_' . $subthemeName;
        $baseConfig['theme'] = $subthemeName;
        $block = Block::create($baseConfig);
        try {
          $block->save();
        }
        catch (EntityStorageException $e) {
          $this->logger->error('Error while instantiating block from %config', [
            '%config' => $configName,
          ]);
        }
      }
    }
  }

  /**
   * Creates config entities from name, file and hash.
   *
   * @param string $name
   *   Config name.
   * @param array|bool $data
   *   Data read from file.
   * @param string $hash
   *   Config hash.
   *
   * @return bool
   *   Status of config import.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createConfig($name, $data, $hash) {

    if (!$this->verifyHash($name, $hash)) {
      $this->logger->warning('Detected changes in %config, aborting import...', [
        '%config' => $name,
      ]);
      return FALSE;
    }

    $entity_type = $this->configManager->getEntityTypeIdByName($name);
    if (!empty($entity_type)) {
      // If this is field config, handle it properly.
      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type);
      /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
      $entity_type = $storage->getEntityType();

      // Try to load the existing config.
      $id = $storage->getIDFromConfigName($name, $entity_type->getConfigPrefix());
      $existingEntity = $storage->load($id);
      if (!empty($existingEntity)) {
        // Set the proper UUID to avoid conflicts.
        $data['uuid'] = $existingEntity->uuid();
      }

      $entity = $storage->createFromStorageRecord($data);

      // If we need an update, we have to inform the storage about it.
      if (!empty($existingEntity)) {
        $entity->set('original', $existingEntity);
        $entity->enforceIsNew(FALSE);
      }

      // Do the update.
      try {
        $entity->save();
        $this->logger->info('Successfully imported field config %config', [
          '%config' => $name,
        ]);
        return TRUE;
      }
      catch (EntityStorageException $e) {
        $this->logger->error('Error while importing entity config %config', [
          '%config' => $name,
        ]);
        return FALSE;
      }
    }
    else {
      // Otherwise use plain config storage.
      try {
        $this->configStorage->write($name, $data);
        $this->logger->info('Successfully imported config %config', [
          '%config' => $name,
        ]);
        return TRUE;
      }
      catch (StorageException $e) {
        $this->logger->error('Error while importing config %config', [
          '%config' => $name,
        ]);
        return FALSE;
      }
    }
  }

  /**
   * Returns whether adding config to database should proceed.
   *
   * @param string $name
   *   Config name without extension.
   * @param string $hash
   *   Config hash or keyword, empty for new configs.
   *
   * @return bool
   *   Returns TRUE for proceed, false for halt.
   */
  public function verifyHash($name, $hash) {
    switch ($hash) {
      case 'override':
        return TRUE;

      case '':
        return !$this->configCompare->configExists($name);

      default:
        return $this->configCompare->compare($name, $hash);
    }
  }

  /**
   * Allows updating of single config, based on yml file.
   *
   * @todo Implement mechanism for "change" keyword.
   *
   * @param string $source
   *   Module/theme name.
   * @param string $name
   *   Config file name without extension.
   *
   * @return bool
   *   Returns if config was modified successfully.
   */
  public function updateConfigurations($source, $name) {
    $data = $this->readConfigFromFile($source, $name, 'update');
    $status = [];
    if (empty($data)) {
      $this->logger->error('Cannot find file for %config', ['%config' => $name]);

      return FALSE;
    }
    foreach ($data as $configName => $configOperations) {
      $updates = $configOperations;
      $config = $this->configFactory->getEditable($configName);
      $newConfig = $config->get();
      $isOptional = $updates['optional'] ?? FALSE;

      if (isset($updates['delete'])) {
        foreach ($updates['delete'] as $update) {
          NestedArray::unsetValue($newConfig, explode(':', $update));
        }
      }

      if (isset($updates['delete_value'])) {
        foreach ($updates['delete_value'] as $update) {
          $exp = explode(':', $update['parents']);

          foreach ($update['values'] as $value) {
            NestedArrayHelper::unsetValueIfEqualTo($newConfig, $exp, $value);
          }
        }
      }

      if (isset($updates['change'])) {
        $newConfig = NestedArray::mergeDeep($newConfig, $updates['change']['new']);
      }

      if (isset($updates['add'])) {
        $newConfig = NestedArray::mergeDeep($newConfig, $updates['add']);
      }

      if (!isset($updates['change']['expected'])) {
        $updates['change']['expected'] = NULL;
      }

      if (!$this->modifyConfig($configName, $newConfig, $updates['change']['expected'])) {
        if ($isOptional) {
          $this->logger->notice('Update failed for optional %config, skipping', ['%config' => $name]);
        }
        else {
          $status[] = FALSE;
          $this->logger->error('Update failed for %config', ['%config' => $name]);
        }
      }
    }

    return !in_array(FALSE, $status);
  }

  /**
   * Loads and changes config.
   *
   * @param string $configName
   *   Name of config to modify.
   * @param array $newConfig
   *   Array containing changes to apply.
   * @param array $expectedConfig
   *   Array containing expected config values.
   *
   * @return bool
   *   Return if the config was changed successfully.
   */
  private function modifyConfig($configName, array $newConfig, array $expectedConfig = []) {
    $configName = $this->replacePlaceholders($configName);
    $config = $this->configFactory->getEditable($configName);
    $configData = $config->get();

    if ($config->isNew() || empty($configData)) {
      $this->logger
        ->error("Unable to modify newly created or empty %config configuration. Aborting import", ['%config' => $configName]);
      return FALSE;
    }

    if (!empty($expectedConfig) && DiffArray::diffAssocRecursive($expectedConfig, $configData)) {
      $this->logger
        ->error('Detected changes in configuration %config. Aborting import', ['%config' => $configName]);
      return FALSE;
    }

    $config->setData($newConfig)->save();

    return TRUE;
  }

  /**
   * Replace placeholders in config file names.
   *
   * @param string $name
   *   Config file name.
   *
   * @return string
   *   Config file name with placeholders replaced.
   */
  protected function replacePlaceholders(string $name) {
    return (new FormattableMarkup($name, [
      '@theme' => $this->configFactory->get('system.theme')->get('default'),
    ]))->__toString();
  }

}
