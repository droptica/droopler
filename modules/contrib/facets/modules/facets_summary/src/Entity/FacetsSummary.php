<?php

namespace Drupal\facets_summary\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\facets_summary\FacetsSummaryInterface;

/**
 * Defines the facet summary entity.
 *
 * @ConfigEntityType(
 *   id = "facets_summary",
 *   label = @Translation("Facet summary"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\facets\FacetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\facets_summary\Form\FacetsSummarySettingsForm",
 *       "edit" = "Drupal\facets_summary\Form\FacetsSummaryForm",
 *       "settings" = "Drupal\facets_summary\Form\FacetsSummarySettingsForm",
 *       "delete" = "Drupal\facets_summary\Form\FacetsSummaryDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer facets",
 *   config_prefix = "facets_summary",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "facets",
 *     "facet_source_id",
 *     "processor_configs",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/search/facets/add-facet-summary",
 *     "edit-form" = "/admin/config/search/facets/facet-summary/{facets_summary}/edit",
 *     "settings-form" = "/admin/config/search/facets/facet-summary{facets_summary}/settings",
 *     "delete-form" = "/admin/config/search/facets/facet-summary/{facets_summary}/delete",
 *   }
 * )
 */
class FacetsSummary extends ConfigEntityBase implements FacetsSummaryInterface {

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
   * The id of the facet source.
   *
   * @var string
   */
  protected $facet_source_id;

  /**
   * The facet source belonging to this facet summary.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginInterface
   *
   * @see getFacetSource()
   */
  protected $facet_source_instance;

  /**
   * The summary block settings per-facet.
   *
   * @var string[]
   */
  protected $facets = [];

  /**
   * Cached information about the processors available for this facet.
   *
   * @var \Drupal\facets_summary\Processor\ProcessorInterface[]|null
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
   * The facet weight.
   *
   * @var int
   *   The weight of the facet.
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the facet source identifier.
   *
   * @return string
   *   The id of the facet source plugin.
   */
  public function getFacetSourceId() {
    return $this->facet_source_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setFacetSourceId($facet_source_id) {
    $this->facet_source_id = $facet_source_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSource() {
    if (!$this->facet_source_instance && $this->facet_source_id) {
      /* @var $facet_source_plugin_manager \Drupal\facets\FacetSource\FacetSourcePluginManager */
      $facet_source_plugin_manager = \Drupal::service('plugin.manager.facets.facet_source');
      $this->facet_source_instance = $facet_source_plugin_manager->createInstance($this->facet_source_id, ['facets_summary' => $this]);
    }

    return $this->facet_source_instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacets() {
    return $this->facets;
  }

  /**
   * {@inheritdoc}
   */
  public function setFacets(array $facets) {
    return $this->facets = $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function removeFacet($facet_id) {
    unset($this->facets[$facet_id]);
    return $this;
  }

  /**
   * Retrieves all processors supported by this facets summary.
   *
   * @return \Drupal\facets_summary\Processor\ProcessorInterface[]
   *   The loaded processors, keyed by processor ID.
   */
  protected function loadProcessors() {
    if (is_array($this->processors)) {
      return $this->processors;
    }

    /* @var $processor_plugin_manager \Drupal\facets\Processor\ProcessorPluginManager */
    $processor_plugin_manager = \Drupal::service('plugin.manager.facets_summary.processor');
    $processor_settings = $this->getProcessorConfigs();

    foreach ($processor_plugin_manager->getDefinitions() as $name => $processor_definition) {
      if (class_exists($processor_definition['class']) && empty($this->processors[$name])) {
        // Create our settings for this processor.
        $settings = empty($processor_settings[$name]['settings']) ? [] : $processor_settings[$name]['settings'];
        $settings['facets_summary'] = $this;

        /* @var $processor \Drupal\facets_summary\Processor\ProcessorInterface */
        $processor = $processor_plugin_manager->createInstance($name, $settings);
        $this->processors[$name] = $processor;
      }
      elseif (!class_exists($processor_definition['class'])) {
        \Drupal::logger('facets_summary')
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
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if ($this->getFacetSource() === NULL) {
      return $this;
    }

    $facet_source_dependencies = $this->getFacetSource()->calculateDependencies();
    if (!empty($facet_source_dependencies)) {
      $this->addDependencies($facet_source_dependencies);
    }

    return $this;
  }

}
