<?php

namespace Drupal\search_api\Plugin\views\cache;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\QueryHelperInterface;

/**
 * Provides a trait to use in Views cache plugins for Search API queries.
 */
trait SearchApiCachePluginTrait {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|null
   */
  protected $cacheBackend;

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|null
   */
  protected $cacheContextsManager;

  /**
   * The query helper.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface|null
   */
  protected $queryHelper;

  /**
   * Retrieves the cache backend.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend.
   */
  public function getCacheBackend() {
    return $this->cacheBackend ?: \Drupal::cache($this->resultsBin);
  }

  /**
   * Sets the cache backend.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The new cache backend.
   *
   * @return $this
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend) {
    $this->cacheBackend = $cache_backend;
    return $this;
  }

  /**
   * Retrieves the cache contexts manager.
   *
   * @return \Drupal\Core\Cache\Context\CacheContextsManager
   *   The cache contexts manager.
   */
  public function getCacheContextsManager() {
    return $this->cacheContextsManager ?: \Drupal::service('cache_contexts_manager');
  }

  /**
   * Sets the cache contexts manager.
   *
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The new cache contexts manager.
   *
   * @return $this
   */
  public function setCacheContextsManager(CacheContextsManager $cache_contexts_manager) {
    $this->cacheContextsManager = $cache_contexts_manager;
    return $this;
  }

  /**
   * Retrieves the query helper.
   *
   * @return \Drupal\search_api\Utility\QueryHelperInterface
   *   The query helper.
   */
  public function getQueryHelper() {
    return $this->queryHelper ?: \Drupal::service('search_api.query_helper');
  }

  /**
   * Sets the query helper.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The new query helper.
   *
   * @return $this
   */
  public function setQueryHelper(QueryHelperInterface $query_helper) {
    $this->queryHelper = $query_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheSet($type) {
    if ($type != 'results') {
      parent::cacheSet($type);
      return;
    }

    $view = $this->getView();
    $data = [
      'result' => $view->result,
      'total_rows' => isset($view->total_rows) ? $view->total_rows : 0,
      'current_page' => $view->getCurrentPage(),
      'search_api results' => $this->getQuery()->getSearchApiResults(),
    ];

    $expire = $this->cacheSetMaxAge($type);
    if ($expire !== Cache::PERMANENT) {
      $expire += (int) $view->getRequest()->server->get('REQUEST_TIME');
    }
    $this->getCacheBackend()
      ->set($this->generateResultsKey(), $data, $expire, $this->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function cacheGet($type) {
    if ($type != 'results') {
      return parent::cacheGet($type);
    }

    // Values to set: $view->result, $view->total_rows, $view->execute_time,
    // $view->current_page.
    if ($cache = $this->getCacheBackend()->get($this->generateResultsKey())) {
      $cutoff = $this->cacheExpire($type);
      if (!$cutoff || $cache->created > $cutoff) {
        $view = $this->getView();
        $view->result = $cache->data['result'];
        $view->total_rows = $cache->data['total_rows'];
        $view->setCurrentPage($cache->data['current_page']);
        $view->execute_time = 0;

        // Trick Search API into believing a search happened, to make faceting
        // et al. work.
        /** @var \Drupal\search_api\Query\ResultSetInterface $results */
        $results = $cache->data['search_api results'];
        $this->getQueryHelper()->addResults($results);

        try {
          $this->getQuery()->setSearchApiQuery($results->getQuery());
        }
        catch (SearchApiException $e) {
          // Ignore.
        }

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function generateResultsKey() {
    if (!isset($this->resultsKey)) {
      $query = $this->getQuery()->getSearchApiQuery();
      $query->preExecute();

      $view = $this->getView();
      $build_info = $view->build_info;

      $key_data = [
        'build_info' => $build_info,
        'pager' => [
          'page' => $view->getCurrentPage(),
          'items_per_page' => $view->getItemsPerPage(),
          'offset' => $view->getOffset(),
        ],
      ];

      $display_handler_cache_contexts = $this->displayHandler
        ->getCacheMetadata()
        ->getCacheContexts();
      $key_data += $this->getCacheContextsManager()
        ->convertTokensToKeys($display_handler_cache_contexts)
        ->getKeys();

      $this->resultsKey = $view->storage->id() . ':' . $this->displayHandler->display['id'] . ':results:' . Crypt::hashBase64(serialize($key_data));
    }

    return $this->resultsKey;
  }

  /**
   * Retrieves the view to which this plugin belongs.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view.
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * Retrieves the Search API Views query for the current view.
   *
   * @return \Drupal\search_api\Plugin\views\query\SearchApiQuery|null
   *   The Search API Views query associated with the current view.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if there is no current Views query, or it is no Search API query.
   */
  protected function getQuery() {
    $query = $this->getView()->getQuery();
    if ($query instanceof SearchApiQuery) {
      return $query;
    }
    throw new SearchApiException('No matching Search API Views query found in view.');
  }

}
