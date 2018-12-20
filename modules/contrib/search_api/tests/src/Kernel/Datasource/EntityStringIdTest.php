<?php

namespace Drupal\Tests\search_api\Kernel\Datasource;

use Drupal\entity_test\Entity\EntityTestStringId;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;

/**
 * Tests indexing entities that use string IDs.
 *
 * The current limit for item IDs in the Search API is 50 characters. The format
 * of the generated ID is entity:<entity_type_id>/<entity_id>:<language_code>.
 *
 * @group search_api
 */
class EntityStringIdTest extends KernelTestBase {

  /**
   * The test entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_string_id';

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
   * An array of language codes.
   *
   * @var string[]
   */
  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', [
      'search_api_item',
    ]);
    $this->installEntitySchema('entity_test_string_id');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    // Create a test server.
    $this->server = Server::create([
      'name' => 'Test Server',
      'id' => 'test_server',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $this->server->save();

    // Create a test index.
    $this->index = Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => 1,
      'datasource_settings' => [
        'entity:' . $this->testEntityTypeId => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
      'server' => $this->server->id(),
      'options' => ['index_directly' => FALSE],
    ]);
    $this->index->save();
  }

  /**
   * Tests indexing of entities with string IDs.
   *
   * @param string $entity_id
   *   An entity ID for which to check indexing.
   *
   * @dataProvider entityStringIdList
   */
  public function testUriStringId($entity_id) {
    $entity = EntityTestStringId::create([
      'id' => $entity_id,
      'name' => 'String Test',
      'user_id' => $this->container->get('current_user')->id(),
    ]);
    $entity->save();

    // Test that the datasource returns the correct item IDs.
    $datasource = $this->index->getDatasource('entity:' . $this->testEntityTypeId);
    $datasource_item_ids = $datasource->getItemIds();
    $expected = [
      $entity_id . ':und',
    ];
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids.');

    // Test indexing the new entity.
    $this->assertEquals(0, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The index is empty.');
    $this->assertEquals(1, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There is one item to be indexed.');
    $this->index->indexItems();
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'One item has been indexed.');
  }

  /**
   * Provides string IDs to test.
   *
   * @return array
   *   An array of arrays which contain a list of parameters to be passed to the
   *   testUriStringId() test method.
   */
  public function entityStringIdList() {
    return [
      'Normal machine name' => ['short_string_id'],
      'URL ID (with special characters)' => ['http://drupal.org'],
      'Long ID' => [str_repeat('a', 100)],
    ];
  }

}
