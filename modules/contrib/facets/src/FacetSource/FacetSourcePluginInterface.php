<?php

namespace Drupal\facets\FacetSource;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\facets\FacetInterface;

/**
 * Describes a source for facet items.
 *
 * A facet source is used to abstract the data source where facets can be added
 * to. A good example of this is a Search API view. There are other possible
 * facet data sources, these all implement the FacetSourcePluginInterface.
 *
 * @see plugin_api
 */
interface FacetSourcePluginInterface extends PluginFormInterface, DependentPluginInterface {

  /**
   * Fills the facet entities with results from the facet source.
   *
   * @param \Drupal\facets\FacetInterface[] $facets
   *   The configured facets.
   */
  public function fillFacetsWithResults(array $facets);

  /**
   * Returns the allowed query types for a given facet for the facet source.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we should get query types for.
   *
   * @return string[]
   *   array of allowed query types
   *
   * @throws \Drupal\facets\Exception\Exception
   *   An error when no query types are found.
   */
  public function getQueryTypesForFacet(FacetInterface $facet);

  /**
   * Returns the path of the facet source, used to build the facet url.
   *
   * @return string
   *   The path.
   */
  public function getPath();

  /**
   * Returns the number of results that were found for this search.
   *
   * @return string
   *   The path of the facet.
   */
  public function getCount();

  /**
   * Returns true if the Facet source is being rendered in the current request.
   *
   * This function will define if all facets for this facet source are shown
   * when facet source visibility: "being rendered" is configured in the facet
   * visibility settings.
   *
   * @return bool
   *   True when the facet is rendered on the same page.
   */
  public function isRenderedInCurrentRequest();

  /**
   * Returns an array of fields that are defined on the facet source.
   *
   * This returns an array of fields that are defined on the source. This array
   * is keyed by the field's machine name and has values of the field's label.
   *
   * @return array
   *   An array of available fields.
   */
  public function getFields();

  /**
   * Sets the search keys, or query text, submitted by the user.
   *
   * @param string $keys
   *   The search keys, or query text, submitted by the user.
   *
   * @return self
   *   An instance of this class.
   */
  public function setSearchKeys($keys);

  /**
   * Returns the search keys, or query text, submitted by the user.
   *
   * @return string
   *   The search keys, or query text, submitted by the user.
   */
  public function getSearchKeys();

  /**
   * Returns a single field's data definition from the facet source.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A typed data definition.
   */
  public function getDataDefinition($field_name);

  /**
   * Builds and returns an extra renderable array for this facet block plugin.
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay
   */
  public function buildFacet();

}
