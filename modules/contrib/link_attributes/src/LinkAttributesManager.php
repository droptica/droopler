<?php

namespace Drupal\link_attributes;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the link_attributes plugin manager.
 */
class LinkAttributesManager extends DefaultPluginManager implements PluginManagerInterface {

  /**
   * Provides default values for all link_attributes plugins.
   *
   * @var array
   */
  protected $defaults = [
    'title' => '',
    'type' => '',
    'description' => '',
  ];

  /**
   * Constructs a LinkAttributesManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->alterInfo('link_attributes_plugin');
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'link_attributes', array('link_attributes'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('link_attributes', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('title');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Make sure each plugin definition had at least a field type.
    if (empty($definition['type'])) {
      $definition['type'] = 'textfield';
    }
  }

}
