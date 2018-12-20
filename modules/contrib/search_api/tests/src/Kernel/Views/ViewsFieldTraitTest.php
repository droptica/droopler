<?php

namespace Drupal\Tests\search_api\Kernel\Views;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Kernel\ViewsTestField;
use Drupal\user\Entity\User;
use Drupal\views\ResultRow;

/**
 * Tests the functionality of our Views field plugin trait.
 *
 * @group search_api
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\views\field\SearchApiFieldTrait
 */
class ViewsFieldTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'search_api',
    'search_api_test_example_content',
    'user',
    'system',
    'entity_test',
    'text',
  ];

  /**
   * The test index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The field used for testing.
   *
   * @var \Drupal\Tests\search_api\Kernel\ViewsTestField
   */
  protected $field;

  /**
   * Users created for this test.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users;

  /**
   * Test entities created for this test.
   *
   * @var \Drupal\entity_test\Entity\EntityTestMulRevChanged[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('user');

    $this->installConfig([
      'search_api_test_example_content',
      'user',
    ]);

    for ($i = 1; $i <= 3; ++$i) {
      $this->users[$i] = User::create([
        'uid' => $i,
        'name' => "User $i",
      ]);
      $this->users[$i]->save();

      $this->entities[$i] = EntityTestMulRevChanged::create([
        'id' => $i,
        'user_id' => $i,
        'name' => "Test entity $i name",
        'body' => "Test entity $i body",
      ]);
      $this->entities[$i]->save();
    }

    // Create the test index, but don't save it. We don't need it saved for this
    // test and saving it would need more time and additional code (e.g., would
    // track all the entities we create).
    $datasource_id = 'entity:entity_test_mulrev_changed';
    $this->index = Index::create([
      'field_settings' => [
        'aggregated_field' => [
          'label' => 'Aggregated field',
          'property_path' => 'aggregated_field',
          'type' => 'text',
          'configuration' => [
            'type' => 'union',
            'fields' => [
              Utility::createCombinedId($datasource_id, 'name'),
              Utility::createCombinedId($datasource_id, 'body'),
              Utility::createCombinedId('entity:user', 'name'),
            ],
          ],
        ],
      ],
      'datasource_settings' => [
        $datasource_id => [],
        'entity:user' => [],
      ],
      'processor_settings' => [
        'aggregated_field' => [],
      ],
    ]);

    $this->field = new ViewsTestField([], 'search_api', []);
    /** @var \Drupal\search_api\Plugin\views\query\SearchApiQuery|\PHPUnit_Framework_MockObject_MockObject $query */
    $query = $this->getMockBuilder(SearchApiQuery::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query->method('getIndex')->willReturn($this->index);
    $query->method('getAccessAccount')->willReturn($this->users[1]);
    $this->field->setQuery($query);
  }

  /**
   * Tests that entity loading in the pre-render phase works correctly.
   *
   * @covers ::preRender
   */
  public function testPreRender() {
    $datasource_id = 'entity:entity_test_mulrev_changed';
    $entity_name = Utility::createCombinedId($datasource_id, 'name');
    $entity_body = Utility::createCombinedId($datasource_id, 'body');
    $entity_user_name = Utility::createCombinedId($datasource_id, 'user_id:entity:name');
    $user_name = Utility::createCombinedId('entity:user', 'name');

    /** @var \Drupal\views\ResultRow[] $values */
    $values = [];

    $item_id = Utility::createCombinedId($datasource_id, '1:en');
    $item = new Item($this->index, $item_id);
    $item->setOriginalObject($this->entities[1]->getTypedData());
    $values[] = new ResultRow([
      '_item' => $item,
      '_object' => $item->getOriginalObject(),
      '_relationship_objects' => [
        NULL => [$item->getOriginalObject()],
      ],
    ]);
    $item_id = Utility::createCombinedId($datasource_id, '2:en');
    $values[] = new ResultRow([
      '_item' => new Item($this->index, $item_id),
      $entity_name => [
        'Fake value',
      ],
      $entity_user_name => [
        'Other fake value',
      ],
    ]);
    $item_id = Utility::createCombinedId('entity:user', '3:en');
    $values[] = new ResultRow([
      '_item' => new Item($this->index, $item_id),
    ]);
    $item_id = Utility::createCombinedId($datasource_id, '3:en');
    $item = new Item($this->index, $item_id);
    $values[] = new ResultRow([
      '_item' => $item,
    ]);

    // Set some other required properties which can easily be set automatically.
    foreach ($values as $row) {
      $row->search_api_id = $row->_item->getId();
      $row->search_api_datasource = $row->_item->getDatasourceId();
      $row->search_api_language = 'en';
    }

    $this->field->addRetrievedProperty($entity_name);
    $this->field->addRetrievedProperty("$entity_body:_object");
    $this->field->addRetrievedProperty($entity_user_name);
    $this->field->addRetrievedProperty($user_name);
    $this->field->addRetrievedProperty('aggregated_field');

    $this->field->preRender($values);

    $this->assertObjectHasAttribute($entity_name, $values[0]);
    $this->assertEquals(['Test entity 1 name'], $values[0]->$entity_name);
    $this->assertObjectHasAttribute("$entity_body:_object", $values[0]);
    $this->assertCount(1, $values[0]->{"$entity_body:_object"});
    $this->assertInstanceOf(TypedDataInterface::class, $values[0]->{"$entity_body:_object"}[0]);
    $this->assertArrayHasKey($entity_body, $values[0]->_relationship_objects);
    $this->assertNotEmpty($values[0]->_relationship_objects[$entity_body]);
    $this->assertObjectHasAttribute($entity_user_name, $values[0]);
    $this->assertEquals(['User 1'], $values[0]->$entity_user_name);
    $this->assertTrue(empty($values[0]->$user_name), 'User name should not be extracted for non-user entity.');
    $this->assertObjectHasAttribute('aggregated_field', $values[0]);
    $this->assertContains('Test entity 1 name', $values[0]->aggregated_field);
    $this->assertContains('Test entity 1 body', $values[0]->aggregated_field);

    $this->assertObjectHasAttribute($entity_name, $values[1]);
    $this->assertEquals(['Fake value'], $values[1]->$entity_name);
    $this->assertArrayHasKey($entity_body, $values[1]->_relationship_objects);
    $this->assertNotEmpty($values[1]->_relationship_objects[$entity_body]);
    $this->assertObjectHasAttribute($entity_user_name, $values[1]);
    $this->assertEquals(['Other fake value'], $values[1]->$entity_user_name);
    $this->assertTrue(empty($values[1]->$user_name), 'User name should not be extracted for non-user entity.');
    $this->assertObjectHasAttribute('aggregated_field', $values[1]);
    $this->assertContains('Test entity 2 name', $values[1]->aggregated_field);
    $this->assertContains('Test entity 2 body', $values[1]->aggregated_field);

    // Since we provided the values on the row, most relationship objects should
    // not have been loaded.
    $this->assertArrayNotHasKey($entity_name, $values[1]->_relationship_objects);
    $this->assertArrayNotHasKey(Utility::createCombinedId($datasource_id, 'user_id'), $values[1]->_relationship_objects);
    $this->assertArrayNotHasKey(Utility::createCombinedId($datasource_id, 'user_id:entity'), $values[1]->_relationship_objects);
    $this->assertArrayNotHasKey($entity_user_name, $values[1]->_relationship_objects);

    $this->assertObjectHasAttribute($user_name, $values[2]);
    $this->assertEquals(['User 3'], $values[2]->$user_name);
    $this->assertTrue(empty($values[2]->$entity_name), 'Test entity name should not be extracted for user entity.');
    $this->assertTrue(empty($values[2]->$entity_user_name), 'Test entity author name should not be extracted for user entity.');
    $this->assertObjectHasAttribute('aggregated_field', $values[2]);
    $this->assertEquals(['User 3'], $values[2]->aggregated_field);

    $this->assertObjectHasAttribute($entity_name, $values[3]);
    $this->assertEquals(['Test entity 3 name'], $values[3]->$entity_name);
    $this->assertObjectHasAttribute("$entity_body:_object", $values[3]);
    $this->assertCount(1, $values[3]->{"$entity_body:_object"});
    $this->assertInstanceOf(TypedDataInterface::class, $values[3]->{"$entity_body:_object"}[0]);
    $this->assertArrayHasKey($entity_body, $values[3]->_relationship_objects);
    $this->assertNotEmpty($values[3]->_relationship_objects[$entity_body]);
    $this->assertObjectHasAttribute($entity_user_name, $values[3]);
    $this->assertEquals(['User 3'], $values[3]->$entity_user_name);
    $this->assertTrue(empty($values[3]->$user_name), 'User name should not be extracted for non-user entity.');
    $this->assertObjectHasAttribute('aggregated_field', $values[3]);
    $this->assertContains('Test entity 3 name', $values[3]->aggregated_field);
    $this->assertContains('Test entity 3 body', $values[3]->aggregated_field);
  }

}
