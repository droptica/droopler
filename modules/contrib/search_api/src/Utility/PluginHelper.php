<?php

namespace Drupal\search_api\Utility;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\search_api\Datasource\DatasourcePluginManager;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginManager;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Tracker\TrackerPluginManager;

/**
 * Provides methods for creating search plugins.
 */
class PluginHelper implements PluginHelperInterface {

  /**
   * The datasource plugin manager.
   *
   * @var \Drupal\search_api\Datasource\DatasourcePluginManager
   */
  protected $datasourcePluginManager;

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\search_api\processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * The tracker plugin manager.
   *
   * @var \Drupal\search_api\tracker\TrackerPluginManager
   */
  protected $trackerPluginManager;

  /**
   * Constructs a PluginHelper object.
   *
   * @param \Drupal\search_api\Datasource\DatasourcePluginManager $datasource_plugin_manager
   *   The datasource plugin manager.
   * @param \Drupal\search_api\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The processor plugin manager.
   * @param \Drupal\search_api\Tracker\TrackerPluginManager $tracker_plugin_manager
   *   The tracker plugin manager.
   */
  public function __construct(DatasourcePluginManager $datasource_plugin_manager, ProcessorPluginManager $processor_plugin_manager, TrackerPluginManager $tracker_plugin_manager) {
    $this->datasourcePluginManager = $datasource_plugin_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->trackerPluginManager = $tracker_plugin_manager;
  }

  /**
   * Creates a plugin object for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugins.
   * @param string $type
   *   The type of plugin to create: "datasource", "processor" or "tracker".
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api\Plugin\IndexPluginInterface
   *   The new plugin object.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  protected function createIndexPlugin(IndexInterface $index, $type, $plugin_id, array $configuration = []) {
    if (!isset($this->{$type . "PluginManager"})) {
      throw new SearchApiException("Unknown plugin type '$type'");
    }
    try {
      $configuration['#index'] = $index;
      return $this->{$type . "PluginManager"}->createInstance($plugin_id, $configuration);
    }
    catch (PluginException $e) {
      throw new SearchApiException("Unknown $type plugin with ID '$plugin_id'");
    }
  }

  /**
   * Creates multiple plugin objects for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugins.
   * @param string $type
   *   The type of plugin to create: "datasource", "processor" or "tracker".
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the plugins to create, or NULL to create instances
   *   for all known plugins of this type.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the index's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api\Plugin\IndexPluginInterface[]
   *   The created plugin objects.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown $type or plugin ID is given.
   */
  protected function createIndexPlugins(IndexInterface $index, $type, array $plugin_ids = NULL, array $configurations = []) {
    if (!isset($this->{$type . "PluginManager"})) {
      throw new SearchApiException("Unknown plugin type '$type'");
    }
    if ($plugin_ids === NULL) {
      $plugin_ids = array_keys($this->{$type . "PluginManager"}->getDefinitions());
    }

    $plugins = [];
    $index_settings = $index->get($type . '_settings');
    foreach ($plugin_ids as $plugin_id) {
      $configuration = [];
      if (isset($configurations[$plugin_id])) {
        $configuration = $configurations[$plugin_id];
      }
      elseif (isset($index_settings[$plugin_id])) {
        $configuration = $index_settings[$plugin_id];
      }
      $plugins[$plugin_id] = $this->createIndexPlugin($index, $type, $plugin_id, $configuration);
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function createDatasourcePlugin(IndexInterface $index, $plugin_id, array $configuration = []) {
    return $this->createIndexPlugin($index, 'datasource', $plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createProcessorPlugin(IndexInterface $index, $plugin_id, array $configuration = []) {
    return $this->createIndexPlugin($index, 'processor', $plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createTrackerPlugin(IndexInterface $index, $plugin_id, array $configuration = []) {
    return $this->createIndexPlugin($index, 'tracker', $plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createDatasourcePlugins(IndexInterface $index, array $plugin_ids = NULL, array $configuration = []) {
    return $this->createIndexPlugins($index, 'datasource', $plugin_ids, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createProcessorPlugins(IndexInterface $index, array $plugin_ids = NULL, array $configuration = []) {
    return $this->createIndexPlugins($index, 'processor', $plugin_ids, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createTrackerPlugins(IndexInterface $index, array $plugin_ids = NULL, array $configuration = []) {
    return $this->createIndexPlugins($index, 'tracker', $plugin_ids, $configuration);
  }

}
