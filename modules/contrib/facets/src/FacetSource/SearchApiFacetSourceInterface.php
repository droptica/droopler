<?php

namespace Drupal\facets\FacetSource;

/**
 * A facet source that uses Search API as a base.
 */
interface SearchApiFacetSourceInterface extends FacetSourcePluginInterface {

  /**
   * Returns the search_api index.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The Search API index.
   */
  public function getIndex();

  /**
   * Retrieves the Search API display plugin associated with this facet source.
   *
   * @return \Drupal\search_api\Display\DisplayInterface
   *   The Search API display plugin associated with this facet source.
   */
  public function getDisplay();

  /**
   * Retrieves the Views entity with the correct display set.
   *
   * This returns NULL when the facet source is not based on views. If it is, it
   * returns a ViewsExecutable plugin with the correct display already set.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   NULL when the view can't be found or loaded, the view with preset display
   *   otherwise.
   */
  public function getViewsDisplay();

}
