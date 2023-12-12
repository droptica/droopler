<?php

declare(strict_types = 1);

namespace Drupal\d_media\Service;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\d_media\Plugin\Provider\ProviderPluginInterface;

/**
 * Gathers the provider plugins.
 */
class ProviderManager extends DefaultPluginManager implements ProviderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Provider', $namespaces, $module_handler, 'Drupal\d_media\Plugin\Provider\ProviderPluginInterface', 'Drupal\d_media\Annotation\VideoEmbedProvider');
  }

  /**
   * {@inheritdoc}
   */
  public function filterApplicableDefinitions(array $definitions, $user_input): ProviderPluginInterface|false {
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
  public function loadProviderFromInput($input): ProviderPluginInterface|false {
    $definition = $this->loadDefinitionFromInput($input);
    return $definition ? $this->createInstance($definition['id'], ['input' => $input]) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionFromInput($input): ProviderPluginInterface|false {
    return $this->filterApplicableDefinitions($this->getDefinitions(), $input);
  }

}
