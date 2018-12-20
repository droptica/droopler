<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * Defines a filter for filtering on taxonomy term references.
 *
 * Note: The plugin annotation below is not misspelled. Due to dependency
 * problems, the plugin is not defined here but in
 * search_api_views_plugins_filter_alter().
 *
 * @ingroup views_filter_handlers
 *
 * ViewsFilter("search_api_term")
 *
 * @see search_api_views_plugins_filter_alter()
 */
class SearchApiTerm extends TaxonomyIndexTid {

  use SearchApiFilterTrait;

}
