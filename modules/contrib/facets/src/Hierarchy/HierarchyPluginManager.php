<?php

namespace Drupal\facets\Hierarchy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\Annotation\FacetsHierarchy;

/**
 * Manages Hierarchy plugins.
 *
 * @see \Drupal\facets\Annotation\FacetsHierarchy
 * @see \Drupal\facets\Hierarchy\HierarchyInterface
 * @see plugin_api
 */
class HierarchyPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/hierarchy', $namespaces, $module_handler, HierarchyInterface::class, FacetsHierarchy::class);
    $this->setCacheBackend($cache_backend, 'facets_hierarchy');
  }

}
