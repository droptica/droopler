<?php

namespace Drupal\Tests\search_api\Kernel\System;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Tests query functionality.
 *
 * @group search_api
 */
class QueryTest extends KernelTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'search_api_test_hooks',
    'language',
    'user',
    'system',
    'entity_test',
  ];

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Create a test server.
    $server = Server::create([
      'name' => 'Test Server',
      'id' => 'test_server',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $server->save();

    // Create a test index.
    Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => 1,
      'datasource_settings' => [
        'search_api_test' => [],
      ],
      'processor_settings' => [
        'search_api_test' => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
      'server' => $server->id(),
      'options' => ['index_directly' => FALSE],
    ])->save();
    $this->index = Index::load('test_index');
  }

  /**
   * Tests that processing levels are working correctly.
   *
   * @param int $level
   *   The processing level to test.
   * @param bool $hooks_and_processors_invoked
   *   (optional) Whether hooks and processors should be invoked with this
   *   processing level.
   *
   * @dataProvider testProcessingLevelDataProvider
   */
  public function testProcessingLevel($level, $hooks_and_processors_invoked = TRUE) {
    /** @var \Drupal\search_api\Processor\ProcessorInterface $processor */
    $processor = $this->container->get('plugin.manager.search_api.processor')
      ->createInstance('search_api_test', ['#index' => $this->index]);
    $this->index->addProcessor($processor)->save();

    $query = $this->index->query();
    if ($level != QueryInterface::PROCESSING_FULL) {
      $query->setProcessingLevel($level);
    }
    $this->assertEquals($level, $query->getProcessingLevel());
    $query->addTag('andrew_hill');

    \Drupal::messenger()->deleteAll();
    $query->execute();
    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();

    $methods = $this->getCalledMethods('processor');
    if ($hooks_and_processors_invoked) {
      $expected = [
        MessengerInterface::TYPE_STATUS => [
          'Funky blue note',
          'Search id: ',
          'Stepping into tomorrow',
          'Llama',
        ],
      ];
      $this->assertEquals($expected, $messages);
      $this->assertTrue($query->getOption('tag query alter hook'));
      $this->assertContains('preprocessSearchQuery', $methods);
      $this->assertContains('postprocessSearchResults', $methods);
    }
    else {
      $this->assertEmpty($messages);
      $this->assertFalse($query->getOption('tag query alter hook'));
      $this->assertNotContains('preprocessSearchQuery', $methods);
      $this->assertNotContains('postprocessSearchResults', $methods);
    }
  }

  /**
   * Provides test data for the testProcessingLevel() method.
   *
   * @return array[]
   *   Arrays of method arguments for the
   *   \Drupal\Tests\search_api\Kernel\QueryTest::testProcessingLevel() method.
   */
  public function testProcessingLevelDataProvider() {
    return [
      'none' => [QueryInterface::PROCESSING_NONE, FALSE],
      'basic' => [QueryInterface::PROCESSING_BASIC],
      'full' => [QueryInterface::PROCESSING_FULL],
    ];
  }

  /**
   * Tests that queries can be cloned.
   */
  public function testQueryCloning() {
    $query = $this->index->query();
    $this->assertEquals(0, $query->getResults()->getResultCount());
    $cloned_query = clone $query;
    $cloned_query->getResults()->setResultCount(1);
    $this->assertEquals(0, $query->getResults()->getResultCount());
    $this->assertEquals(1, $cloned_query->getResults()->getResultCount());
  }

  /**
   * Tests that serialization of queries works correctly.
   */
  public function testQuerySerialization() {
    $query = Query::create($this->index);
    $tags = ['tag1', 'tag2'];
    $query->keys('foo bar')
      ->addCondition('field1', 'value', '<')
      ->addCondition('field2', [15, 25], 'BETWEEN')
      ->addConditionGroup($query->createConditionGroup('OR', $tags)
        ->addCondition('field2', 'foo')
        ->addCondition('field3', 1, '<>')
      )
      ->sort('field1', Query::SORT_DESC)
      ->sort('field2');
    $query->setOption('option1', ['foo' => 'bar']);
    $translation = $this->container->get('string_translation');
    $query->setStringTranslation($translation);

    $cloned_query = clone $query;
    $unserialized_query = unserialize(serialize($query));
    $this->assertEquals($cloned_query, $unserialized_query);
  }

  /**
   * Tests that the results cache works correctly.
   */
  public function testResultsCache() {
    /** @var \Drupal\search_api\Query\QueryInterface[] $results */
    $results = [];
    $search_ids = ['foo', 'bar'];
    foreach ($search_ids as $search_id) {
      $results[$search_id] = $this->index->query()
        ->setSearchId($search_id)
        ->execute();
    }

    $results_cache = \Drupal::getContainer()
      ->get('search_api.query_helper');
    foreach ($search_ids as $search_id) {
      $this->assertSame($results[$search_id], $results_cache->getResults($search_id));
    }
    $this->assertNull($results_cache->getResults('foobar'));

    $this->assertSame($results, $results_cache->getAllResults());

    $results_cache->removeResults('foo');
    unset($results['foo']);
    $this->assertSame($results, $results_cache->getAllResults());
  }

  /**
   * Tests whether the display plugin integration works correctly.
   */
  public function testDisplayPluginIntegration() {
    $query = $this->index->query();
    $this->assertSame(NULL, $query->getSearchId(FALSE));
    $this->assertSame('search_1', $query->getSearchId());
    $this->assertSame('search_1', $query->getSearchId(FALSE));
    $this->assertSame(NULL, $query->getDisplayPlugin());

    $query = $this->index->query()->setSearchId('search_api_test');
    $this->assertInstanceOf('Drupal\search_api_test\Plugin\search_api\display\TestDisplay', $query->getDisplayPlugin());
  }

  /**
   * Tests the getOriginalQuery() method.
   */
  public function testGetOriginalQuery() {
    $this->getCalledMethods('backend');

    $query = $this->index->query()
      ->addCondition('search_api_id', 2, '<>');
    $query_clone_1 = $query->getOriginalQuery();
    $this->assertEquals($query, $query_clone_1);
    $this->assertNotSame($query, $query_clone_1);

    $query->sort('search_api_id');
    $query_clone_2 = clone $query;
    $query->execute();
    $methods = $this->getCalledMethods('backend');
    $this->assertEquals(['search'], $methods);
    $this->assertFalse($query_clone_1->hasExecuted());

    $original_query = $query->getOriginalQuery();
    $this->assertEquals($query_clone_2, $original_query);
    $this->assertFalse($original_query->hasExecuted());
    $original_query->execute();
    $methods = $this->getCalledMethods('backend');
    $this->assertEquals(['search'], $methods);
  }

}
