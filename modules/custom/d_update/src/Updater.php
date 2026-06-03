<?php

declare(strict_types=1);

namespace Drupal\d_update;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\DiffArray;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\block\Entity\Block;
use Drupal\d_p\Helper\NestedArrayHelper;

/**
 * Helper service that orchestrates Droopler config updates.
 *
 * Provides:
 *  - importing config from a module's `config/install` or `config/optional`
 *    directory (with hash-fenced safety),
 *  - patching live configuration via declarative YAML diffs in
 *    `config/update/<name>.yml` (delete / delete_value / change / add),
 *  - installing modules (with dependency resolution),
 *  - cloning block configs to a new subtheme.
 */
class Updater {

  use StringTranslationTrait;

  protected const string LOGGER_CHANNEL = 'd_update';

  /**
   * Logger channel for d_update.
   */
  protected readonly LoggerChannelInterface $logger;

  public function __construct(
    protected readonly ModuleInstallerInterface $moduleInstaller,
    protected readonly StorageInterface $configStorage,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigCompareInterface $configCompare,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly UpdateChecklist $checklist,
    protected readonly ModuleExtensionList $moduleExtensionList,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ExtensionPathResolver $extensionPathResolver,
    LoggerChannelFactoryInterface $logger_channel_factory,
  ) {
    $this->logger = $logger_channel_factory->get(self::LOGGER_CHANNEL);
  }

  /**
   * Update checklist service.
   */
  public function checklist(): UpdateChecklist {
    return $this->checklist;
  }

  /**
   * Import a config file if the module/theme exists.
   *
   * Tries `config/install` then `config/optional` under the source extension.
   * When the source extension isn't installed the call no-ops with a warning
   * and returns TRUE (so caller iteration continues).
   *
   * @return bool
   *   TRUE on successful import or skipped-missing-module, FALSE on real
   *   failure (missing file, hash mismatch, storage exception).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function importConfig(string $source, string $name, string $hash): bool {
    $data = $this->readConfigFromFile($source, $name, 'install');
    if (empty($data)) {
      $data = $this->readConfigFromFile($source, $name, 'optional');
    }
    if (empty($data)) {
      $this->logger->error('Cannot find file for %config', ['%config' => $name]);
      return FALSE;
    }

    try {
      $this->moduleExtensionList->getExtensionInfo($source);
    }
    catch (UnknownExtensionException) {
      $this->logger->warning('The specified extensions %extension could not be found or is not installed. Configuration import skipped.', ['%extension' => $source]);
      return TRUE;
    }

    return $this->createConfig($name, $data, $hash);
  }

  /**
   * Read a config file from an extension's `config/<source_directory>` folder.
   *
   * @return array<string, mixed>|false
   *   Config data, or FALSE if the file doesn't exist.
   */
  public function readConfigFromFile(string $source, string $name, string $source_directory): array|false {
    $source_info = $this->getSourceInformation($source);
    $config_path = $this->extensionPathResolver->getPath($source_info['source_type'], $source_info['source']) . '/config';
    $storage = new FileStorage($config_path . '/' . $source_directory);

    return $storage->read($name);
  }

  /**
   * Import multiple config files at once.
   *
   * @param array<string, array<string, string>> $configs
   *   Mapping of `source => [config_name => hash]`.
   *
   * @return bool
   *   TRUE iff every config was imported (or its module was missing).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function importConfigs(array $configs): bool {
    $status = [];
    foreach ($configs as $source => $config) {
      foreach ($config as $config_name => $config_hash) {
        $status[] = $this->importConfig((string) $source, (string) $config_name, (string) $config_hash);
      }
    }
    return !in_array(FALSE, $status, TRUE);
  }

  /**
   * Install a list of modules.
   *
   * @param string[] $modules
   *   Module machine names.
   * @param bool $enable_dependencies
   *   When TRUE, recursively install missing dependencies.
   *
   * @return bool
   *   TRUE on success, FALSE when the list is empty or unknown modules appear.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function installModules(array $modules, bool $enable_dependencies = TRUE): bool {
    if ($modules === []) {
      return FALSE;
    }

    $module_data = $this->moduleExtensionList->getList();
    $modules = array_combine($modules, $modules);
    if (array_diff_key($modules, $module_data) !== []) {
      return FALSE;
    }

    return $this->moduleInstaller->install($modules, $enable_dependencies);
  }

  /**
   * Clone block configs to a new subtheme.
   *
   * @param string $subthemeName
   *   Machine name of the subtheme to clone blocks into.
   * @param array<string, array<string, string>> $configs
   *   Mapping of `base_theme => [block_config_name => hash]`.
   */
  public function instantiateBlocksForSubtheme(string $subthemeName, array $configs): void {
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
        catch (EntityStorageException) {
          $this->logger->error('Error while instantiating block from %config', ['%config' => $configName]);
        }
      }
    }
  }

  /**
   * Create / overwrite a config entity (or plain config) from imported data.
   *
   * @param string $name
   *   Config name (the YAML file basename without extension).
   * @param array<string, mixed>|false $data
   *   Config data as read from the source file. Passing FALSE returns FALSE
   *   (defensive — kept for backwards compatibility with old callers).
   * @param string $hash
   *   Hash strategy understood by ::verifyHash() (`override`, empty, or value).
   *
   * @return bool
   *   TRUE when the import succeeded, FALSE when aborted by hash mismatch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createConfig(string $name, array|false $data, string $hash): bool {
    if ($data === FALSE) {
      return FALSE;
    }
    if (!$this->verifyHash($name, $hash)) {
      $this->logger->warning('Detected changes in %config, aborting import...', ['%config' => $name]);
      return FALSE;
    }

    $entity_type = $this->configManager->getEntityTypeIdByName($name);
    if (empty($entity_type)) {
      return $this->writePlainConfig($name, $data);
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type);
    return $this->writeEntityConfig($storage, $name, $data);
  }

  /**
   * Decide whether import should proceed based on the stored hash strategy.
   *
   * `override` → always proceed.
   * empty hash → proceed only when no config currently exists.
   * any other → proceed only when the live config matches the hash exactly.
   */
  public function verifyHash(string $name, string $hash): bool {
    return match ($hash) {
      'override' => TRUE,
      '' => !$this->configCompare->configExists($name),
      default => $this->configCompare->compare($name, $hash),
    };
  }

  /**
   * Apply a declarative YAML diff over each config in an update file.
   *
   * Reads `config/update/<name>.yml` and applies any of the supported
   * operations (`delete`, `delete_value`, `change`, `add`) per config entry.
   */
  public function updateConfigurations(string $source, string $name): bool {
    $data = $this->readConfigFromFile($source, $name, 'update');
    if (empty($data)) {
      $this->logger->error('Cannot find file for %config', ['%config' => $name]);
      return FALSE;
    }

    $status = [];
    foreach ($data as $configName => $configOperations) {
      $updates = $configOperations;
      $config = $this->configFactory->getEditable($configName);
      $newConfig = $config->get();
      $isOptional = (bool) ($updates['optional'] ?? FALSE);

      if (isset($updates['delete'])) {
        foreach ($updates['delete'] as $update) {
          NestedArray::unsetValue($newConfig, explode(':', $update));
        }
      }

      if (isset($updates['delete_value'])) {
        foreach ($updates['delete_value'] as $update) {
          $exp = explode(':', $update['parents']);
          foreach ($update['values'] as $value) {
            NestedArrayHelper::unsetValueIfEqualTo($newConfig, $exp, (string) $value);
          }
        }
      }

      if (isset($updates['change'])) {
        $newConfig = NestedArray::mergeDeep($newConfig, $updates['change']['new']);
      }

      if (isset($updates['add'])) {
        $newConfig = NestedArray::mergeDeep($newConfig, $updates['add']);
      }

      $expected = $updates['change']['expected'] ?? NULL;
      if ($this->modifyConfig($configName, $newConfig, $expected)) {
        continue;
      }
      if ($isOptional) {
        $this->logger->notice('Update failed for optional %config, skipping', ['%config' => $name]);
        continue;
      }
      $status[] = FALSE;
      $this->logger->error('Update failed for %config', ['%config' => $name]);
    }

    return !in_array(FALSE, $status, TRUE);
  }

  /**
   * Resolve `source_type` (`module` / `theme`) and `source` (machine name).
   *
   * @return array{source_type: string, source: string}
   *   Source descriptor parsed from a `type/name` string.
   */
  protected function getSourceInformation(string $source): array {
    $parts = explode('/', $source);
    if (count($parts) === 2) {
      return [
        'source_type' => $parts[0],
        'source' => $parts[1],
      ];
    }
    return [
      'source_type' => 'module',
      'source' => $source,
    ];
  }

  /**
   * Write data to a plain (non-entity) config object.
   */
  protected function writePlainConfig(string $name, array $data): bool {
    try {
      $this->configStorage->write($name, $data);
      $this->logger->info('Successfully imported config %config', ['%config' => $name]);
      return TRUE;
    }
    catch (StorageException) {
      $this->logger->error('Error while importing config %config', ['%config' => $name]);
      return FALSE;
    }
  }

  /**
   * Write data to a config entity, updating in place when one already exists.
   */
  protected function writeEntityConfig(ConfigEntityStorageInterface $storage, string $name, array $data): bool {
    $entityType = $storage->getEntityType();
    assert($entityType instanceof ConfigEntityTypeInterface);
    $id = $storage->getIDFromConfigName($name, $entityType->getConfigPrefix());
    $existingEntity = $storage->load($id);
    if ($existingEntity !== NULL) {
      $data['uuid'] = $existingEntity->uuid();
    }

    $entity = $storage->createFromStorageRecord($data);
    if ($existingEntity !== NULL) {
      $entity->original = $existingEntity;
      $entity->enforceIsNew(FALSE);
    }

    try {
      $entity->save();
      $this->logger->info('Successfully imported field config %config', ['%config' => $name]);
      return TRUE;
    }
    catch (EntityStorageException) {
      $this->logger->error('Error while importing entity config %config', ['%config' => $name]);
      return FALSE;
    }
  }

  /**
   * Apply patched data to a config, optionally guarded by expected diff.
   *
   * Returns FALSE (without writing) when:
   *  - the target config is new or empty,
   *  - `$expectedConfig` is provided and the live config doesn't already
   *    contain it (recursive diff guard against drift).
   */
  protected function modifyConfig(string $configName, array $newConfig, ?array $expectedConfig = NULL): bool {
    $configName = $this->replacePlaceholders($configName);
    $config = $this->configFactory->getEditable($configName);
    $configData = $config->get();

    if ($config->isNew() || empty($configData)) {
      $this->logger->error('Unable to modify newly created or empty %config configuration. Aborting import', ['%config' => $configName]);
      return FALSE;
    }

    if (!empty($expectedConfig) && DiffArray::diffAssocRecursive($expectedConfig, $configData)) {
      $this->logger->error('Detected changes in configuration %config. Aborting import', ['%config' => $configName]);
      return FALSE;
    }

    $config->setData($newConfig)->save();
    return TRUE;
  }

  /**
   * Replace `@theme` placeholder in config names with the active theme.
   */
  protected function replacePlaceholders(string $name): string {
    return (string) (new FormattableMarkup($name, [
      '@theme' => $this->configFactory->get('system.theme')->get('default'),
    ]));
  }

}
