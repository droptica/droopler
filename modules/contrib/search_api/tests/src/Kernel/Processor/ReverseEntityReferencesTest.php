<?php

namespace Drupal\Tests\search_api\Kernel\Processor;

use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Processor\ProcessorPropertyInterface;
use Drupal\user\Entity\User;

/**
 * Tests the "Reverse entity references" processor.
 *
 * @group search_api
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\search_api\processor\ReverseEntityReferences
 */
class ReverseEntityReferencesTest extends ProcessorTestBase {

  /**
   * The nodes created for testing.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes;

  /**
   * The author UIDs of the created nodes, keyed by index in $this->nodes.
   *
   * @var array
   */
  protected $nodeUids = [
    1,
    0,
    1,
    2,
    0,
    1,
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp('reverse_entity_references');

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();

    // Insert the anonymous user into the database, as well as some other users.
    User::create([
      'uid' => 0,
      'name' => '',
    ])->save();
    User::create([
      'uid' => 1,
      'name' => 'admin',
    ])->save();
    User::create([
      'uid' => 2,
      'name' => 'user',
    ])->save();
    User::create([
      'uid' => 3,
      'name' => 'other user',
    ])->save();

    // Create nodes.
    foreach ($this->nodeUids as $i => $uid) {
      $values = [
        'type' => 'page',
        'title' => 'test title',
        'uid' => $uid,
      ];
      $this->nodes[$i] = Node::create($values);
      $this->nodes[$i]->save();
    }

    // Switch the index to index users and add a reverse reference to the nodes
    // authored by the indexed user.
    $datasources = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createDatasourcePlugins($this->index, [
        'entity:user',
      ]);
    $this->index->setDatasources($datasources);
    $field = new Field($this->index, 'nid');
    $field->setType('integer');
    $field->setPropertyPath('search_api_reverse_entity_references_node__uid:nid');
    $field->setDatasourceId('entity:user');
    $field->setLabel('Authored nodes');
    $this->index->addField($field);
    $this->index->save();

    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll($this->index);
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    $index_storage->resetCache([$this->index->id()]);
    $this->index = $index_storage->load($this->index->id());
  }

  /**
   * Tests that property definitions are created correctly.
   *
   * @covers ::getPropertyDefinitions
   */
  public function testGetPropertyDefinitions() {
    $properties = $this->processor->getPropertyDefinitions(NULL);
    $this->assertEmpty($properties);

    $datasource = $this->createMock(DatasourceInterface::class);
    $datasource->method('getEntityTypeId')
      ->willReturn(NULL);
    $properties = $this->processor->getPropertyDefinitions($datasource);
    $this->assertEmpty($properties);

    $datasource = $this->index->getDatasource('entity:user');
    $properties = $this->processor->getPropertyDefinitions($datasource);
    $this->assertArrayHasKey('search_api_reverse_entity_references_node__uid', $properties);

    /** @var \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface|\Drupal\search_api\Processor\ProcessorPropertyInterface $property */
    $property = $properties['search_api_reverse_entity_references_node__uid'];
    $this->assertInstanceOf(EntityDataDefinitionInterface::class, $property);
    $this->assertInstanceOf(ProcessorPropertyInterface::class, $property);
    $this->assertEquals('node', $property->getEntityTypeId());
    $this->assertEquals('reverse_entity_references', $property->getProcessorId());
  }

  /**
   * Tests that field value extraction works correctly.
   *
   * @covers ::addFieldValues
   */
  public function testAddFieldValues() {
    $nids_by_user = [];
    foreach ($this->nodeUids as $i => $uid) {
      $nids_by_user[$uid][] = $this->nodes[$i]->id();
    }

    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    foreach ([0, 1, 2, 3] as $uid) {
      $item = $fields_helper->createItem($this->index, "entity:user/$uid:en");

      // This will automatically trigger field extraction.
      $nids = $item->getField('nid')->getValues();

      sort($nids);
      $nids_by_user += [$uid => []];
      $this->assertEquals($nids_by_user[$uid], $nids, "Unexpected field values extracted for user #$uid.");
    }
  }

}
