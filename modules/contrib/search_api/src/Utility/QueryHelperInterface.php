<?php

namespace Drupal\search_api\Utility;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Provides an interface for query helper services.
 */
interface QueryHelperInterface {

  /**
   * Creates a new search query object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index on which to search.
   * @param array $options
   *   (optional) The options to set for the query. See
   *   \Drupal\search_api\Query\QueryInterface::setOption() for a list of
   *   options that are recognized by default.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A search query object to use.
   */
  public function createQuery(IndexInterface $index, array $options = []);

  /**
   * Adds a result set to the cache.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The search results to cache.
   */
  public function addResults(ResultSetInterface $results);

  /**
   * Retrieves the results data for a search ID.
   *
   * @param string $search_id
   *   The search ID of the results to retrieve.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface|null
   *   The results with the given search ID, if present; NULL otherwise.
   */
  public function getResults($search_id);

  /**
   * Retrieves all results data cached in this request.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface[]
   *   The results of all searches cached in this service, keyed by their
   *   search IDs.
   */
  public function getAllResults();

  /**
   * Removes the result set with the given search ID from the cache.
   *
   * @param string $search_id
   *   The search ID.
   */
  public function removeResults($search_id);

}
