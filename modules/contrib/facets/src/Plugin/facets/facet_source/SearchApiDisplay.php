<?php

namespace Drupal\facets\Plugin\facets\facet_source;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\facets\Exception\Exception;
use Drupal\facets\Exception\InvalidQueryTypeException;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\FacetSourcePluginBase;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\Display\DisplayPluginManager;
use Drupal\search_api\FacetsQueryTypeMappingInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Utility\QueryHelper;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a facet source based on a Search API display.
 *
 * @FacetsFacetSource(
 *   id = "search_api",
 *   deriver = "Drupal\facets\Plugin\facets\facet_source\SearchApiDisplayDeriver"
 * )
 */
class SearchApiDisplay extends FacetSourcePluginBase implements SearchApiFacetSourceInterface {

  /**
   * The search index the query should is executed on.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The display plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManager
   */
  protected $displayPluginManager;

  /**
   * The search result cache.
   *
   * @var \Drupal\search_api\Utility\QueryHelper
   */
  protected $searchApiQueryHelper;

  /**
   * The clone of the current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a SearchApiBaseFacetSource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager
   *   The query type plugin manager.
   * @param \Drupal\search_api\Utility\QueryHelper $search_results_cache
   *   The query type plugin manager.
   * @param \Drupal\search_api\Display\DisplayPluginManager $display_plugin_manager
   *   The display plugin manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object for the current request.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Core's module handler class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryTypePluginManager $query_type_plugin_manager, QueryHelper $search_results_cache, DisplayPluginManager $display_plugin_manager, Request $request, ModuleHandler $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager);

    $this->searchApiQueryHelper = $search_results_cache;
    $this->displayPluginManager = $display_plugin_manager;
    $this->moduleHandler = $moduleHandler;
    $this->request = clone $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // If the Search API module is not enabled, we should just return an empty
    // object. This allows us to have this class in the module without having a
    // dependency on the Search API module.
    if (!$container->get('module_handler')->moduleExists('search_api')) {
      return new \stdClass();
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.query_type'),
      $container->get('search_api.query_helper'),
      $container->get('plugin.manager.search_api.display'),
      $container->get('request_stack')->getMasterRequest(),
      $container->get('module_handler')
    );
  }

  /**
   * Retrieves the Search API index for this facet source.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index.
   */
  public function getIndex() {
    if ($this->index === NULL) {
      $this->index = $this->getDisplay()->getIndex();
    }

    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    // The implementation in search api tells us that this is a base path only
    // if a path is defined, and false if that isn't done. This means that we
    // have to check for this + create our own uri if that's needed.
    if ($this->getDisplay()->getPath()) {
      return $this->getDisplay()->getPath();
    }

    return \Drupal::service('path.current')->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults(array $facets) {
    $search_id = $this->getDisplay()->getPluginId();

    // Check if the results for this search id are already populated in the
    // query helper. This is usually the case for views displays that are
    // rendered on the same page, such as views_page.
    $results = $this->searchApiQueryHelper->getResults($search_id);

    // If there are no results, we can check the Search API Display plugin has
    // configuration for views. If that configuration exists, we can execute
    // that view and try to use it's results.
    $display_definition = $this->getDisplay()->getPluginDefinition();
    if ($results === NULL && isset($display_definition['view_id'])) {
      $view = Views::getView($display_definition['view_id']);
      $view->setDisplay($display_definition['view_display']);
      $view->execute();
      $results = $this->searchApiQueryHelper->getResults($search_id);
    }

    if (!$results instanceof ResultSetInterface) {
      return;
    }

    // Get our facet data.
    $facet_results = $results->getExtraData('search_api_facets');

    // If no data is found in the 'search_api_facets' extra data, we can stop
    // execution here.
    if ($facet_results === []) {
      return;
    }

    // Loop over each facet and execute the build method from the given
    // query type.
    foreach ($facets as $facet) {
      $configuration = [
        'query' => $results->getQuery(),
        'facet' => $facet,
        'results' => isset($facet_results[$facet->getFieldIdentifier()]) ? $facet_results[$facet->getFieldIdentifier()] : [],
      ];

      // Get the Facet Specific Query Type so we can process the results
      // using the build() function of the query type.
      $query_type = $this->queryTypePluginManager->createInstance($facet->getQueryType(), $configuration);
      $query_type->build();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    return $this->getDisplay()->isRenderedInCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['field_identifier'] = [
      '#type' => 'select',
      '#options' => $this->getFields(),
      '#title' => $this->t('Field'),
      '#description' => $this->t('The field from the selected facet source which contains the data to build a facet for.<br> The field types supported are <strong>boolean</strong>, <strong>date</strong>, <strong>decimal</strong>, <strong>integer</strong> and <strong>string</strong>.'),
      '#required' => TRUE,
      '#default_value' => $this->facet->getFieldIdentifier(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $indexed_fields = [];
    $index = $this->getIndex();

    $fields = $index->getFields();
    $server = $index->getServerInstance();
    $backend = $server->getBackend();

    foreach ($fields as $field) {
      $data_type_plugin_id = $field->getDataTypePlugin()->getPluginId();
      $query_types = $this->getQueryTypesForDataType($backend, $data_type_plugin_id);
      if (!empty($query_types)) {
        $indexed_fields[$field->getFieldIdentifier()] = $field->getLabel() . ' (' . $field->getPropertyPath() . ')';
      }
    }

    return $indexed_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypesForFacet(FacetInterface $facet) {
    // Get our Facets Field Identifier, which is equal to the Search API Field
    // identifier.
    $field_id = $facet->getFieldIdentifier();
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();
    // Get the Search API Server.
    $server = $index->getServerInstance();
    // Get the Search API Backend.
    $backend = $server->getBackend();

    $fields = $index->getFields();
    foreach ($fields as $field) {
      if ($field->getFieldIdentifier() == $field_id) {
        return $this->getQueryTypesForDataType($backend, $field->getType());
      }
    }

    throw new InvalidQueryTypeException("No available query types were found for facet {$facet->getName()}");
  }

  /**
   * Retrieves the query types for a specified data type.
   *
   * Backend plugins can use this method to override the default query types
   * provided by the Search API with backend-specific ones that better use
   * features of that backend.
   *
   * @param \Drupal\search_api\Backend\BackendInterface $backend
   *   The backend that we want to get the query types for.
   * @param string $data_type_plugin_id
   *   The identifier of the data type.
   *
   * @return string[]
   *   An associative array with the plugin IDs of allowed query types, keyed by
   *   the generic name of the query_type.
   *
   * @see hook_facets_search_api_query_type_mapping_alter()
   */
  protected function getQueryTypesForDataType(BackendInterface $backend, $data_type_plugin_id) {
    $query_types = [];
    $query_types['string'] = 'search_api_string';

    // Add additional query types for specific data types.
    switch ($data_type_plugin_id) {
      case 'date':
        $query_types['date'] = 'search_api_date';
        $query_types['range'] = 'search_api_range';
        break;

      case 'decimal':
      case 'integer':
        $query_types['numeric'] = 'search_api_granular';
        $query_types['range'] = 'search_api_range';
        break;

    }

    // Find out if the backend implemented the Interface to retrieve specific
    // query types for the supported data_types.
    if ($backend instanceof FacetsQueryTypeMappingInterface) {
      $mapping = [
        $data_type_plugin_id => &$query_types,
      ];
      $backend->alterFacetQueryTypeMapping($mapping);
    }
    // Add it to a variable so we can pass it by reference. Alter hook complains
    // due to the property of the backend object is not passable by reference.
    $backend_plugin_id = $backend->getPluginId();

    // Let modules alter this mapping.
    \Drupal::moduleHandler()
      ->alter('facets_search_api_query_type_mapping', $backend_plugin_id, $query_types);

    return $query_types;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $display = $this->getDisplay();
    if ($display instanceof DependentPluginInterface) {
      return $display->calculateDependencies();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplay() {
    return $this->displayPluginManager
      ->createInstance($this->pluginDefinition['display_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsDisplay() {
    if (!$this->moduleHandler->moduleExists('views')) {
      return NULL;
    }

    $search_api_display_definition = $this->getDisplay()->getPluginDefinition();
    if (empty($search_api_display_definition['view_id'])) {
      return NULL;
    }

    $view_id = $search_api_display_definition['view_id'];
    $view_display = $search_api_display_definition['view_display'];

    $view = Views::getView($view_id);
    $view->setDisplay($view_display);
    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition($field_name) {
    $field = $this->getIndex()->getField($field_name);
    if ($field) {
      return $field->getDataDefinition();
    }
    throw new Exception("Field with name {$field_name} does not have a definition");
  }

  /**
   * {@inheritdoc}
   */
  public function buildFacet() {
    $build = parent::buildFacet();
    $view = $this->getViewsDisplay();
    if ($view === NULL) {
      return $build;
    }

    // Add JS for Views with Ajax Enabled.
    if ($view->display_handler->ajaxEnabled()) {
      $js_settings = [
        'view_id' => $view->id(),
        'current_display_id' => $view->current_display,
        'view_base_path' => ltrim($view->getPath(), '/'),
        'ajax_path' => Url::fromRoute('views.ajax')->toString(),
      ];
      $build['#attached']['library'][] = 'facets/drupal.facets.views-ajax';
      $build['#attached']['drupalSettings']['facets_views_ajax'] = [
        $this->facet->id() => $js_settings,
      ];
      $build['#use_ajax'] = TRUE;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $search_id = $this->getDisplay()->getPluginId();
    if ($search_id && !empty($search_id)) {
      if ($this->searchApiQueryHelper->getResults($search_id) !== NULL) {
        return $this->searchApiQueryHelper->getResults($search_id)
          ->getResultCount();
      }
    }
  }

}
