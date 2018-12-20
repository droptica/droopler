<?php

namespace Drupal\Tests\search_api\Kernel\System;

use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Kernel\PostRequestIndexingTrait;

/**
 * Tests Search API functionality when executed in the CLI.
 *
 * @group search_api
 */
class CliTest extends KernelTestBase {

  use PostRequestIndexingTrait;

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'user',
    'system',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Create a test server.
    $this->server = Server::create([
      'name' => 'Test server',
      'id' => 'test',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $this->server->save();

    // Disable the use of batches for item tracking to simulate a CLI
    // environment.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }
  }

  /**
   * Tests tracking of items when saving an index through the CLI.
   */
  public function testItemTracking() {
    EntityTestMulRevChanged::create([
      'name' => 'foo bar baz föö smile' . json_decode('"\u1F601"'),
      'body' => 'test test case Case casE',
      'type' => 'entity_test_mulrev_changed',
      'keywords' => ['Orange', 'orange', 'örange', 'Orange'],
      'category' => 'item_category',
    ])->save();
    EntityTestMulRevChanged::create([
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'entity_test_mulrev_changed',
      'keywords' => ['strawberry', 'llama'],
      'category' => 'item_category',
    ])->save();

    // Create a test index.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::create([
      'name' => 'Test index',
      'id' => 'index',
      'status' => 1,
      'datasource_settings' => [
        'entity:entity_test_mulrev_changed' => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
      'server' => $this->server->id(),
      'options' => ['index_directly' => TRUE],
    ]);
    $index->save();

    $total_items = $index->getTrackerInstance()->getTotalItemsCount();
    $indexed_items = $index->getTrackerInstance()->getIndexedItemsCount();

    $this->assertEquals(2, $total_items, 'The 2 items are tracked.');
    $this->assertEquals(0, $indexed_items, 'No items are indexed');

    EntityTestMulRevChanged::create([
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'entity_test_mulrev_changed',
      'keywords' => ['strawberry', 'llama'],
      'category' => 'item_category',
    ])->save();
    EntityTestMulRevChanged::create([
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'entity_test_mulrev_changed',
      'keywords' => ['strawberry', 'llama'],
      'category' => 'item_category',
    ])->save();

    $total_items = $index->getTrackerInstance()->getTotalItemsCount();
    $indexed_items = $index->getTrackerInstance()->getIndexedItemsCount();

    $this->assertEquals(4, $total_items, 'All 4 items are tracked.');
    $this->assertEquals(0, $indexed_items, 'No items are indexed.');

    $this->triggerPostRequestIndexing();

    $total_items = $index->getTrackerInstance()->getTotalItemsCount();
    $indexed_items = $index->getTrackerInstance()->getIndexedItemsCount();

    $this->assertEquals(4, $total_items, 'All 4 items are tracked.');
    $this->assertEquals(2, $indexed_items, '2 items are indexed.');
  }

}
