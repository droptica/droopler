<?php

namespace Drupal\search_api\ParseMode;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages parse mode plugins.
 *
 * @see \Drupal\search_api\Annotation\SearchApiParseMode
 * @see \Drupal\search_api\ParseMode\ParseModeInterface
 * @see \Drupal\search_api\ParseMode\ParseModePluginBase
 * @see plugin_api
 */
class ParseModePluginManager extends DefaultPluginManager {

  /**
   * Constructs a ParseModePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api/parse_mode', $namespaces, $module_handler, 'Drupal\search_api\ParseMode\ParseModeInterface', 'Drupal\search_api\Annotation\SearchApiParseMode');

    $this->setCacheBackend($cache_backend, 'search_api_parse_mode');
    $this->alterInfo('search_api_parse_mode_info');
  }

  /**
   * Returns all known parse modes.
   *
   * @return \Drupal\search_api\ParseMode\ParseModeInterface[]
   *   An array of parse mode plugins, keyed by type identifier.
   */
  public function getInstances() {
    $parse_modes = [];

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (class_exists($definition['class'])) {
        $parse_modes[$plugin_id] = $this->createInstance($plugin_id);
      }
    }

    return $parse_modes;
  }

  /**
   * Returns all parse modes known by the Search API as an options list.
   *
   * @return string[]
   *   An associative array with all parse mode's IDs as keys, mapped to their
   *   translated labels.
   *
   * @see \Drupal\search_api\ParseMode\ParseModePluginManager::getInstances()
   */
  public function getInstancesOptions() {
    $parse_modes = [];
    foreach ($this->getInstances() as $id => $info) {
      $parse_modes[$id] = $info->label();
    }
    return $parse_modes;
  }

}
