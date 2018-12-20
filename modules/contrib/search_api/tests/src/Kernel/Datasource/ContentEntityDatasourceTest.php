<?php

namespace Drupal\Tests\search_api\Kernel\Datasource;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests correct functionality of the content entity datasource.
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\search_api\datasource\ContentEntity
 *
 * @group search_api
 */
class ContentEntityDatasourceTest extends KernelTestBase {

  use ExampleContentTrait;

  /**
   * The entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_mulrev_changed';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
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
   * The datasource used for testing.
   *
   * @var \Drupal\search_api\Plugin\search_api\datasource\EntityDatasourceInterface
   */
  protected $datasource;

  /**
   * The item IDs of all items that can be part of the datasource.
   *
   * @var string[]
   */
  protected $allItemIds;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable translation for the entity_test module.
    \Drupal::state()->set('entity_test.translation', TRUE);

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installConfig(['language']);

    // Create some languages.
    for ($i = 0; $i < 2; ++$i) {
      ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => 'language - ' . $i,
        'weight' => $i,
      ])->save();
    }

    // Create a test index.
    $this->index = Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => FALSE,
      'datasource_settings' => [
        'entity:' . $this->testEntityTypeId => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
    ]);
    $this->datasource = $this->index->getDatasource('entity:' . $this->testEntityTypeId);

    $this->setUpExampleStructure();

    foreach (['item', 'article'] as $i => $bundle) {
      $entity = EntityTestMulRevChanged::create([
        'id' => $i + 1,
        'type' => $bundle,
        'langcode' => 'l0',
      ]);
      $entity->save();
      $entity->addTranslation('l1')->save();
    }

    $this->allItemIds = ['1:l0', '1:l1', '2:l0', '2:l1'];
  }

  /**
   * Tests entity loading.
   *
   * @covers ::loadMultiple
   */
  public function testEntityLoading() {
    $loaded_items = $this->datasource->loadMultiple($this->allItemIds);
    $this->assertCorrectItems($this->allItemIds, $loaded_items);

    $this->datasource->setConfiguration([
      'bundles' => [
        'default' => FALSE,
        'selected' => ['item'],
      ],
      'languages' => [
        'default' => TRUE,
        'selected' => ['l0'],
      ],
    ]);
    $loaded_items = $this->datasource->loadMultiple($this->allItemIds);
    $this->assertCorrectItems(['1:l1'], $loaded_items);

    $this->datasource->setConfiguration([
      'bundles' => [
        'default' => TRUE,
        'selected' => ['item'],
      ],
      'languages' => [
        'default' => FALSE,
        'selected' => ['l0', 'l1'],
      ],
    ]);
    $loaded_items = $this->datasource->loadMultiple($this->allItemIds);
    $this->assertCorrectItems(['2:l0', '2:l1'], $loaded_items);
  }

  /**
   * Asserts that the given array of loaded items is correct.
   *
   * @param string[] $expected_ids
   *   The expected item IDs, sorted.
   * @param \Drupal\Core\TypedData\ComplexDataInterface[] $loaded_items
   *   The loaded items.
   */
  protected function assertCorrectItems(array $expected_ids, array $loaded_items) {
    $loaded_ids = array_keys($loaded_items);
    sort($loaded_ids);
    $this->assertEquals($expected_ids, $loaded_ids);

    foreach ($loaded_items as $item_id => $item) {
      $this->assertInstanceOf(EntityAdapter::class, $item);
      $entity = $item->getValue();
      $this->assertInstanceOf(EntityTestMulRevChanged::class, $entity);
      list($id, $langcode) = explode(':', $item_id);
      $this->assertEquals($id, $entity->id());
      $this->assertEquals($langcode, $entity->language()->getId());
    }
  }

  /**
   * Tests viewing of items.
   *
   * @covers ::viewItem
   * @covers ::viewMultipleItems
   */
  public function testItemViewing() {
    $loaded_items = $this->datasource->loadMultiple($this->allItemIds);
    $builder = \Drupal::entityTypeManager()
      ->getViewBuilder('entity_test_mulrev_changed');

    $item = $loaded_items['1:l0'];
    $build = $this->datasource->viewItem($item, 'foobar');
    $expected = [
      '#entity_test_mulrev_changed' => $item->getValue(),
      '#view_mode' => 'foobar',
      '#cache' => [
        'tags' => [
          'entity_test_mulrev_changed:1',
          'entity_test_mulrev_changed_view',
        ],
        'contexts' => [],
        'max-age' => -1,
      ],
      '#weight' => 0,
      '#pre_render' => [
        [$builder, 'build'],
      ],
    ];
    $this->assertEquals($expected, $build);

    $build = $this->datasource->viewMultipleItems($loaded_items, 'foobar');
    $this->assertTrue($build['#sorted']);
    $this->assertEquals([[$builder, 'buildMultiple']], $build['#pre_render']);
    foreach ($loaded_items as $item_id => $item) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $item->getValue();
      $expected = [
        '#entity_test_mulrev_changed' => $entity,
        '#view_mode' => 'foobar',
        '#cache' => [
          'tags' => [
            'entity_test_mulrev_changed:' . $entity->id(),
            'entity_test_mulrev_changed_view',
          ],
          'contexts' => [],
          'max-age' => -1,
        ],
      ];
      $this->assertArrayHasKey('#weight', $build[$item_id]);
      unset($build[$item_id]['#weight']);
      $this->assertEquals($expected, $build[$item_id]);
    }
  }

  /**
   * Verifies that paged item discovery works correctly.
   *
   * @covers ::getPartialItemIds
   */
  public function testItemDiscovery() {
    // Set page size to 1 to also test paging.
    \Drupal::configFactory()
      ->getEditable('search_api.settings')
      ->set('tracking_page_size', 1)
      ->save();

    // Test item discovery with various bundle/language combinations.
    $discovered_ids = $this->getItemIds();
    $this->assertEquals($this->allItemIds, $discovered_ids);

    $discovered_ids = $this->getItemIds(['item']);
    $this->assertEquals(['1:l0', '1:l1'], $discovered_ids);

    $discovered_ids = $this->getItemIds(['item'], []);
    $this->assertEquals(['1:l0', '1:l1'], $discovered_ids);

    $discovered_ids = $this->getItemIds(NULL, ['l0']);
    $this->assertEquals(['1:l0', '2:l0'], $discovered_ids);

    $discovered_ids = $this->getItemIds([], ['l0']);
    $this->assertEquals(['1:l0', '2:l0'], $discovered_ids);

    $discovered_ids = $this->getItemIds(['item'], ['l0']);
    $this->assertEquals(['1:l0', '1:l1', '2:l0'], $discovered_ids);

    $discovered_ids = $this->getItemIds(['item', 'article'], ['l0']);
    $this->assertEquals($this->allItemIds, $discovered_ids);

    $discovered_ids = $this->getItemIds(['item'], ['l0', 'l1']);
    $this->assertEquals($this->allItemIds, $discovered_ids);

    $discovered_ids = $this->getItemIds(['item', 'article'], []);
    $this->assertEquals($this->allItemIds, $discovered_ids);

    $discovered_ids = $this->getItemIds([], ['l0', 'l1']);
    $this->assertEquals($this->allItemIds, $discovered_ids);

    $discovered_ids = $this->getItemIds([], []);
    $this->assertEquals([], $discovered_ids);

    $discovered_ids = $this->getItemIds([], NULL);
    $this->assertEquals([], $discovered_ids);

    $discovered_ids = $this->getItemIds(NULL, []);
    $this->assertEquals([], $discovered_ids);
  }

  /**
   * Retrieves the IDs of all matching items from the test datasource.
   *
   * Will automatically use paging to go through the entire result set.
   *
   * If both $bundles and $languages are specified, they are combined with OR.
   *
   * @param string[]|null $bundles
   *   (optional) The bundles for which all item IDs should be returned; or NULL
   *   to retrieve IDs from all enabled bundles in this datasource.
   * @param string[]|null $languages
   *   (optional) The languages for which all item IDs should be returned; or
   *   NULL to retrieve IDs from all enabled languages in this datasource.
   *
   * @return string[]
   *   All discovered item IDs.
   *
   * @see \Drupal\search_api\Plugin\search_api\datasource\EntityDatasourceInterface::getPartialItemIds()
   */
  protected function getItemIds(array $bundles = NULL, array $languages = NULL) {
    $discovered_ids = [];
    for ($page = 0;; ++$page) {
      $new_ids = $this->datasource->getPartialItemIds($page, $bundles, $languages);
      if ($new_ids === NULL) {
        break;
      }
      $discovered_ids = array_merge($discovered_ids, $new_ids);
    }
    sort($discovered_ids);
    return $discovered_ids;
  }

}
