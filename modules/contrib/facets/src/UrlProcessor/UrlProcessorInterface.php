<?php

namespace Drupal\facets\UrlProcessor;

use Drupal\facets\FacetInterface;

/**
 * Interface UrlProcessorInterface.
 *
 * The url processor takes care of retrieving facet information from the url.
 * It also handles the generation of facet links. This extends the pre query and
 * build processor interfaces, those methods are where the bulk of the work
 * should be done.
 *
 * The facet manager has one url processor.
 *
 * @package Drupal\facets\UrlProcessor
 */
interface UrlProcessorInterface {

  /**
   * Adds urls to the results.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   An array of results.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   An array of results with added urls.
   */
  public function buildUrls(FacetInterface $facet, array $results);

  /**
   * Sets active items.
   *
   * Is called after the url processor is ready retrieving and altering the
   * active filters to let the facet know about the active items.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet that is edited.
   */
  public function setActiveItems(FacetInterface $facet);

  /**
   * Returns the filter key.
   *
   * @return string
   *   A string containing the filter key.
   */
  public function getFilterKey();

  /**
   * Returns the url separator.
   *
   * @return string
   *   A string containing the url separator.
   */
  public function getSeparator();

  /**
   * Returns the active filters.
   *
   * @return array
   *   An array containing the active filters with key being the facet id and
   *   value being an array of raw values.
   */
  public function getActiveFilters();

  /**
   * Set active filters.
   *
   * Allows overriding the active filters, which initially are set by the url
   * processor logic, to build custom urls.
   *
   * @param array $active_filters
   *   An array containing the active filters with key being the facet id and
   *   value being an array of raw values.
   */
  public function setActiveFilters(array $active_filters);

}
