<?php

namespace Drupal\d_update;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Helper class to update configuration.
 */
class Updater {

  use StringTranslationTrait;

  /**
   * Module installer service.
   *
   * @var ModuleInstallerInterface
   */
  protected $module_installer;

  /**
   * The logger service.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config storage.
   *
   * @var StorageInterface
   */
  protected $config_storage;

  /**
   * Constructor.
   *
   * @param ModuleInstallerInterface $module_installer
   *   Module installer service.
   *
   * @param LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   *
   * @param StorageInterface $config_storage
   *   Config storage service.
   */
  public function __construct(ModuleInstallerInterface $module_installer, LoggerChannelFactoryInterface $logger_factory, StorageInterface $config_storage) {
    $this->module_installer = $module_installer;
    $this->config_storage = $config_storage;
    $this->logger = $logger_factory->get('system');
  }

  /**
   * Update a single config.
   *
   * @param string $name
   *  Config name.
   *
   * @param string $module
   *  Config module.
   *
   * @param string $hash
   *  Previous hash of the config with langcode set to en.
   */
  public function updateConfig($name, $module, $hash = null) {
    $config_path = drupal_get_path('module', $module) . '/config/install';
    $source = new FileStorage($config_path);
    $this->config_storage->write($name, $source->read($name));
  }

  /**
   * Install a module.
   *
   * @param string $module
   *  Module machine name.
   *
   * @return bool
   *  TRUE if module is installed.
   */
  public function installModule($module) {
    try {
      if ($this->module_installer->install([$module])) {
        $this->logger->info($this->t('Module @module is successfully enabled.', ['@module' => $module]));
        return TRUE;
      }
      else {
        //$this->checklist->markUpdatesFailed([$update]);
        $this->logger->warning($this->t('Unable to enable @module.', ['@module' => $module]));
        return FALSE;
      }
    }
    catch (MissingDependencyException $e) {
      //$this->checklist->markUpdatesFailed([$update]);
      $this->logger->warning($this->t('Unable to enable @module because of missing dependencies.', ['@module' => $module]));
      return FALSE;
    }
  }
}
