<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class UrlGeneratorManager
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 */
class UrlGeneratorManager extends DefaultPluginManager {

  /**
   * UrlGeneratorManager constructor.
   * @param \Traversable $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/simple_sitemap/UrlGenerator',
      $namespaces,
      $module_handler,
      'Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorInterface',
      'Drupal\simple_sitemap\Annotation\UrlGenerator'
    );

    $this->alterInfo('simple_sitemap_url_generators');
    $this->setCacheBackend($cache_backend, 'simple_sitemap:url_generator');
  }
}
