<?php

namespace Drupal\facets\QueryType;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\facets\Annotation\FacetsQueryType;

/**
 * Defines a plugin manager for query types.
 */
class QueryTypePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/query_type', $namespaces, $module_handler, QueryTypeInterface::class, FacetsQueryType::class);
  }

}
