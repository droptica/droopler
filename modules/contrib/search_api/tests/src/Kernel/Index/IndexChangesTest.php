<?php

namespace Drupal\Tests\search_api\Kernel\Index;

use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_test\PluginTestTrait;
use Drupal\user\Entity\User;

/**
 * Tests correct reactions to changes for the index.
 *
 * @group search_api
 */
class IndexChangesTest extends KernelTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'language',
    'user',
    'system',
    'entity_test',
  ];

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The test entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_mulrev_changed';

  /**
   * The task manager to use for the tests.
   *
   * @var \Drupal\search_api\Task\TaskManagerInterface
   */
  protected $taskManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', [
      'search_api_item',
    ]);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('user');
    $this->installConfig('search_api');

    $this->taskManager = $this->container->get('search_api.task_manager');

    User::create([
      'uid' => 1,
      'name' => 'root',
      'langcode' => 'en',
    ])->save();

    EntityTestMulRevChanged::create([
      'id' => 1,
      'name' => 'test 1',
    ])->save();

    // Create a test server.
    $this->server = Server::create([
      'name' => 'Test Server',
      'id' => 'test_server',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $this->server->save();

    // Create a test index (but don't save it yet).
    $this->index = Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => 1,
      'tracker_settings' => [
        'default' => [],
      ],
      'datasource_settings' => [
        'entity:user' => [],
        'entity:entity_test_mulrev_changed' => [],
      ],
      'server' => $this->server->id(),
      'options' => ['index_directly' => FALSE],
    ]);

    $this->taskManager->deleteTasks();
  }

  /**
   * Tests correct reactions when a new datasource is added.
   */
  public function testDatasourceAdded() {
    $this->index->set('datasource_settings', [
      'entity:user' => [],
    ]);
    $this->index->save();

    $tracker = $this->index->getTrackerInstance();

    $expected = [
      Utility::createCombinedId('entity:user', '1:en'),
    ];
    $this->assertEquals($expected, $tracker->getRemainingItems());

    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createDatasourcePlugin($this->index, 'entity:entity_test_mulrev_changed');
    $this->index->addDatasource($datasource)->save();

    $this->taskManager->executeAllTasks();

    $expected = [
      Utility::createCombinedId('entity:entity_test_mulrev_changed', '1:en'),
      Utility::createCombinedId('entity:user', '1:en'),
    ];
    $remaining_items = $tracker->getRemainingItems();
    sort($remaining_items);
    $this->assertEquals($expected, $remaining_items);

    User::create([
      'uid' => 2,
      'name' => 'someone',
      'langcode' => 'en',
    ])->save();
    EntityTestMulRevChanged::create([
      'id' => 2,
      'name' => 'test 2',
    ])->save();

    $expected = [
      Utility::createCombinedId('entity:entity_test_mulrev_changed', '1:en'),
      Utility::createCombinedId('entity:entity_test_mulrev_changed', '2:en'),
      Utility::createCombinedId('entity:user', '1:en'),
      Utility::createCombinedId('entity:user', '2:en'),
    ];
    $remaining_items = $tracker->getRemainingItems();
    sort($remaining_items);
    $this->assertEquals($expected, $remaining_items);

    $this->getCalledMethods('backend');
    $indexed = $this->index->indexItems();
    $this->assertEquals(4, $indexed);
    $this->assertEquals(['indexItems'], $this->getCalledMethods('backend'));

    $indexed_items = array_keys($this->getIndexedItems());
    sort($indexed_items);
    $this->assertEquals($expected, $indexed_items);
    $this->assertEquals(0, $tracker->getRemainingItemsCount());
  }

  /**
   * Tests correct reactions when a datasource is removed.
   */
  public function testDatasourceRemoved() {
    $info = [
      'datasource_id' => 'entity:entity_test_mulrev_changed',
      'property_path' => 'id',
    ];
    $field = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createField($this->index, 'id', $info);
    $this->index->addField($field);

    $processor = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createProcessorPlugin($this->index, 'search_api_test');
    $this->index->addProcessor($processor);
    $this->setMethodOverride('processor', 'supportsIndex', function (IndexInterface $index) {
      return in_array('entity:entity_test_mulrev_changed', $index->getDatasourceIds());
    });

    $this->index->save();

    $this->assertArrayHasKey('search_api_test', $this->index->getProcessors());

    $tracker = $this->index->getTrackerInstance();

    $expected = [
      Utility::createCombinedId('entity:entity_test_mulrev_changed', '1:en'),
      Utility::createCombinedId('entity:user', '1:en'),
    ];
    $remaining_items = $tracker->getRemainingItems();
    sort($remaining_items);
    $this->assertEquals($expected, $remaining_items);

    $this->getCalledMethods('backend');
    $indexed = $this->index->indexItems();
    $this->assertEquals(2, $indexed);
    $this->assertEquals(['indexItems'], $this->getCalledMethods('backend'));

    $indexed_items = array_keys($this->getIndexedItems());
    sort($indexed_items);
    $this->assertEquals($expected, $indexed_items);
    $this->assertEquals(0, $tracker->getRemainingItemsCount());

    $this->index->removeDatasource('entity:entity_test_mulrev_changed')->save();

    $this->assertArrayNotHasKey('id', $this->index->getFields());
    $this->assertArrayNotHasKey('search_api_test', $this->index->getProcessors());

    $this->assertEquals(1, $tracker->getTotalItemsCount());

    $expected = [
      Utility::createCombinedId('entity:user', '1:en'),
    ];
    $indexed_items = array_keys($this->getIndexedItems());
    sort($indexed_items);
    $this->assertEquals($expected, $indexed_items);
    $this->assertEquals(['updateIndex', 'deleteAllIndexItems'], $this->getCalledMethods('backend'));

    User::create([
      'uid' => 2,
      'name' => 'someone',
      'langcode' => 'en',
    ])->save();
    EntityTestMulRevChanged::create([
      'id' => 2,
      'name' => 'test 2',
    ])->save();

    $this->assertEquals(2, $tracker->getTotalItemsCount());

    $indexed = $this->index->indexItems();
    $this->assertGreaterThanOrEqual(1, $indexed);
    $this->assertEquals(['indexItems'], $this->getCalledMethods('backend'));

    $expected = [
      Utility::createCombinedId('entity:user', '1:en'),
      Utility::createCombinedId('entity:user', '2:en'),
    ];
    $indexed_items = array_keys($this->getIndexedItems());
    sort($indexed_items);
    $this->assertEquals($expected, $indexed_items);
    $this->assertEquals(0, $tracker->getRemainingItemsCount());
  }

  /**
   * Tests correct reaction when the index's tracker changes.
   */
  public function testTrackerChange() {
    $this->index->save();

    /** @var \Drupal\search_api\Tracker\TrackerInterface $tracker */
    $tracker = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createTrackerPlugin($this->index, 'search_api_test');
    $this->index->setTracker($tracker)->save();

    $this->taskManager->executeAllTasks();

    $methods = $this->getCalledMethods('tracker');
    $expected = [
      'trackItemsInserted',
      'trackItemsInserted',
    ];
    $this->assertEquals($expected, $methods);

    /** @var \Drupal\search_api\Tracker\TrackerInterface $tracker */
    $tracker = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createTrackerPlugin($this->index, 'default');
    $this->index->setTracker($tracker)->save();

    $this->taskManager->executeAllTasks();

    $methods = $this->getCalledMethods('tracker');
    $this->assertEquals(['trackAllItemsDeleted'], $methods);
    $arguments = $this->getMethodArguments('tracker', 'trackAllItemsDeleted');
    $this->assertEquals([], $arguments);
  }

  /**
   * Tests correct reaction when a processor adding a property is removed.
   */
  public function testPropertyProcessorRemoved() {
    $processor = $this->container
      ->get('plugin.manager.search_api.processor')
      ->createInstance('add_url', [
        '#index' => $this->index,
      ]);
    $this->index->addProcessor($processor);

    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $info = [
      'datasource_id' => 'entity:entity_test_mulrev_changed',
      'property_path' => 'id',
    ];
    $this->index->addField($fields_helper->createField($this->index, 'id', $info));
    $info = [
      'property_path' => 'search_api_url',
    ];
    $this->index->addField($fields_helper->createField($this->index, 'url', $info));

    $this->index->save();

    $fields = array_keys($this->index->getFields());
    sort($fields);
    $this->assertEquals(['id', 'url'], $fields);

    $this->index->removeProcessor('add_url')->save();

    $fields = array_keys($this->index->getFields());
    $this->assertEquals(['id'], $fields);
  }

  /**
   * Tests correct reaction when a bundle containing a property is removed.
   */
  public function testPropertyBundleRemoved() {
    entity_test_create_bundle('bundle1', NULL, 'entity_test_mulrev_changed');
    entity_test_create_bundle('bundle2', NULL, 'entity_test_mulrev_changed');

    $this->enableModules(['field', 'text']);
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installConfig('field');

    FieldStorageConfig::create([
      'field_name' => 'field1',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field1',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'bundle1',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field2',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field2',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'bundle2',
    ])->save();

    $datasource_id = 'entity:entity_test_mulrev_changed';
    $datasource = $this->container
      ->get('plugin.manager.search_api.datasource')
      ->createInstance($datasource_id, [
        '#index' => $this->index,
        'bundles' => [
          'default' => TRUE,
          'selected' => [],
        ],
      ]);
    $this->index->setDatasources([$datasource_id => $datasource]);

    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $info = [
      'datasource_id' => $datasource_id,
      'property_path' => 'field1',
    ];
    $this->index->addField($fields_helper->createField($this->index, 'field1', $info));
    $info = [
      'datasource_id' => $datasource_id,
      'property_path' => 'field2',
    ];
    $this->index->addField($fields_helper->createField($this->index, 'field2', $info));

    $this->index->save();

    $fields = array_keys($this->index->getFields());
    sort($fields);
    $this->assertEquals(['field1', 'field2'], $fields);

    $this->index->getDatasource($datasource_id)->setConfiguration([
      'bundles' => [
        'default' => TRUE,
        'selected' => ['bundle2'],
      ],
    ]);
    $this->index->save();

    $fields = array_keys($this->index->getFields());
    $this->assertEquals(['field1'], $fields);
  }

  /**
   * Tests correct behavior when a field ID is changed.
   */
  public function testFieldRenamed() {
    $datasource_id = 'entity:entity_test_mulrev_changed';
    $info = [
      'datasource_id' => $datasource_id,
      'property_path' => 'name',
    ];
    $field = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createField($this->index, 'name', $info);
    $this->index->addField($field);
    $this->assertEquals([], $this->index->getFieldRenames());

    $this->index->renameField('name', 'name1');
    $this->assertEquals(['name1' => $field], $this->index->getFields());
    $this->assertEquals(['name' => 'name1'], $this->index->getFieldRenames());

    // Saving resets the field IDs.
    $this->index->save();
    $this->assertEquals([], $this->index->getFieldRenames());
    $this->assertEquals('name1', $this->index->getField('name1')->getOriginalFieldIdentifier());
  }

  /**
   * Retrieves the indexed items from the test backend.
   *
   * @return array
   *   The indexed items, keyed by their item IDs and containing associative
   *   arrays with their field values.
   */
  protected function getIndexedItems() {
    $key = 'search_api_test.backend.indexed.' . $this->index->id();
    return \Drupal::state()->get($key, []);
  }

}
