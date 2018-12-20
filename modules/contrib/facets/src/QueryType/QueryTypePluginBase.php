<?php

namespace Drupal\facets\QueryType;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * A base class for query type plugins that implements most of the boilerplate.
 */
abstract class QueryTypePluginBase extends PluginBase implements QueryTypeInterface, ConfigurablePluginInterface {

  use DependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->query = $this->configuration['query'];
    $this->facet = $this->configuration['facet'];
    $this->results = !empty($this->configuration['results']) ? $this->configuration['results'] : [];
  }

  /**
   * The backend native query object.
   *
   * @var \Drupal\search_api\Query\Query
   */
  protected $query;

  /**
   * The facet that needs the query type.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected $facet;

  /**
   * The results for the facet.
   *
   * @var array[]
   */
  protected $results;

  /**
   * The injected link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->addDependency('module', $this->getPluginDefinition()['provider']);
    return $this->dependencies;
  }

  /**
   * Builds facet options that will be send to the backend.
   *
   * @return array
   *   An array of default options for the facet.
   */
  protected function getFacetOptions() {
    return [
      'field' => $this->facet->getFieldIdentifier(),
      'limit' => $this->facet->getHardLimit(),
      'operator' => $this->facet->getQueryOperator(),
      'min_count' => $this->facet->getMinCount(),
      'missing' => FALSE,
      'query_type' => $this->getPluginId(),
    ];
  }

}
