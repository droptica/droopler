<?php

namespace Drupal\d_media\Service;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Gathers the provider plugins.
 */
class ProviderManager extends DefaultPluginManager implements ProviderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Provider', $namespaces, $module_handler, 'Drupal\d_media\Plugin\Provider\ProviderPluginInterface', 'Drupal\d_media\Annotation\VideoEmbedProvider');
  }

  /**
   * {@inheritdoc}
   */
  public function filterApplicableDefinitions(array $definitions, $user_input) {
    foreach ($definitions as $definition) {
      $is_applicable = $definition['class']::isApplicable($user_input);
      if ($is_applicable) {
        return $definition;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProviderFromInput($input) {
    $definition = $this->loadDefinitionFromInput($input);
    return $definition ? $this->createInstance($definition['id'], ['input' => $input]) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionFromInput($input) {
    return $this->filterApplicableDefinitions($this->getDefinitions(), $input);
  }

}
