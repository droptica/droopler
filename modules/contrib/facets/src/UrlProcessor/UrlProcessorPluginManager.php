<?php

namespace Drupal\facets\UrlProcessor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\Annotation\FacetsUrlProcessor;

/**
 * Manages URL processor plugins.
 *
 * @see \Drupal\facets\Annotation\FacetsProcessor
 * @see \Drupal\facets\Processor\ProcessorInterface
 * @see \Drupal\facets\Processor\ProcessorPluginBase
 * @see plugin_api
 */
class UrlProcessorPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets/url_processor', $namespaces, $module_handler, UrlProcessorInterface::class, FacetsUrlProcessor::class);
    $this->alterInfo('facets_url_processors_info');
    $this->setCacheBackend($cache_backend, 'facets_url_processors');
  }

}
