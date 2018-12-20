<?php

namespace Drupal\search_api\Utility;

use Drupal\search_api\IndexInterface;

/**
 * Provides an interface for the plugin helper service.
 */
interface PluginHelperInterface {

  /**
   * Creates a datasource plugin object for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugin.
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface
   *   The new datasource plugin object.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  public function createDatasourcePlugin(IndexInterface $index, $plugin_id, array $configuration = []);

  /**
   * Creates a processor plugin object for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugin.
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api\processor\ProcessorInterface
   *   The new processor plugin object.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  public function createProcessorPlugin(IndexInterface $index, $plugin_id, array $configuration = []);

  /**
   * Creates a processor plugin object for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugin.
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api\tracker\TrackerInterface
   *   The new tracker plugin object.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  public function createTrackerPlugin(IndexInterface $index, $plugin_id, array $configuration = []);

  /**
   * Creates multiple datasource plugin objects for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugins.
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the plugins to create, or NULL to create instances
   *   for all known plugins of this type.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the index's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface[]
   *   The created datasource plugin objects.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createDatasourcePlugins(IndexInterface $index, array $plugin_ids = NULL, array $configurations = []);

  /**
   * Creates multiple processor plugin objects for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugins.
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the processors to create, or NULL to create
   *   instances for all known processors that support the given index.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the index's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api\processor\ProcessorInterface[]
   *   The created processor plugin objects.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createProcessorPlugins(IndexInterface $index, array $plugin_ids = NULL, array $configurations = []);

  /**
   * Creates multiple tracker plugin objects for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to create the plugins.
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the plugins to create, or NULL to create instances
   *   for all known plugins of this type.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the index's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api\tracker\TrackerInterface[]
   *   The created tracker plugin objects.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createTrackerPlugins(IndexInterface $index, array $plugin_ids = NULL, array $configurations = []);

}
