<?php

namespace Drupal\facets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\facets\Exception\Exception;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\Exception\InvalidQueryTypeException;
use Drupal\facets\FacetInterface;

/**
 * Defines the facet configuration entity.
 *
 * @ConfigEntityType(
 *   id = "facets_facet",
 *   label = @Translation("Facet"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\facets\FacetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\facets\Form\FacetSettingsForm",
 *       "edit" = "Drupal\facets\Form\FacetForm",
 *       "settings" = "Drupal\facets\Form\FacetSettingsForm",
 *       "clone" = "Drupal\facets\Form\FacetCloneForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer facets",
 *   config_prefix = "facet",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "url_alias",
 *     "weight",
 *     "min_count",
 *     "show_only_one_result",
 *     "field_identifier",
 *     "facet_source_id",
 *     "widget",
 *     "query_operator",
 *     "use_hierarchy",
 *     "expand_hierarchy",
 *     "enable_parent_when_child_gets_disabled",
 *     "hard_limit",
 *     "exclude",
 *     "only_visible_when_facet_source_is_visible",
 *     "processor_configs",
 *     "empty_behavior",
 *     "show_title"
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/facets",
 *     "add-form" = "/admin/config/search/facets/add-facet",
 *     "edit-form" = "/admin/config/search/facets/{facets_facet}/edit",
 *     "settings-form" = "/admin/config/search/facets/{facets_facet}/settings",
 *     "clone-form" = "/admin/config/search/facets/{facets_facet}/clone",
 *     "delete-form" = "/admin/config/search/facets/{facets_facet}/delete",
 *   }
 * )
 */
class Facet extends ConfigEntityBase implements FacetInterface {

  /**
   * The ID of the facet.
   *
   * @var string
   */
  protected $id;

  /**
   * A name to be displayed for the facet.
   *
   * @var string
   */
  protected $name;

  /**
   * The name for the parameter when used in the URL.
   *
   * @var string
   */
  protected $url_alias;

  /**
   * A string describing the facet.
   *
   * @var string
   */
  protected $description;

  /**
   * The widget plugin definition.
   *
   * @var array
   */
  protected $widget;

  /**
   * The widget plugin instance.
   *
   * @var \Drupal\facets\Widget\WidgetPluginBase
   */
  protected $widgetInstance;

  /**
   * The hierarchy definition.
   *
   * @var array
   */
  protected $hierarchy;

  /**
   * The hierarchy instance.
   *
   * @var \Drupal\facets\Hierarchy\HierarchyPluginBase
   */
  protected $hierarchy_processor;

  /**
   * The operator to hand over to the query, currently AND | OR.
   *
   * @var string
   */
  protected $query_operator;

  /**
   * Hard limit for the facet items.
   *
   * @var int
   */
  protected $hard_limit;

  /**
   * A boolean flag indicating if search should exclude selected facets.
   *
   * @var bool
   */
  protected $use_hierarchy = FALSE;

  /**
   * A boolean indicating if hierarchical items should always be expanded.
   *
   * @var bool
   */
  protected $expand_hierarchy = FALSE;

  /**
   * Wether or not parents should be enabled when a child gets disabled.
   *
   * @var bool
   */
  protected $enable_parent_when_child_gets_disabled = TRUE;

  /**
   * A boolean flag indicating if search should exclude selected facets.
   *
   * @var bool
   */
  protected $exclude;

  /**
   * The field identifier.
   *
   * @var string
   */
  protected $field_identifier;

  /**
   * The id of the facet source.
   *
   * @var string
   */
  protected $facet_source_id;

  /**
   * The facet source belonging to this facet.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginInterface
   *
   * @see getFacetSource()
   */
  protected $facet_source_instance = NULL;

  /**
   * The path all the links should point to.
   *
   * @var string
   */
  protected $path;

  /**
   * The results.
   *
   * @var \Drupal\facets\Result\ResultInterface[]
   */
  protected $results = [];

  /**
   * An array of active values.
   *
   * @var string[]
   */
  protected $active_values = [];

  /**
   * An array containing the facet source plugins.
   *
   * @var array
   */
  protected $facetSourcePlugins;

  /**
   * Cached information about the processors available for this facet.
   *
   * @var \Drupal\facets\Processor\ProcessorInterface[]|null
   *
   * @see loadProcessors()
   */
  protected $processors;

  /**
   * Configuration for the processors. This is an array of arrays.
   *
   * @var array
   */
  protected $processor_configs = [];

  /**
   * Is the facet only visible when the facet source is only visible.
   *
   * A boolean that defines whether or not the facet is only visible when the
   * facet source is visible.
   *
   * @var bool
   */
  protected $only_visible_when_facet_source_is_visible;

  /**
   * Determines if only one result can be selected in the facet at one time.
   *
   * @var bool
   */
  protected $show_only_one_result = FALSE;

  /**
   * The no-result configuration.
   *
   * @var string[]
   */
  protected $empty_behavior;

  /**
   * The widget plugin manager.
   *
   * @var \Drupal\facets\Widget\WidgetPluginManager
   */
  protected $widget_plugin_manager;

  /**
   * The hierarchy plugin manager.
   *
   * @var \Drupal\facets\Hierarchy\HierarchyPluginManager
   *   The hierarchy plugin manager.
   */
  protected $hierarchy_manager;

  /**
   * The facet source config object.
   *
   * @var \Drupal\Facets\FacetSourceInterface
   *   The facet source config object.
   */
  protected $facetSourceConfig;

  /**
   * The facet weight.
   *
   * @var int
   *   The weight of the facet.
   */
  protected $weight;

  /**
   * The minimum amount of results to show.
   *
   * @var int
   *   The minimum amount of results.
   */
  protected $min_count = 1;

  /**
   * Returns the widget plugin manager.
   *
   * @return \Drupal\facets\Widget\WidgetPluginManager
   *   The widget plugin manager.
   */
  public function getWidgetManager() {
    return $this->widget_plugin_manager ?: \Drupal::service('plugin.manager.facets.widget');
  }

  /**
   * Returns the hierarchy plugin manager.
   *
   * @return \Drupal\facets\Hierarchy\HierarchyPluginManager
   *   The hierarchy plugin manager.
   */
  public function getHierarchyManager() {
    return $this->hierarchy_manager ?: \Drupal::service('plugin.manager.facets.hierarchy');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidget($id, array $configuration = NULL) {
    if ($configuration === NULL) {
      $instance = $this->getWidgetManager()->createInstance($id);
      // Get the default configuration for this plugin.
      $configuration = $instance->getConfiguration();
    }
    $this->widget = ['type' => $id, 'config' => $configuration];

    // Unset the widget instance, if exists.
    unset($this->widgetInstance);
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return $this->widget;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetInstance() {
    if ($this->widget === NULL) {
      throw new InvalidProcessorException();
    }

    if (!isset($this->widgetInstance)) {
      $definition = $this->getWidget();
      $this->widgetInstance = $this->getWidgetManager()
        ->createInstance($definition['type'], (array) $definition['config']);
    }
    return $this->widgetInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function setHierarchy($id, array $configuration = NULL) {
    if ($configuration === NULL) {
      $instance = $this->getHierarchyManager()->createInstance($id);
      // Get the default configuration for this plugin.
      $configuration = $instance->getConfiguration();
    }
    $this->hierarchy = ['type' => $id, 'config' => $configuration];

    // Unset the hierarchy instance, if exists.
    unset($this->hierarchy_instance);
  }

  /**
   * {@inheritdoc}
   */
  public function getHierarchy() {
    // TODO: do not hardcode on taxonomy, make this configurable (or better,
    // autoselected depending field type).
    return ['type' => 'taxonomy', 'config' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function getHierarchyInstance() {
    if (!isset($this->hierarchy_instance)) {
      $definition = $this->getHierarchy();
      $this->hierarchy_instance = $this->getHierarchyManager()
        ->createInstance($definition['type'], (array) $definition['config']);
    }
    return $this->hierarchy_instance;
  }

  /**
   * Retrieves all processors supported by this facet.
   *
   * @return \Drupal\facets\Processor\ProcessorInterface[]
   *   The loaded processors, keyed by processor ID.
   */
  protected function loadProcessors() {
    if (is_array($this->processors)) {
      return $this->processors;
    }

    /* @var $processor_plugin_manager \Drupal\facets\Processor\ProcessorPluginManager */
    $processor_plugin_manager = \Drupal::service('plugin.manager.facets.processor');
    $processor_settings = $this->getProcessorConfigs();

    foreach ($processor_plugin_manager->getDefinitions() as $name => $processor_definition) {
      if (class_exists($processor_definition['class']) && empty($this->processors[$name])) {
        // Create our settings for this processor.
        $settings = empty($processor_settings[$name]['settings']) ? [] : $processor_settings[$name]['settings'];
        $settings['facet'] = $this;

        /* @var $processor \Drupal\facets\Processor\ProcessorInterface */
        $processor = $processor_plugin_manager->createInstance($name, $settings);
        $this->processors[$name] = $processor;
      }
      elseif (!class_exists($processor_definition['class'])) {
        \Drupal::logger('facets')
          ->warning('Processor @id specifies a non-existing @class.', [
            '@id' => $name,
            '@class' => $processor_definition['class'],
          ]);
      }
    }

    return $this->processors;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorConfigs() {
    return !empty($this->processor_configs) ? $this->processor_configs : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    $facet_source = $this->getFacetSource();
    if (is_null($facet_source)) {
      throw new Exception("No facet source defined for facet.");
    }

    $query_types = $facet_source->getQueryTypesForFacet($this);

    // Get the widget configured for this facet.
    /** @var \Drupal\facets\Widget\WidgetPluginInterface $widget */
    $widget = $this->getWidgetInstance();

    // Give the widget the chance to select a preferred query type. This is
    // needed for widget that have different query type. For example the need
    // for a range query.
    $widgetQueryType = $widget->getQueryType();

    // Allow widgets to also specify a query type.
    $processorQueryTypes = [];
    foreach ($this->getProcessors() as $processor) {
      $pqt = $processor->getQueryType();
      if ($pqt !== NULL) {
        $processorQueryTypes[] = $pqt;
      }
    }
    $processorQueryTypes = array_flip($processorQueryTypes);

    // The widget has made no decision and neither have the processors.
    if ($widgetQueryType === NULL && count($processorQueryTypes) === 0) {
      return $this->pickQueryType($query_types, 'string');
    }
    // The widget has made no decision but the processors have made 1 decision.
    if ($widgetQueryType === NULL && count($processorQueryTypes) === 1) {
      return $this->pickQueryType($query_types, key($processorQueryTypes));
    }
    // The widget has made a decision and the processors have not.
    if ($widgetQueryType !== NULL && count($processorQueryTypes) === 0) {
      return $this->pickQueryType($query_types, $widgetQueryType);
    }
    // The widget has made a decision and the processors have 1, being the same.
    if ($widgetQueryType !== NULL && count($processorQueryTypes) === 1 && key($processorQueryTypes) === $widgetQueryType) {
      return $this->pickQueryType($query_types, $widgetQueryType);
    }

    // Invalid choice.
    throw new InvalidQueryTypeException("Invalid query type combination in widget / processors. Widget: {$widgetQueryType}, Processors: " . implode(', ', array_keys($processorQueryTypes)) . ".");
  }

  /**
   * Choose the query type.
   *
   * @param array $allTypes
   *   An array of query type definitions.
   * @param string $type
   *   The chose query type.
   *
   * @return string
   *   The class name of the chose query type.
   *
   * @throws \Drupal\facets\Exception\InvalidQueryTypeException
   */
  protected function pickQueryType(array $allTypes, $type) {
    if (!isset($allTypes[$type])) {
      throw new InvalidQueryTypeException("Query type {$type} doesn't exist.");
    }
    return $allTypes[$type];
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryOperator($operator = '') {
    return $this->query_operator = $operator;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryOperator() {
    return $this->query_operator ?: 'or';
  }

  /**
   * {@inheritdoc}
   */
  public function setUseHierarchy($use_hierarchy) {
    return $this->use_hierarchy = $use_hierarchy;
  }

  /**
   * {@inheritdoc}
   */
  public function getUseHierarchy() {
    return $this->use_hierarchy;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpandHierarchy($expand_hierarchy) {
    return $this->expand_hierarchy = $expand_hierarchy;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpandHierarchy() {
    return $this->expand_hierarchy;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnableParentWhenChildGetsDisabled($enable_parent_when_child_gets_disabled) {
    return $this->enable_parent_when_child_gets_disabled = $enable_parent_when_child_gets_disabled;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnableParentWhenChildGetsDisabled() {
    return $this->enable_parent_when_child_gets_disabled;
  }

  /**
   * {@inheritdoc}
   */
  public function setHardLimit($limit) {
    return $this->hard_limit = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getHardLimit() {
    return $this->hard_limit ?: 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition() {
    return $this->getFacetSource()->getDataDefinition($this->field_identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function setExclude($exclude) {
    return $this->exclude = $exclude;
  }

  /**
   * {@inheritdoc}
   */
  public function getExclude() {
    return $this->exclude;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldAlias() {
    // For now, create the field alias based on the field identifier.
    $field_alias = preg_replace('/[:\/]+/', '_', $this->field_identifier);
    return $field_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveItem($value) {
    if (!in_array($value, $this->active_values)) {
      $this->active_values[] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveItems() {
    return $this->active_values;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveItems(array $values) {
    $this->active_values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldIdentifier() {
    return $this->field_identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldIdentifier($field_identifier) {
    $this->field_identifier = $field_identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlAlias() {
    return $this->url_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrlAlias($url_alias) {
    $this->url_alias = $url_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setFacetSourceId($facet_source_id) {
    $this->facet_source_id = $facet_source_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSourceId() {
    return $this->facet_source_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSource() {
    if (is_null($this->facet_source_instance) && $this->facet_source_id) {
      /* @var $facet_source_plugin_manager \Drupal\facets\FacetSource\FacetSourcePluginManager */
      $facet_source_plugin_manager = \Drupal::service('plugin.manager.facets.facet_source');
      if (!$facet_source_plugin_manager->hasDefinition($this->facet_source_id)) {
        return NULL;
      }
      $this->facet_source_instance = $facet_source_plugin_manager
        ->createInstance($this->facet_source_id, ['facet' => $this]);
    }

    return $this->facet_source_instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowOnlyOneResult() {
    return $this->show_only_one_result;
  }

  /**
   * {@inheritdoc}
   */
  public function setShowOnlyOneResult($show_only_one_result) {
    $this->show_only_one_result = $show_only_one_result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSourceConfig() {
    // Return the facet source config object, if it's already set on the facet.
    if ($this->facetSourceConfig instanceof FacetSource) {
      return $this->facetSourceConfig;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('facets_facet_source');
    $source_id = str_replace(':', '__', $this->facet_source_id);

    // Load and return the facet source config object from the storage.
    $facet_source = $storage->load($source_id);
    if ($facet_source instanceof FacetSource) {
      $this->facetSourceConfig = $facet_source;
      return $this->facetSourceConfig;
    }

    // We didn't have a facet source config entity yet for this facet source
    // plugin, so we create it on the fly.
    $facet_source = new FacetSource(
      [
        'id' => $source_id,
        'name' => $this->facet_source_id,
        'filter_key' => 'f',
        'url_processor' => 'query_string',
      ],
      'facets_facet_source'
    );

    return $facet_source;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * {@inheritdoc}
   */
  public function setResults(array $results) {
    $this->results = $results;
    // If there are active values,
    // set the results which are active to active.
    if (count($this->active_values)) {
      foreach ($this->results as $result) {
        if (in_array($result->getRawValue(), $this->active_values)) {
          $result->setActiveState(TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveValue($value) {
    $is_active = FALSE;
    if (in_array($value, $this->active_values)) {
      $is_active = TRUE;
    }
    return $is_active;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSources($only_enabled = FALSE) {
    if (!isset($this->facetSourcePlugins)) {
      $this->facetSourcePlugins = [];

      /* @var $facet_source_plugin_manager \Drupal\facets\FacetSource\FacetSourcePluginManager */
      $facet_source_plugin_manager = \Drupal::service('plugin.manager.facets.facet_source');

      foreach ($facet_source_plugin_manager->getDefinitions() as $name => $facet_source_definition) {
        if (class_exists($facet_source_definition['class']) && empty($this->facetSourcePlugins[$name])) {
          // Create our settings for this facet source..
          $config = isset($this->facetSourcePlugins[$name]) ? $this->facetSourcePlugins[$name] : [];

          /* @var $facet_source \Drupal\facets\FacetSource\FacetSourcePluginInterface */
          $facet_source = $facet_source_plugin_manager->createInstance($name, $config);
          $this->facetSourcePlugins[$name] = $facet_source;
        }
        elseif (!class_exists($facet_source_definition['class'])) {
          \Drupal::logger('facets')
            ->warning('Facet Source @id specifies a non-existing @class.', [
              '@id' => $name,
              '@class' => $facet_source_definition['class'],
            ]);
        }
      }
    }

    // Filter facet sources by status if required.
    if (!$only_enabled) {
      return $this->facetSourcePlugins;
    }

    return array_intersect_key($this->facetSourcePlugins, array_flip($this->facetSourcePlugins));
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors($only_enabled = TRUE) {
    $processors = $this->loadProcessors();

    // Filter processors by status if required. Enabled processors are those
    // which have settings in the processor_configs.
    if ($only_enabled) {
      $processors_settings = $this->getProcessorConfigs();
      $processors = array_intersect_key($processors, $processors_settings);
    }

    return $processors;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE) {
    $processors = $this->getProcessors($only_enabled);
    $processor_settings = $this->getProcessorConfigs();
    $processor_weights = [];

    // Get a list of all processors for given stage.
    foreach ($processors as $name => $processor) {
      if ($processor->supportsStage($stage)) {
        if (!empty($processor_settings[$name]['weights'][$stage])) {
          $processor_weights[$name] = $processor_settings[$name]['weights'][$stage];
        }
        else {
          $processor_weights[$name] = $processor->getDefaultWeight($stage);
        }
      }
    }

    // Sort requested processors by weight.
    asort($processor_weights);

    $return_processors = [];
    foreach ($processor_weights as $name => $weight) {
      $return_processors[$name] = $processors[$name];
    }
    return $return_processors;
  }

  /**
   * {@inheritdoc}
   */
  public function setOnlyVisibleWhenFacetSourceIsVisible($only_visible_when_facet_source_is_visible) {
    $this->only_visible_when_facet_source_is_visible = $only_visible_when_facet_source_is_visible;
  }

  /**
   * {@inheritdoc}
   */
  public function getOnlyVisibleWhenFacetSourceIsVisible() {
    return $this->only_visible_when_facet_source_is_visible;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(array $processor) {
    $this->processor_configs[$processor['processor_id']] = [
      'processor_id' => $processor['processor_id'],
      'weights' => $processor['weights'],
      'settings' => $processor['settings'],
    ];
    // Sort the processors so we won't have unnecessary changes.
    ksort($this->processor_configs);
  }

  /**
   * {@inheritdoc}
   */
  public function removeProcessor($processor_id) {
    unset($this->processor_configs[$processor_id]);
    unset($this->processors[$processor_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEmptyBehavior() {
    return $this->empty_behavior;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmptyBehavior(array $empty_behavior) {
    $this->empty_behavior = $empty_behavior;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setMinCount($min_count) {
    $this->min_count = $min_count;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinCount() {
    return $this->min_count;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $source = $this->getFacetSource();
    if ($source === NULL) {
      return $this;
    }

    $facet_dependencies = $source->calculateDependencies();
    if (!empty($facet_dependencies)) {
      $this->addDependencies($facet_dependencies);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (!$update) {
      self::clearBlockCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    self::clearBlockCache();
  }

  /**
   * Clear the block cache.
   *
   * This includes resetting the shared plugin block manager as this can result
   * in the block definition cache being rebuilt in the same request with stale
   * static caches in the deriver.
   */
  protected static function clearBlockCache() {
    $container = \Drupal::getContainer();

    // If the block manager has already been loaded, we may have stale static
    // caches in the facet deriver, so lets clear it out.
    $container->set('plugin.manager.block', NULL);

    // Now rebuild the cache to force a fresh set of data.
    $container->get('plugin.manager.block')->clearCachedDefinitions();
  }

}
