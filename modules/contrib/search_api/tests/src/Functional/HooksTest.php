<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\search_api\Entity\Index;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Tests integration of hooks.
 *
 * @group search_api
 */
class HooksTest extends SearchApiBrowserTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'rest',
    'search_api',
    'search_api_test',
    'search_api_test_views',
    'search_api_test_hooks',
  ];

  /**
   * The test server.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create some nodes.
    $this->drupalCreateNode(['type' => 'page', 'title' => 'node - 1']);
    $this->drupalCreateNode(['type' => 'page', 'title' => 'node - 2']);
    $this->drupalCreateNode(['type' => 'page', 'title' => 'node - 3']);
    $this->drupalCreateNode(['type' => 'page', 'title' => 'node - 4']);

    // Create an index and server to work with.
    $this->server = $this->getTestServer();
    $index = $this->getTestIndex();

    // Add the test processor to the index so we can make sure that all expected
    // processor methods are called, too.
    /** @var \Drupal\search_api\Processor\ProcessorInterface $processor */
    $processor = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createProcessorPlugin($index, 'search_api_test');
    $index->addProcessor($processor)->save();

    // Parts of this test actually use the "database_search_index" from the
    // search_api_test_db module (via the test view). Set the processor there,
    // too.
    $index = Index::load('database_search_index');
    $processor = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createProcessorPlugin($index, 'search_api_test');
    $index->addProcessor($processor)->save();

    // Reset the called methods on the processor.
    $this->getCalledMethods('processor');

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests various operations via the Search API's admin UI.
   */
  public function testHooks() {
    // hook_search_api_backend_info_alter() was invoked.
    $this->drupalGet('admin/config/search/search-api/add-server');
    $this->assertSession()->pageTextContains('Slims return');

    // hook_search_api_datasource_info_alter() was invoked.
    $this->drupalGet('admin/config/search/search-api/add-index');
    $this->assertSession()->pageTextContains('Distant land');
    // hook_search_api_tracker_info_alter() was invoked.
    $this->assertSession()->pageTextContains('Good luck');

    // hook_search_api_processor_info_alter() was invoked.
    $this->drupalGet($this->getIndexPath('processors'));
    $this->assertSession()->pageTextContains('Mystic bounce');

    // hook_search_api_parse_mode_info_alter was invoked.
    $definition = \Drupal::getContainer()
      ->get('plugin.manager.search_api.parse_mode')
      ->getDefinition('direct');
    $this->assertEquals('Song for My Father', $definition['label']);

    // Saving the index should trigger the processor's preIndexSave() method.
    $this->submitForm([], 'Save');
    $processor_methods = $this->getCalledMethods('processor');
    $this->assertEquals(['preIndexSave'], $processor_methods);

    $this->drupalGet($this->getIndexPath());
    // Duplication on value 'Index now' with summary.
    $this->submitForm([], 'Index now');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Successfully indexed 4 items.');

    // During indexing, alterIndexedItems() and preprocessIndexItems() should be
    // called on the processor.
    $processor_methods = $this->getCalledMethods('processor');
    $expected = ['alterIndexedItems', 'preprocessIndexItems'];
    $this->assertEquals($expected, $processor_methods);

    // hook_search_api_index_items_alter() was invoked, this removed node:1.
    // hook_search_api_query_TAG_alter() was invoked, this removed node:3.
    $this->assertSession()->pageTextContains('There are 2 items indexed on the server for this index.');
    $this->assertSession()->pageTextContains('Stormy');

    // hook_search_api_items_indexed() was invoked.
    $this->assertSession()->pageTextContains('Please set me at ease');

    // hook_search_api_index_reindex() was invoked.
    $this->drupalGet($this->getIndexPath('reindex'));
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('Montara');

    // hook_search_api_data_type_info_alter() was invoked.
    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertSession()->pageTextContains('Peace/Dolphin dance');
    // The implementation of hook_search_api_field_type_mapping_alter() has
    // removed all dates, so we can't see any timestamp anymore in the page.
    $url_options['query']['datasource'] = 'entity:node';
    $this->drupalGet($this->getIndexPath('fields/add/nojs'), $url_options);
    $this->assertSession()->pageTextContains('Add fields to index');
    $this->assertSession()->pageTextNotContains('timestamp');

    $this->drupalGet('search-api-test');
    $this->assertSession()->pageTextContains('Search id: views_page:search_api_test_view__page_1');
    // hook_search_api_query_alter() was invoked.
    $this->assertSession()->pageTextContains('Funky blue note');
    // hook_search_api_results_alter() was invoked.
    $this->assertSession()->pageTextContains('Stepping into tomorrow');
    // hook_search_api_results_TAG_alter() was invoked.
    $this->assertSession()->pageTextContains('Llama');

    // The query alter methods of the processor were called.
    $processor_methods = $this->getCalledMethods('processor');
    $expected = ['preprocessSearchQuery', 'postprocessSearchResults'];
    $this->assertEquals($expected, $processor_methods);

    // hook_search_api_server_features_alter() is triggered.
    $this->assertTrue($this->server->supportsFeature('welcome_to_the_jungle'));

    $displays = \Drupal::getContainer()->get('plugin.manager.search_api.display')
      ->getInstances();
    // hook_search_api_displays_alter was invoked.
    $display_label = $displays['views_page:search_api_test_view__page_1']->label();
    $this->assertEquals('Some funny label for testing', $display_label);
  }

}
