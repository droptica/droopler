<?php

declare(strict_types=1);

namespace Drupal\d_media\Service;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\d_media\Annotation\VideoEmbedProvider;
use Drupal\d_media\Plugin\Provider\ProviderPluginInterface;

/**
 * Gathers the video-embed provider plugins.
 */
class ProviderManager extends DefaultPluginManager implements ProviderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/Provider',
      $namespaces,
      $module_handler,
      ProviderPluginInterface::class,
      VideoEmbedProvider::class,
    );
    $this->setCacheBackend($cache_backend, 'd_media_providers');
  }

  /**
   * {@inheritdoc}
   */
  public function filterApplicableDefinitions(array $definitions, string $user_input): array|false {
    foreach ($definitions as $definition) {
      /** @var class-string<\Drupal\d_media\Plugin\Provider\ProviderPluginInterface> $class */
      $class = $definition['class'];
      if ($class::isApplicable($user_input)) {
        return $definition;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProviderFromInput(string $input): ProviderPluginInterface|false {
    $definition = $this->loadDefinitionFromInput($input);
    if ($definition === FALSE) {
      return FALSE;
    }
    /** @var \Drupal\d_media\Plugin\Provider\ProviderPluginInterface $plugin */
    $plugin = $this->createInstance($definition['id'], ['input' => $input]);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionFromInput(string $input): array|false {
    return $this->filterApplicableDefinitions($this->getDefinitions(), $input);
  }

}
