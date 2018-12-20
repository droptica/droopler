<?php

namespace Drupal\facets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * The facet entity.
 */
interface FacetInterface extends ConfigEntityInterface {

  /**
   * Sets the facet widget definition.
   *
   * @param string $id
   *   The widget plugin id.
   * @param array $configuration
   *   (optional) The facet widget plugin configuration. If missed, the default
   *   plugin configuration will be filled.
   */
  public function setWidget($id, array $configuration = NULL);

  /**
   * Returns the facet widget definition.
   *
   * @return array
   *   An associative array with the following structure:
   *   - id: The widget plugin id as a string.
   *   - config: The widget configuration as an array.
   */
  public function getWidget();

  /**
   * Returns the facet widget instance.
   *
   * @return \Drupal\facets\Widget\WidgetPluginBase
   *   The plugin instance
   */
  public function getWidgetInstance();

  /**
   * Sets the facet hierarchy definition.
   *
   * @param string $id
   *   The hierarchy plugin id.
   * @param array $configuration
   *   (optional) The facet hierarchy plugin configuration. When empty, the
   *   default plugin configuration will be used.
   */
  public function setHierarchy($id, array $configuration = NULL);

  /**
   * Returns the facet hierarchy definition.
   *
   * @return array
   *   An associative array with the following structure:
   *   - id: The hierarchy plugin id as a string.
   *   - config: The widget configuration as an array.
   */
  public function getHierarchy();

  /**
   * Returns the facet hierarchy instance.
   *
   * @return \Drupal\facets\Hierarchy\HierarchyPluginBase
   *   The plugin instance
   */
  public function getHierarchyInstance();

  /**
   * Returns field identifier.
   *
   * @return string
   *   The field identifier of this facet.
   */
  public function getFieldIdentifier();

  /**
   * Sets field identifier.
   *
   * @param string $field_identifier
   *   The field identifier of this facet.
   */
  public function setFieldIdentifier($field_identifier);

  /**
   * Returns the field alias used to identify the facet in the url.
   *
   * @return string
   *   The field alias for the facet.
   */
  public function getFieldAlias();

  /**
   * Returns the field name of the facet as used in the index.
   *
   * @return string
   *   The name of the facet.
   */
  public function getName();

  /**
   * Returns the name of the facet for use in the URL.
   *
   * @return string
   *   The name of the facet for use in the URL.
   */
  public function getUrlAlias();

  /**
   * Sets the name of the facet for use in the URL.
   *
   * @param string $url_alias
   *   The name of the facet for use in the URL.
   */
  public function setUrlAlias($url_alias);

  /**
   * Sets an item with value to active.
   *
   * @param string $value
   *   An item that is active.
   */
  public function setActiveItem($value);

  /**
   * Returns all the active items in the facet.
   *
   * @return mixed
   *   An array containing all active items.
   */
  public function getActiveItems();

  /**
   * Overwrites the active items.
   *
   * @param array $values
   *   A list of values.
   */
  public function setActiveItems(array $values);

  /**
   * Checks if a value is active.
   *
   * @param string $value
   *   The value to be checked.
   *
   * @return bool
   *   Is an active value.
   */
  public function isActiveValue($value);

  /**
   * Returns the show_only_one_result option.
   *
   * @return bool
   *   Show only one result.
   */
  public function getShowOnlyOneResult();

  /**
   * Sets the show_only_one_result option.
   *
   * @param bool $show_only_one_result
   *   Show only one result.
   */
  public function setShowOnlyOneResult($show_only_one_result);

  /**
   * Returns the result for the facet.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   The results of the facet.
   */
  public function getResults();

  /**
   * Sets the results for the facet.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The results of the facet.
   */
  public function setResults(array $results);

  /**
   * Returns the query type instance.
   *
   * @return string
   *   The query type plugin being used.
   */
  public function getQueryType();

  /**
   * Returns the query operator.
   *
   * @return string
   *   The query operator being used.
   */
  public function getQueryOperator();

  /**
   * Returns the limit number for facet items.
   */
  public function getHardLimit();

  /**
   * Returns the data definition from the facet field.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A typed data definition.
   */
  public function getDataDefinition();

  /**
   * Returns the value of the exclude boolean.
   *
   * This will return true when the current facet's value should be exclusive
   * from the search rather than inclusive.
   * When this returns TRUE, the operator will be "<>" instead of "=".
   *
   * @return bool
   *   A boolean flag indicating if search should exlude selected facets
   */
  public function getExclude();

  /**
   * Returns the value of the use_hierarchy boolean.
   *
   * This will return true when the results in the facet should be rendered in
   * a hierarchical structure.
   *
   * @return bool
   *   A boolean flag indicating if results should be rendered using hierarchy.
   */
  public function getUseHierarchy();

  /**
   * Sets the use_hierarchy.
   *
   * @param bool $use_hierarchy
   *   A boolean flag indicating if results should be rendered using hierarchy.
   */
  public function setUseHierarchy($use_hierarchy);

  /**
   * Returns the value of the expand_hierarchy boolean.
   *
   * This will return true when the results in the facet should be expanded in
   * a hierarchical structure, regardless of active state.
   *
   * @return bool
   *   Wether or not results should always be expanded using hierarchy.
   */
  public function getExpandHierarchy();

  /**
   * Sets the expand_hierarchy.
   *
   * @param bool $expand_hierarchy
   *   Wether or not results should always be expanded using hierarchy.
   */
  public function setExpandHierarchy($expand_hierarchy);

  /**
   * Returns the value of the enable_parent_when_child_gets_disabled boolean.
   *
   * This will return true when the parent item in the facet should be enabled
   * in an hierarchical structure, when a child facet item gets disabled.
   *
   * @return bool
   *   Wether or not parents should be enabled when a child gets disabled.
   */
  public function getEnableParentWhenChildGetsDisabled();

  /**
   * Sets the enable_parent_when_child_gets_disabled.
   *
   * @param bool $enable_parent_when_child_gets_disabled
   *   Wether or not parents should be enabled when a child gets disabled.
   */
  public function setEnableParentWhenChildGetsDisabled($enable_parent_when_child_gets_disabled);

  /**
   * Sets a string representation of the Facet source plugin.
   *
   * This is usually the name of the Search-api view.
   *
   * @param string $facet_source_id
   *   The facet source id.
   */
  public function setFacetSourceId($facet_source_id);

  /**
   * Sets the query operator.
   *
   * @param string $operator
   *   The query operator being used.
   */
  public function setQueryOperator($operator);

  /**
   * Sets the hard limit of facet items.
   *
   * @param int $limit
   *   Hard limit of the facet.
   */
  public function setHardLimit($limit);

  /**
   * Sets the exclude.
   *
   * @param bool $exclude
   *   A boolean flag indicating if search should exclude selected facets.
   */
  public function setExclude($exclude);

  /**
   * Returns the Facet source id.
   *
   * @return string
   *   The id of the facet source.
   */
  public function getFacetSourceId();

  /**
   * Returns the plugin instance of a facet source.
   *
   * @return \Drupal\facets\FacetSource\FacetSourcePluginInterface|null
   *   The plugin instance for the facet source.
   */
  public function getFacetSource();

  /**
   * Returns the facet source configuration object.
   *
   * @return \Drupal\facets\FacetSourceInterface
   *   A facet source configuration object.
   */
  public function getFacetSourceConfig();

  /**
   * Loads the facet sources for this facet.
   *
   * @param bool $only_enabled
   *   Only return enabled facet sources.
   *
   * @return \Drupal\facets\FacetSource\FacetSourcePluginInterface[]
   *   An array of facet sources.
   */
  public function getFacetSources($only_enabled = TRUE);

  /**
   * Returns an array of processors with their configuration.
   *
   * @param bool $only_enabled
   *   Only return enabled processors.
   *
   * @return \Drupal\facets\Processor\ProcessorInterface[]
   *   An array of processors.
   */
  public function getProcessors($only_enabled = TRUE);

  /**
   * Loads this facets processors for a specific stage.
   *
   * @param string $stage
   *   The stage for which to return the processors. One of the
   *   \Drupal\facets\Processor\ProcessorInterface::STAGE_* constants.
   * @param bool $only_enabled
   *   (optional) If FALSE, also include disabled processors. Otherwise, only
   *   load enabled ones.
   *
   * @return \Drupal\facets\Processor\ProcessorInterface[]
   *   An array of all enabled (or available, if if $only_enabled is FALSE)
   *   processors that support the given stage, ordered by the weight for that
   *   stage.
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE);

  /**
   * Retrieves this facets's processor configs.
   *
   * @return array
   *   An array of processors and their configs.
   */
  public function getProcessorConfigs();

  /**
   * Sets the "only visible when facet source is visible" boolean flag.
   *
   * @param bool $only_visible_when_facet_source_is_visible
   *   A boolean flag indicating if the facet should be hidden on a page that
   *   does not show the facet source.
   */
  public function setOnlyVisibleWhenFacetSourceIsVisible($only_visible_when_facet_source_is_visible);

  /**
   * Returns the "only visible when facet source is visible" boolean flag.
   *
   * @return bool
   *   True when the facet is only shown on a page with the facet source.
   */
  public function getOnlyVisibleWhenFacetSourceIsVisible();

  /**
   * Adds a processor for this facet.
   *
   * @param array $processor
   *   An array definition for a processor.
   */
  public function addProcessor(array $processor);

  /**
   * Removes a processor for this facet.
   *
   * @param string $processor_id
   *   The plugin id of the processor.
   */
  public function removeProcessor($processor_id);

  /**
   * Defines the no-results behavior.
   *
   * @param array $behavior
   *   The definition of the behavior.
   */
  public function setEmptyBehavior(array $behavior);

  /**
   * Returns the defined no-results behavior or NULL if none defined.
   *
   * @return array|null
   *   The behavior definition or NULL.
   */
  public function getEmptyBehavior();

  /**
   * Returns the weight of the facet.
   */
  public function getWeight();

  /**
   * Sets the weight of the facet.
   *
   * @param int $weight
   *   Weight of the facet.
   */
  public function setWeight($weight);

  /**
   * Sets the minimum count of the result to show.
   *
   * @param int $min_count
   *   Minimum count.
   */
  public function setMinCount($min_count);

  /**
   * Returns the minimum count of the result to show.
   *
   * @return int
   *   Minimum count.
   */
  public function getMinCount();

}
