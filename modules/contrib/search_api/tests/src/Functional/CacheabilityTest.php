<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\search_api\Entity\Index;

/**
 * Tests the cacheability metadata of Search API.
 *
 * @group search_api
 */
class CacheabilityTest extends SearchApiBrowserTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rest',
    'search_api',
    'search_api_test',
    'search_api_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set up example structure and content and populate the test index with
    // that content.
    $this->setUpExampleStructure();
    $this->insertExampleContent();

    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll(Index::load($this->indexId));
    $this->indexItems($this->indexId);
  }

  /**
   * Tests the cacheability settings of Search API.
   */
  public function testFramework() {
    $this->drupalLogin($this->adminUser);

    // Verify that the search results are marked as uncacheable.
    $this->drupalGet('search-api-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('x-drupal-dynamic-cache', 'UNCACHEABLE');
    $this->assertTrue(strpos($this->drupalGetHeader('cache-control'), 'no-cache'));

    // Verify that the search results are displayed.
    $this->assertSession()->pageTextContains('foo test');
    $this->assertSession()->pageTextContains('foo baz');
  }

  /**
   * Tests that indexing or deleting items clears the cache.
   */
  public function testViewsCacheAddRemoveContent() {
    $entity = $this->addTestEntity(6, [
      'name' => 'Fresh node',
      'body' => 'test foobar Case',
      'type' => 'item',
    ]);
    // Prime page cache before indexing.
    $this->drupalGet('search-api-test-search-view-caching-tag');
    $this->assertSession()->pageTextContains('Displaying 5 search results');

    $this->indexItems($this->indexId);

    // Check that the newly indexed node is visible on the search index.
    $this->drupalGet('search-api-test-search-view-caching-tag');
    $this->assertSession()->pageTextContains('Displaying 6 search results');

    $entity->delete();

    // Check that the deleted entity is now no longer shown.
    $this->drupalGet('search-api-test-search-view-caching-tag');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
  }

}
