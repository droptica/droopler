<?php

namespace Drupal\search_api\Utility;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\ResultSetInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods for creating search queries and statically caching results.
 */
class QueryHelper implements QueryHelperInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The parse mode manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager
   */
  protected $parseModeManager;

  /**
   * Storage for the results, keyed by request and search ID.
   *
   * @var \SplObjectStorage
   */
  protected $results;

  /**
   * NULL value to use as a key for the results storage.
   *
   * @var object
   */
  protected $null;

  /**
   * Constructs a QueryHelper object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parseModeManager
   *   The parse mode manager.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, ParseModePluginManager $parseModeManager) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $moduleHandler;
    $this->parseModeManager = $parseModeManager;
    $this->results = new \SplObjectStorage();
    $this->null = (object) [];
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery(IndexInterface $index, array $options = []) {
    $query = Query::create($index, $options);

    $query->setModuleHandler($this->moduleHandler);
    $query->setParseModeManager($this->parseModeManager);
    $query->setQueryHelper($this);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function addResults(ResultSetInterface $results) {
    $search_id = $results->getQuery()->getSearchId();
    $request = $this->getCurrentRequest();
    if (!isset($this->results[$request])) {
      $this->results[$request] = [
        $search_id => $results,
      ];
    }
    else {
      // It's not possible to directly assign array values to an array inside of
      // a \SplObjectStorage object. So we have to first retrieve the array,
      // then add the results to it, then store it again.
      $cache = $this->results[$request];
      $cache[$search_id] = $results;
      $this->results[$request] = $cache;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResults($search_id) {
    $request = $this->getCurrentRequest();
    if (isset($this->results[$request])) {
      $results = $this->results[$request];
      if (!empty($results[$search_id])) {
        return $this->results[$request][$search_id];
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllResults() {
    $request = $this->getCurrentRequest();
    if (isset($this->results[$request])) {
      return $this->results[$request];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function removeResults($search_id) {
    $request = $this->getCurrentRequest();
    if (isset($this->results[$request])) {
      $cache = $this->results[$request];
      unset($cache[$search_id]);
      $this->results[$request] = $cache;
    }
  }

  /**
   * Retrieves the current request.
   *
   * If there is no current request, instead of returning NULL this will instead
   * return a unique object to be used in lieu of a NULL key.
   *
   * @return \Symfony\Component\HttpFoundation\Request|object
   *   The current request, if present; or this object's representation of the
   *   NULL key.
   */
  protected function getCurrentRequest() {
    return $this->requestStack->getCurrentRequest() ?: $this->null;
  }

}
