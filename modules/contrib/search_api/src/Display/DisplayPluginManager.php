<?php

namespace Drupal\search_api\Display;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages display plugins.
 *
 * @see \Drupal\search_api\Annotation\SearchApiDisplay
 * @see \Drupal\search_api\Display\DisplayInterface
 * @see \Drupal\search_api\Display\DisplayPluginBase
 * @see plugin_api
 */
class DisplayPluginManager extends DefaultPluginManager implements DisplayPluginManagerInterface {

  /**
   * Static cache for the display plugins.
   *
   * @var \Drupal\search_api\Display\DisplayInterface[]|null
   *
   * @see \Drupal\search_api\Display\DisplayPluginManager::getInstances()
   */
  protected $displays = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api/display', $namespaces, $module_handler, 'Drupal\search_api\Display\DisplayInterface', 'Drupal\search_api\Annotation\SearchApiDisplay');
    $this->setCacheBackend($cache_backend, 'search_api_displays');
    $this->alterInfo('search_api_displays');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    if ($this->displays === NULL) {
      $this->displays = [];

      foreach ($this->getDefinitions() as $name => $display_definition) {
        if (class_exists($display_definition['class']) && empty($this->displays[$name])) {
          $display = $this->createInstance($name);
          $this->displays[$name] = $display;
        }
      }
    }

    return $this->displays;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();

    $this->discovery = NULL;
  }

}
