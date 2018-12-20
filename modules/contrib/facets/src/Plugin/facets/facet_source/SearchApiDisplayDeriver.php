<?php

namespace Drupal\facets\Plugin\facets\facet_source;

use Drupal\Component\Plugin\PluginBase;
use Drupal\facets\FacetSource\FacetSourceDeriverBase;
use Drupal\search_api\IndexInterface;

/**
 * Derives a facet source plugin definition for every Search API display plugin.
 *
 * This facet source supports all search api display sources.
 *
 * @see \Drupal\facets\Plugin\facets\facet_source\SearchApi
 */
class SearchApiDisplayDeriver extends FacetSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];
    $plugin_derivatives = [];

    $display_plugin_manager = $this->getSearchApiDisplayPluginManager();
    foreach ($display_plugin_manager->getDefinitions() as $display_id => $display_definition) {
      // If 'index' is not set on the plugin, we can't load the index.
      if (!isset($display_definition['index'])) {
        continue;
      }

      /** @var \Drupal\search_api\Display\DisplayInterface $display */
      $display = $display_plugin_manager->createInstance($display_id);

      $index = $display->getIndex();

      // If we can't reliably load the index, we should just cancel trying to
      // create a derivative for this display.
      if (!$index instanceof IndexInterface) {
        continue;
      }

      // Get the server linked to the index.
      $server = $index->getServerInstance();

      // If facets are not supported by the server, don't actually add this to
      // the list of plugins.
      if (empty($server) || !$server->supportsFeature('search_api_facets')) {
        continue;
      }

      $machine_name = str_replace(':', '__', $display->getPluginId());
      $plugin_derivatives[$machine_name] = [
        'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
        'display_id' => $display_id,
        'label' => $display->label(),
        'description' => $display->getDescription(),
      ] + $base_plugin_definition;
    }

    uasort($plugin_derivatives, [$this, 'compareDerivatives']);

    $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    return $this->derivatives[$base_plugin_id];
  }

}
