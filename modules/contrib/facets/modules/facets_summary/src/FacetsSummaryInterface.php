<?php

namespace Drupal\facets_summary;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for the entity.
 */
interface FacetsSummaryInterface extends ConfigEntityInterface {

  /**
   * Returns the field name of the facets summary.
   *
   * @return string
   *   The name of the facets summary.
   */
  public function getName();

  /**
   * Returns the Facet source id.
   *
   * @return string
   *   The id of the facet source.
   */
  public function getFacetSourceId();

  /**
   * Sets a string representation of the Facet source plugin.
   *
   * This is usually the name of the Search-api view.
   *
   * @param string $facet_source_id
   *   The facet source id.
   *
   * @return $this
   *   Returns self.
   */
  public function setFacetSourceId($facet_source_id);

  /**
   * Returns the plugin instance of a facet source.
   *
   * @return \Drupal\facets\FacetSource\FacetSourcePluginInterface
   *   The plugin instance for the facet source.
   */
  public function getFacetSource();

  /**
   * Returns a list of facets that are included in this summary.
   *
   * @return array[]
   *   An associative array keyed by facet id and having arrays as values with
   *   the next structure:
   *   - facet_id: (string) The facet entity id.
   *   - show_count: (bool) If the source count will be displayed in the block.
   *   - prefix: (string) Prefix of facet group.
   *   - suffix: (string) Suffix of facet group.
   *   - separator: (string) Separator for facet items.
   */
  public function getFacets();

  /**
   * Returns a list of facets that are included in this summary.
   *
   * @param array $facets
   *   An associative array keyed by facet id and having arrays as values with
   *   the next structure:
   *   - facet_id: (string) The facet entity id.
   *   - show_count: (bool) If the source count will be displayed in the block.
   *   - prefix: (string) Prefix of facet group.
   *   - suffix: (string) Suffix of facet group.
   *   - separator: (string) Separator for facet items.
   */
  public function setFacets(array $facets);

  /**
   * Removes a facet from the list.
   *
   * @param string $facet_id
   *   The facet id to be removed.
   *
   * @return $this
   */
  public function removeFacet($facet_id);

  /**
   * Returns an array of processors with their configuration.
   *
   * @param bool $only_enabled
   *   Only return enabled processors.
   *
   * @return \Drupal\facets_summary\Processor\ProcessorInterface[]
   *   An array of processors.
   */
  public function getProcessors($only_enabled = TRUE);

  /**
   * Loads this facets processors for a specific stage.
   *
   * @param string $stage
   *   The stage for which to return the processors. One of the
   *   \Drupal\facets_summary\Processor\ProcessorInterface::STAGE_* constants.
   * @param bool $only_enabled
   *   (optional) If FALSE, also include disabled processors. Otherwise, only
   *   load enabled ones.
   *
   * @return \Drupal\facets_summary\Processor\ProcessorInterface[]
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

}
