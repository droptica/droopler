<?php

namespace Drupal\paragraphs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for paragraphs type behavior plugins.
 *
 * @ingroup paragraphs_behavior
 */
class ParagraphsBehaviorManager extends DefaultPluginManager {

  /**
   * Constructs a ParagraphsBehaviorManager object.
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
    parent::__construct('Plugin/paragraphs/Behavior', $namespaces, $module_handler, 'Drupal\paragraphs\ParagraphsBehaviorInterface', 'Drupal\paragraphs\Annotation\ParagraphsBehavior');
    $this->setCacheBackend($cache_backend, 'paragraphs_behavior_plugins');
    $this->alterInfo('paragraphs_behavior_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions =  parent::getDefinitions();
    uasort($definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $definitions;
  }

  /**
   * Gets the applicable behavior plugins.
   *
   * Loop over the plugin definitions, check the applicability of each one of
   * them and return the array of the applicable plugins.
   *
   * @return array
   *   The applicable behavior plugins.
   */
  public function getApplicableDefinitions($paragraphs_type) {
    $definitions = $this->getDefinitions();
    $applicable_plugins = [];
    foreach ($definitions as $key => $definition) {
      if ($definition['class']::isApplicable($paragraphs_type)) {
        $applicable_plugins[$key] = $definition;
      }
    }
    return $applicable_plugins;
  }

}
