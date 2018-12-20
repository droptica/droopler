<?php

namespace Drupal\config_update;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides methods related to config reverting, deleting, and importing.
 *
 * In this class, when any import or revert operation is requested, the
 * configuration that is being reverted or imported is searched for in both the
 * config/install repository and config/optional. This happens automatically.
 */
class ConfigReverter implements ConfigRevertInterface, ConfigDeleteInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeConfigStorage;

  /**
   * The extension config storage for config/install config items.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $extensionConfigStorage;

  /**
   * The extension config storage for config/optional config items.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionOptionalConfigStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs a ConfigReverter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active config storage.
   * @param \Drupal\Core\Config\StorageInterface $extension_config_storage
   *   The extension config storage.
   * @param \Drupal\Core\Config\StorageInterface $extension_optional_config_storage
   *   The extension config storage for optional config items.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, StorageInterface $active_config_storage, StorageInterface $extension_config_storage, StorageInterface $extension_optional_config_storage, ConfigFactoryInterface $config_factory, EventDispatcherInterface $dispatcher) {
    $this->entityManager = $entity_manager;
    $this->activeConfigStorage = $active_config_storage;
    $this->extensionConfigStorage = $extension_config_storage;
    $this->extensionOptionalConfigStorage = $extension_optional_config_storage;
    $this->configFactory = $config_factory;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function import($type, $name) {
    // Read the config from the file. Note: Do not call getFromExtension() here
    // because we need $full_name below.
    $full_name = $this->getFullName($type, $name);
    $value = FALSE;
    if ($full_name) {
      $value = $this->extensionConfigStorage->read($full_name);
      if (!$value) {
        $value = $this->extensionOptionalConfigStorage->read($full_name);
      }
    }
    if (!$value) {
      return FALSE;
    }

    // Save it as a new config entity or simple config.
    if ($type == 'system.simple') {
      $this->configFactory->getEditable($full_name)->setData($value)->save();
    }
    else {
      $entity_storage = $this->entityManager->getStorage($type);
      $entity = $entity_storage->createFromStorageRecord($value);
      $entity->save();
    }

    // Trigger an event notifying of this change.
    $event = new ConfigRevertEvent($type, $name);
    $this->dispatcher->dispatch(ConfigRevertInterface::IMPORT, $event);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function revert($type, $name) {
    // Read the config from the file. Note: Do not call getFromExtension() here
    // because we need $full_name below.
    $value = FALSE;
    $full_name = $this->getFullName($type, $name);
    if ($full_name) {
      $value = $this->extensionConfigStorage->read($full_name);
      if (!$value) {
        $value = $this->extensionOptionalConfigStorage->read($full_name);
      }
    }
    if (!$value) {
      return FALSE;
    }

    // Make sure the configuration exists currently in active storage.
    if (!$this->activeConfigStorage->read($full_name)) {
      return FALSE;
    }

    // Load the current config and replace the value, retaining the config
    // hash (which is part of the _core config key's value).
    if ($type == 'system.simple') {
      $config = $this->configFactory->getEditable($full_name);
      $core = $config->get('_core');
      $config
        ->setData($value)
        ->set('_core', $core)
        ->save();
    }
    else {
      $definition = $this->entityManager->getDefinition($type);
      $id_key = $definition->getKey('id');
      $id = $value[$id_key];
      $entity_storage = $this->entityManager->getStorage($type);
      $entity = $entity_storage->load($id);
      $core = $entity->get('_core');
      $entity = $entity_storage->updateFromStorageRecord($entity, $value);
      $entity->set('_core', $core);
      $entity->save();
    }

    // Trigger an event notifying of this change.
    $event = new ConfigRevertEvent($type, $name);
    $this->dispatcher->dispatch(ConfigRevertInterface::REVERT, $event);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($type, $name) {
    $config = FALSE;
    $full_name = $this->getFullName($type, $name);
    if ($full_name) {
      // Make sure the configuration exists currently in active storage.
      if (!$this->activeConfigStorage->read($full_name)) {
        return FALSE;
      }
      $config = $this->configFactory->getEditable($full_name);
    }
    if (!$config) {
      return FALSE;
    }
    $config->delete();

    // Trigger an event notifying of this change.
    $event = new ConfigRevertEvent($type, $name);
    $this->dispatcher->dispatch(ConfigDeleteInterface::DELETE, $event);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromActive($type, $name) {
    $full_name = $this->getFullName($type, $name);
    if ($full_name) {
      return $this->activeConfigStorage->read($full_name);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromExtension($type, $name) {
    $value = FALSE;
    $full_name = $this->getFullName($type, $name);
    if ($full_name) {
      $value = $this->extensionConfigStorage->read($full_name);
      if (!$value) {
        $value = $this->extensionOptionalConfigStorage->read($full_name);
      }
    }
    return $value;
  }

  /**
   * Returns the full name of a config item.
   *
   * @param string $type
   *   The config type, or '' to indicate $name is already prefixed.
   * @param string $name
   *   The config name, without prefix.
   *
   * @return string
   *   The config item's full name, or FALSE if there is an error.
   */
  protected function getFullName($type, $name) {
    if ($type == 'system.simple' || !$type) {
      return $name;
    }

    $definition = $this->entityManager->getDefinition($type);
    if ($definition) {
      $prefix = $definition->getConfigPrefix() . '.';
      return $prefix . $name;
    }
    else {
      return FALSE;
    }
  }

}
