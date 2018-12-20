<?php

namespace Drupal\facets\FacetSource;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\QueryType\QueryTypePluginManager;

/**
 * Defines a base class from which other facet sources may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. The definition includes the following keys:
 * - id: The unique, system-wide identifier of the facet source.
 * - label: The human-readable name of the facet source, translated.
 * - description: A human-readable description for the facet source, translated.
 *
 * @see \Drupal\facets\Annotation\FacetsFacetSource
 * @see \Drupal\facets\FacetSource\FacetSourcePluginManager
 * @see \Drupal\facets\FacetSource\FacetSourcePluginInterface
 * @see plugin_api
 */
abstract class FacetSourcePluginBase extends PluginBase implements FacetSourcePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\facets\QueryType\QueryTypePluginManager
   */
  protected $queryTypePluginManager;

  /**
   * The search keys, or query text, submitted by the user.
   *
   * @var string
   */
  protected $keys;

  /**
   * The facet we're editing for.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected $facet;

  /**
   * Constructs a FacetSourcePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager
   *   The query type plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryTypePluginManager $query_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queryTypePluginManager = $query_type_plugin_manager;

    if (isset($configuration['facet'])) {
      $this->facet = $configuration['facet'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Insert the plugin manager for query types.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.query_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypesForFacet(FacetInterface $facet) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSearchKeys($keys) {
    $this->keys = $keys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchKeys() {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFacet() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    global $pager_total_items;
    // Exposing a global here. This is pretty ugly but the only way to get the
    // actual results for any kind of query that was done and gets back results.
    // @see core/includes/pager.inc for more information how this works.
    // Rounding as some backend plugins could not give accurate information on
    // the results found.
    // @todo Figure out when it can not be the first one in the list.
    return round($pager_total_items[0]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $facet_source_id = $this->facet->getFacetSourceId();
    $field_identifier = $form_state->getValue('facet_source_configs')[$facet_source_id]['field_identifier'];
    $this->facet->setFieldIdentifier($field_identifier);
  }

}
