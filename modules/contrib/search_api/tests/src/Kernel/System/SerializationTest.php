<?php

namespace Drupal\Tests\search_api\Kernel\System;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\Item;

/**
 * Tests that various classes can be properly serialized and/or cloned.
 *
 * @group search_api
 */
class SerializationTest extends KernelTestBase {

  /**
   * A test index for use in these tests.
   *
   * @var \Drupal\search_api\IndexInterface|null
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'node',
    'user',
    'system',
  ];

  /**
   * Tests that serialization of index entities doesn't lead to data loss.
   */
  public function testIndexSerialization() {
    $index = $this->createTestIndex();

    // Make some changes to the index to ensure they're saved, too.
    $field_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $field_info = [
      'type' => 'date',
      'datasource_id' => 'entity:node',
      'property_path' => 'uid:entity:created',
    ];
    $index->addField($field_helper->createField($index, 'test1', $field_info));
    $plugin_creation_helper = \Drupal::getContainer()
      ->get('search_api.plugin_helper');
    $index->addDatasource($plugin_creation_helper->createDatasourcePlugin($index, 'entity:user'));
    $index->addProcessor($plugin_creation_helper->createProcessorPlugin($index, 'highlight'));
    $index->setTracker($plugin_creation_helper->createTrackerPlugin($index, 'search_api_test'));

    /** @var \Drupal\search_api\IndexInterface $serialized */
    $serialized = unserialize(serialize($index));

    $this->assertNotEmpty($serialized);
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $index->preSave($storage);
    $serialized->preSave($storage);
    $this->assertEquals($index->toArray(), $serialized->toArray());

    // Make sure no object properties will be serialized for an index.
    $index->getDatasources();
    $index->getFields();
    $index->getProcessors();
    $index->getTrackerInstance();
    $index->getPropertyDefinitions(NULL);

    $contains_object = function ($var) use (&$contains_object) {
      if (is_object($var)) {
        return TRUE;
      }
      if (is_array($var)) {
        foreach ($var as $key => $value) {
          if ($contains_object($value)) {
            return TRUE;
          }
        }
      }
      return FALSE;
    };
    $to_serialize = $index->__sleep();
    foreach ($to_serialize as $property) {
      $this->assertFalse($contains_object($index->get($property)), "Serialized property \$$property contains an object.");
    }
  }

  /**
   * Tests that serialization of server entities doesn't lead to data loss.
   */
  public function testServerSerialization() {
    // As our test server, just use the one from the DB Defaults module.
    $path = __DIR__ . '/../../../../modules/search_api_db/search_api_db_defaults/config/optional/search_api.server.default_server.yml';
    $values = Yaml::decode(file_get_contents($path));
    $server = new Server($values, 'search_api_server');

    $serialized = unserialize(serialize($server));

    $this->assertNotEmpty($serialized);
    $this->assertEquals($server, $serialized);
  }

  /**
   * Tests that serialization of search queries works correctly.
   */
  public function testQuerySerialization() {
    $query = $this->createTestQuery();
    $this->setMockIndexStorage();

    $serialized = unserialize(serialize($query));

    $this->assertNotEmpty($serialized);
    $this->assertEquals((string) $query, (string) $serialized);

    // Call serialize() on the restored query to make "equals" work correctly.
    // (__sleep() sets some properties as a by-product which the serialized
    // version doesn't have – namely, $indexId and $_serviceIds.)
    serialize($serialized);
    $this->assertEquals($query, $serialized);
  }

  /**
   * Tests that cloning of search queries works correctly.
   */
  public function testQueryCloning() {
    $query = $this->createTestQuery();
    // Since Drupal's DB layer sometimes has problems with side-effects of
    // __toString(), we here try to make sure this won't happen to us.
    $this->assertInternalType('string', (string) $query);

    $clone = clone $query;

    // Modify the original query. None of this should change the clone in any
    // way.
    $query->setOption('test1', 'foo');
    $query->getParseMode()->setConjunction('AND');
    $query->addCondition('test1', 'bar');
    $condition_group_1 = $query->getConditionGroup()->getConditions()[1];
    $condition_group_2 = $condition_group_1->getConditions()[2];
    $condition_group_3 = $query->createConditionGroup('AND');
    $condition_group_1->addCondition('test1', 'foobar');
    $condition_group_2->addCondition('test1', 'foobar');
    $condition_group_3->addCondition('test1', 'foobar');
    $query->getResults()->addWarning('This query is very dumb.');

    $query_2 = $this->createTestQuery();
    $this->assertEquals($query_2, $clone);
    $this->assertNotSame($query->getResults(), $clone->getResults());
    $this->assertNotSame($query->getResults(), $query_2->getResults());
    $this->assertNotSame($query->getConditionGroup(), $clone->getConditionGroup());
    $this->assertNotSame($query->getConditionGroup(), $query_2->getConditionGroup());
    $this->assertNotSame($query->getParseMode(), $clone->getParseMode());
    $this->assertNotSame($query->getParseMode(), $query_2->getParseMode());
  }

  /**
   * Tests that cloning of items works correctly.
   */
  public function testItemCloning() {
    $item = $this->createTestItem();

    $clone = clone $item;

    $item->setBoost(3);
    $item->setExcerpt('Test 1');
    $item->getExtraData('foo')->bar = 2;
    $item->setExtraData('test', 3);
    $item->setLanguage('de');
    $item->setScore(3.14);
    $item->getField('test')->setLabel('Foobar');

    $item_2 = $this->createTestItem();
    $this->assertEquals($item_2, $clone);
    $this->assertNotSame($item->getExtraData('foo'), $clone->getExtraData('foo'));
    $this->assertNotSame($item->getField('test'), $clone->getField('test'));
    $this->assertNotSame($item->getField('foo'), $clone->getField('foo'));
  }

  /**
   * Tests that serialization of fields works correctly.
   */
  public function testFieldSerialization() {
    $field = $this->createTestField('test', 'entity:entity:entity_test_mulrev_changed');
    $this->setMockIndexStorage();

    $serialized = unserialize(serialize($field));

    // Call serialize() on the restored query to make "equals" work correctly.
    // (__sleep() sets some properties as a by-product which the serialized
    // version doesn't have – $indexId, in this case.)
    serialize($serialized);

    $this->assertEquals($field, $serialized);
  }

  /**
   * Creates a search query for use in this test.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A test query.
   */
  protected function createTestQuery() {
    $query = $this->createTestIndex()->query([
      'foo' => 'bar',
    ]);

    $query->getParseMode()->setConjunction('OR');
    $query->keys('test foobar');
    $query->setFulltextFields(['foo', 'bar']);

    $query->addCondition('title', 'foo', '<>');
    $condition_group_1 = $query->createConditionGroup('OR', ['foobar']);
    $condition_group_1->addCondition('foo', 'bar');
    $query->addConditionGroup($condition_group_1);
    $condition_group_1->addCondition('bar', [1, 5], 'BETWEEN');
    $condition_group_2 = $query->createConditionGroup('AND', ['baz']);
    $condition_group_2->addCondition('baz', 2, '>');
    $condition_group_2->addCondition('baz', NULL, '<>');
    $condition_group_1->addConditionGroup($condition_group_2);

    $query->addTag('serialization_test');

    $query->getResults()->addWarning('This query is dumb.');

    return $query;
  }

  /**
   * Creates an item for testing purposes.
   *
   * @return \Drupal\search_api\Item\ItemInterface
   *   A test item.
   */
  protected function createTestItem() {
    $index = $this->createTestIndex();
    $datasource = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createDatasourcePlugin($index, 'entity:user');
    $item = new Item($index, 'id', $datasource);

    $item->setBoost(2);
    $item->setExcerpt('Foo bar baz');
    $item->setExtraData('foo', (object) ['bar' => 1]);
    $item->setExtraData('test', 1);
    $item->setLanguage('en');
    $item->setScore(4);
    $item->setFields([
      'test' => $this->createTestField(),
      'foo' => $this->createTestField('foo', 'entity:entity_test_mulrev_changed'),
    ]);
    $item->setFieldsExtracted(TRUE);

    return $item;
  }

  /**
   * Creates a field for testing purposes.
   *
   * @param string $id
   *   (optional) The field ID (and property path).
   * @param string|null $datasource_id
   *   (optional) The field's datasource ID.
   *
   * @return \Drupal\search_api\Item\FieldInterface
   *   A test field.
   */
  protected function createTestField($id = 'test', $datasource_id = NULL) {
    $index = $this->createTestIndex();

    $field = new Field($index, $id);
    $field->setDatasourceId($datasource_id);
    $field->setPropertyPath($id);
    $field->setLabel('Foo');
    $field->setDescription('Bar');
    $field->setType('float');
    $field->setBoost(2);
    $field->setIndexedLocked();
    $field->setConfiguration([
      'foo' => 'bar',
      'test' => TRUE,
    ]);
    $field->setValues([1, 3, 5]);

    return $field;
  }

  /**
   * Creates an index entity object for testing purposes.
   *
   * @return \Drupal\search_api\Entity\Index
   *   A test index.
   */
  protected function createTestIndex() {
    if (!$this->index) {
      // As our test index, just use the one from the DB Defaults module.
      $path = __DIR__ . '/../../../../modules/search_api_db/search_api_db_defaults/config/optional/search_api.index.default_index.yml';
      $index_values = Yaml::decode(file_get_contents($path));
      $index = new Index($index_values, 'search_api_index');
      $this->index = $index;
    }

    return $this->index;
  }

  /**
   * Sets a mock entity type manager to be able to load the test index.
   */
  protected function setMockIndexStorage() {
    $index = $this->createTestIndex();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturnMap([
      [$index->id(), $index],
    ]);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturnMap([
      ['search_api_index', $storage],
    ]);
    \Drupal::getContainer()->set('entity_type.manager', $entity_type_manager);
  }

}
