<?php

namespace Drupal\Tests\search_api\Kernel\Datasource;

use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;

/**
 * Tests task integration of the content entity datasource.
 *
 * @group search_api
 */
class DatasourceTaskTest extends KernelTestBase {

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
   * The test entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_mulrev_changed';

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

    // Enable translation for the entity_test module.
    \Drupal::state()->set('entity_test.translation', TRUE);

    // Define the bundles for our test entity type. (Should happen before we
    // install its entity schema.)
    $bundles = [
      'article' => [
        'label' => 'Article',
      ],
      'item' => [
        'label' => 'Item',
      ],
    ];
    \Drupal::state()->set($this->testEntityTypeId . '.bundles', $bundles);

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');

    $this->taskManager = $this->container->get('search_api.task_manager');

    // Create some languages.
    $this->installConfig(['language']);
    for ($i = 0; $i < 3; ++$i) {
      /** @var \Drupal\language\ConfigurableLanguageInterface $language */
      $language = ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => 'language - ' . $i,
        'weight' => $i,
      ]);
      $language->save();
    }

    $this->installConfig('search_api');

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

    $this->taskManager->deleteTasks();
  }

  /**
   * Tests that datasource config changes are reflected correctly.
   */
  public function testItemTranslations() {
    // Test retrieving language and translations when no translations are
    // available.
    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged $entity_1 */
    $uid = $this->container->get('current_user')->id();
    $entity_1 = EntityTestMulRevChanged::create([
      'id' => 1,
      'name' => 'test 1',
      'user_id' => $uid,
      'type' => 'item',
      'langcode' => 'l0',
    ]);
    $entity_1->save();
    $entity_1->addTranslation('l1')->save();
    $entity_1->addTranslation('l2')->save();

    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged $entity_2 */
    $entity_2 = EntityTestMulRevChanged::create([
      'id' => 2,
      'name' => 'test 2',
      'user_id' => $uid,
      'type' => 'article',
      'langcode' => 'l1',
    ]);
    $entity_2->save();
    $entity_2->addTranslation('l0')->save();
    $entity_2->addTranslation('l2')->save();

    $index = $this->index;
    $tracker = $index->getTrackerInstance();
    $datasource_id = 'entity:' . $this->testEntityTypeId;
    $datasource = $index->getDatasource($datasource_id);

    $get_ids = function (array $raw_ids) use ($datasource_id) {
      foreach ($raw_ids as $i => $id) {
        $raw_ids[$i] = Utility::createCombinedId($datasource_id, $id);
      }
      return $raw_ids;
    };

    $this->assertEquals(6, $tracker->getTotalItemsCount());
    $this->assertEquals(6, $tracker->getRemainingItemsCount());

    $configuration = [
      'bundles' => [
        'default' => TRUE,
        'selected' => [
          'item',
        ],
      ],
      'languages' => [
        'default' => FALSE,
        'selected' => [
          'l0',
          'l2',
        ],
      ],
    ];
    $datasource->setConfiguration($configuration);
    $index->save();

    $this->runBatch();

    $expected = $get_ids(['2:l0', '2:l2']);
    $this->assertEquals(count($expected), $tracker->getTotalItemsCount());
    $remaining = $tracker->getRemainingItems();
    sort($remaining);
    $this->assertEquals($expected, $remaining);

    $configuration['bundles']['default'] = FALSE;
    $configuration['bundles']['selected'][] = 'article';
    $configuration['languages']['selected'] = ['l0'];
    $datasource->setConfiguration($configuration);
    $index->save();

    $this->runBatch();

    $expected = $get_ids(['1:l0', '2:l0']);
    $this->assertEquals(count($expected), $tracker->getTotalItemsCount());
    $remaining = $tracker->getRemainingItems();
    sort($remaining);
    $this->assertEquals($expected, $remaining);

    $configuration['languages']['selected'][] = 'l1';
    $datasource->setConfiguration($configuration);
    $index->save();

    $this->runBatch();

    $expected = $get_ids(['1:l0', '1:l1', '2:l0', '2:l1']);
    $this->assertEquals(count($expected), $tracker->getTotalItemsCount());
    $remaining = $tracker->getRemainingItems();
    sort($remaining);
    $this->assertEquals($expected, $remaining);

    $configuration['bundles']['selected'] = ['article'];
    $datasource->setConfiguration($configuration);
    $index->save();

    $this->runBatch();

    $expected = $get_ids(['2:l0', '2:l1']);
    $this->assertEquals(count($expected), $tracker->getTotalItemsCount());
    $remaining = $tracker->getRemainingItems();
    sort($remaining);
    $this->assertEquals($expected, $remaining);
  }

  /**
   * Runs the currently set batch, if any.
   */
  protected function runBatch() {
    $batch = &batch_get();
    if ($batch) {
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }

}
