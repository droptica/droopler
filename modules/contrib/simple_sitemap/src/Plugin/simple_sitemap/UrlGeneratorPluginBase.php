<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UrlGeneratorPluginBase
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap
 */
abstract class UrlGeneratorPluginBase extends SimplesitemapPluginBase  implements ConfigurablePluginInterface {

  /**
   * @var array
   */
  public $settings = [];

  /**
   * @var bool
   */
  public $enabled = TRUE;

  /**
   * @var int
   */
  public $weight = 0;

  /**
   * @var string
   */
  public $provider;

  /**
   * UrlGeneratorPluginBase constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['module' => 'simple_sitemap'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->enabled,
      'weight' => $this->weight,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['enabled'])) {
      $this->enabled = (bool) $configuration['enabled'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled' => $this->pluginDefinition['enabled'],
      'weight' => isset($this->pluginDefinition['weight']) ? $this->pluginDefinition['weight'] : 0,
      'settings' => isset($this->pluginDefinition['settings']) ? $this->pluginDefinition['settings'] : [],
    ];
  }
}
