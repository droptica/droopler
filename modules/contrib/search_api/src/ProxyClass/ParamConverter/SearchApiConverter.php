<?php

namespace Drupal\search_api\ProxyClass\ParamConverter;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a proxy class for \Drupal\search_api\ParamConverter\SearchApiConverter.
 *
 * This file was generated via:
 * @code
 * php core/scripts/generate-proxy-class.php 'Drupal\search_api\ParamConverter\SearchApiConverter' modules/search_api/src/
 * @endcode
 *
 * @see \Drupal\Component\ProxyBuilder\ProxyBuilder
 */
class SearchApiConverter implements ParamConverterInterface {

  use DependencySerializationTrait;

  /**
   * The id of the original proxied service.
   *
   * @var string
   */
  protected $drupalProxyOriginalServiceId;

  /**
   * The real proxied service, after it was lazy loaded.
   *
   * @var \Drupal\search_api\ParamConverter\SearchApiConverter
   */
  protected $service;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a ProxyClass Drupal proxy object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param string $drupal_proxy_original_service_id
   *   The service ID of the original service.
   */
  public function __construct(ContainerInterface $container, $drupal_proxy_original_service_id) {
    $this->container = $container;
    $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
  }

  /**
   * Lazy loads the real service from the container.
   *
   * @return \Drupal\search_api\ParamConverter\SearchApiConverter
   *   Returns the constructed real service.
   */
  protected function lazyLoadItself() {
    if (!isset($this->service)) {
      $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
    }

    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->lazyLoadItself()->convert($value, $definition, $name, $defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $this->lazyLoadItself()->applies($definition, $name, $route);
  }

}
