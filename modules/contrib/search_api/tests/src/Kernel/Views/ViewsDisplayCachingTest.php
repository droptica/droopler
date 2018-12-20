<?php

namespace Drupal\Tests\search_api\Kernel\Views;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\views\Tests\AssertViewsCacheTagsTrait;
use Drupal\views\ViewExecutable;

/**
 * Tests the Search API caching plugins for Views.
 *
 * @group search_api
 */
class ViewsDisplayCachingTest extends KernelTestBase {

  use AssertViewsCacheTagsTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service that is responsible for creating Views executable objects.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutableFactory;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The cache backend used for testing.
   *
   * @var \Drupal\Tests\search_api\Kernel\Views\TestMemoryBackend
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'rest',
    'search_api',
    'search_api_db',
    'search_api_test',
    'search_api_test_db',
    'search_api_test_example_content',
    'search_api_test_views',
    'serialization',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);

    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');

    $this->installConfig([
      'search_api',
      'search_api_test_example_content',
      'search_api_test_db',
      'search_api_test_views',
    ]);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->viewExecutableFactory = $this->container->get('views.executable');

    // Use the test search index from the search_api_test_db module.
    $this->index = Index::load('database_search_index');

    // Use a test cache backend that allows to tamper with the request time so
    // we can test time based caching.
    $this->cache = new TestMemoryBackend();
    $this->container->set('cache.data', $this->cache);

    // Create some demo content and index it.
    $this->createDemoContent();
    $this->index->indexItems();
  }

  /**
   * Tests whether the search display plugin for a new view is available.
   *
   * @param string $display_id
   *   The tested Views display's ID.
   * @param string[] $expected_cache_tags
   *   The expected cache tags for the executed view.
   * @param string[] $expected_cache_contexts
   *   The expected cache contexts for the executed view.
   * @param int $expected_max_age
   *   The expected max cache age for the executed view.
   * @param bool $expected_results_cache
   *   TRUE if the results cache is expected to be populated after executing the
   *   view, FALSE otherwise.
   *
   * @dataProvider displayCacheabilityProvider
   */
  public function testDisplayCacheability($display_id, array $expected_cache_tags, array $expected_cache_contexts, $expected_max_age, $expected_results_cache) {
    $view = $this->getView('search_api_test_cache', $display_id);

    // Before the search is executed, the query should not be cached.
    $this->assertViewsResultsCacheNotPopulated($view);

    // Execute the search and assert the cacheability metadata.
    $this->assertViewsCacheability($view, $expected_cache_tags, $expected_cache_contexts, $expected_max_age);

    // AssertViewsCache() destroys the view, get a fresh copy to continue the
    // test.
    $view = $this->getView('search_api_test_cache', $display_id);

    // The query has been executed. The query should now be cached if the test
    // case expects it.
    if ($expected_results_cache) {
      $this->assertViewsResultsCachePopulated($view);

      // Trigger the event that would cause a cache invalidation for the plugin
      // under test. Now the query result should not be cached any more.
      $this->triggerInvalidation($display_id);
    }

    $this->assertViewsResultsCacheNotPopulated($view);
  }

  /**
   * Checks that the query result of the given view is not currently cached.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   */
  protected function assertViewsResultsCacheNotPopulated(ViewExecutable $view) {
    $this->assertEmpty($this->getResultsCache($view));
  }

  /**
   * Checks that the query result of the given view is currently cached.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   */
  protected function assertViewsResultsCachePopulated(ViewExecutable $view) {
    $this->assertNotEmpty($this->getResultsCache($view));
  }

  /**
   * Returns the current query result cache of the given view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view for which to return the query result cache.
   *
   * @return false|object
   *   The cache object, or FALSE if the query cache is not populated.
   */
  protected function getResultsCache(ViewExecutable $view) {
    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache_plugin */
    $cache_plugin = $view->display_handler->getPlugin('cache');

    // Ensure that the views query is built.
    $view->build();
    return $this->cache->get($cache_plugin->generateResultsKey());
  }

  /**
   * Checks the cacheability metadata of the given view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to test.
   * @param string[] $expected_cache_tags
   *   The expected cache tags.
   * @param string[] $expected_cache_contexts
   *   The expected cache contexts.
   * @param int $expected_max_age
   *   The expected cache max age.
   */
  protected function assertViewsCacheability(ViewExecutable $view, array $expected_cache_tags, array $expected_cache_contexts, $expected_max_age) {
    $build = $this->assertViewsCacheTags($view, NULL, FALSE, $expected_cache_tags);
    $this->assertCacheContexts($expected_cache_contexts, $build);
    $this->assertCacheMaxAge($expected_max_age, $build);
  }

  /**
   * Triggers the event that should provoke a cache invalidation.
   *
   * @param string $plugin_type
   *   The views cache plugin type for which a cache invalidation should be
   *   triggered. Can be 'none', 'tag' or 'time'.
   */
  protected function triggerInvalidation($plugin_type) {
    switch ($plugin_type) {
      // When using the 'tag' based caching strategy, create and index a new
      // entity of the type that is used in the index. This should clear it.
      case 'tag':
        EntityTestMulRevChanged::create(['name' => 'Tomahawk'])->save();
        $this->index->indexItems();
        break;

      // When using 'time' based caching, pretend to be more than 1 hour in the
      // future.
      case 'time':
        $this->cache->setRequestTime($this->cache->getRequestTime() + 3700);
        break;
    }
  }

  /**
   * Creates some test axes.
   */
  protected function createDemoContent() {
    foreach (['Glaive', 'Halberd', 'Hurlbat'] as $name) {
      EntityTestMulRevChanged::create(['name' => $name])->save();
    }
  }

  /**
   * Loads a view from configuration and returns its executable object.
   *
   * @param string $id
   *   The view ID to load.
   * @param string $display_id
   *   The display ID to set.
   *
   * @return \Drupal\views\ViewExecutable
   *   A view executable instance, from the loaded entity.
   */
  protected function getView($id, $display_id) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->entityTypeManager->getStorage('view')->load($id);
    $executable = $this->viewExecutableFactory->get($view);
    $executable->setDisplay($display_id);
    $executable->setExposedInput(['search_api_fulltext' => 'Glaive']);
    return $executable;
  }

  /**
   * Checks if the cache contexts in the given render array are as expected.
   *
   * @param string[] $expected_contexts
   *   An array of cache contexts that are expected to be present in the given
   *   render array.
   * @param array $render_array
   *   The render array of which to check the cache contexts.
   */
  protected function assertCacheContexts(array $expected_contexts, array $render_array) {
    // Merge in the default cache contexts in the expected contexts.
    $default_contexts = [
      // Default contexts that are always provided by core.
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      'theme',
      'user.permissions',
      // We are showing translatable content, so it varies by content language.
      'languages:' . LanguageInterface::TYPE_CONTENT,
      // Our view has a pager, so we vary by query arguments.
      // @see \Drupal\views\Plugin\views\pager\SqlBase::getCacheContexts()
      'url.query_args',
      // Since our view has exposed filters, it varies by url.
      // @see \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase::getCacheContexts()
      'url',
    ];

    $expected_contexts = Cache::mergeContexts($expected_contexts, $default_contexts);
    $actual_contexts = CacheableMetadata::createFromRenderArray($render_array)->getCacheContexts();

    sort($expected_contexts);
    sort($actual_contexts);

    $this->assertEquals($expected_contexts, $actual_contexts);
  }

  /**
   * Checks if the cache max-age in the given render array is as expected.
   *
   * @param int $expected_max_age
   *   The max-age value that is expected to be present in the given render
   *   array.
   * @param array $render_array
   *   The render array of which to check the cache max-age.
   */
  protected function assertCacheMaxAge($expected_max_age, array $render_array) {
    $actual_max_age = CacheableMetadata::createFromRenderArray($render_array)->getCacheMaxAge();
    $this->assertEquals($expected_max_age, $actual_max_age);
  }

  /**
   * Data provider for testDisplayCacheability().
   *
   * @return array
   *   Array of method call argument arrays for testDisplayCacheability().
   *
   * @see ::testDisplayCacheability
   */
  public function displayCacheabilityProvider() {
    return [
      // First test case, using the 'none' caching plugin that is included with
      // Views. This is expected to disable caching.
      [
        'none',
        // It is expected that only the configuration of the view itself is
        // available as a cache tag.
        [
          'config:views.view.search_api_test_cache',
        ],
        // No specific cache contexts are expected to be present.
        [],
        // It is expected that the cache max-age is set to zero, effectively
        // disabling the cache.
        0,
        // It is expected that no results are cached.
        FALSE,
      ],

      // Test case using cache tags based caching. This should provide relevant
      // cache tags so that the results can be cached permanently, but be
      // invalidated whenever relevant changes occur.
      [
        'tag',
        [
          // It is expected that the configuration of the view itself is
          // available as a cache tag, so that the caches are invalidated if the
          // view configuration changes.
          'config:views.view.search_api_test_cache',
          // Caches should also be invalidated if any items on the index are
          // indexed or deleted.
          'search_api_list:database_search_index',
        ],
        // No specific cache contexts are expected to be present.
        [],
        // For tag based caching it is expected that the cache life time is set
        // to permanent.
        Cache::PERMANENT,
        // It is expected that views results can be cached.
        TRUE,
      ],

      // Test case using time based caching. This should invalidate the caches
      // after a predefined time period.
      [
        'time',
        [
          // It is expected that the configuration of the view itself is
          // available as a cache tag, so that the caches are invalidated if the
          // view configuration changes. No other tags should be available.
          'config:views.view.search_api_test_cache',
        ],
        // No specific cache contexts are expected to be present.
        [],
        // It is expected that the cache max-age is set to 1 hour.
        3600,
        // It is expected that views results can be cached.
        TRUE,
      ],
    ];
  }

}
