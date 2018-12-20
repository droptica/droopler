<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\processor\EntityStatus;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;

/**
 * Tests the "Entity status" processor.
 *
 * @group search_api
 *
 * @var \Drupal\search_api\Plugin\search_api\processor\EntityStatus
 */
class EntityStatusTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\search_api\processor\EntityStatus
   */
  protected $processor;

  /**
   * The test index.
   *
   * @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $index;

  /**
   * The test index's potential datasources.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface[]
   */
  protected $datasources = [];

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpMockContainer();

    $this->processor = new EntityStatus([], 'entity_status', []);

    $this->index = $this->createMock(IndexInterface::class);

    foreach (['node', 'comment', 'user', 'file'] as $entity_type) {
      $datasource = $this->createMock(DatasourceInterface::class);
      $datasource->expects($this->any())
        ->method('getEntityTypeId')
        ->will($this->returnValue($entity_type));
      $this->datasources["entity:$entity_type"] = $datasource;
    }
  }

  /**
   * Tests whether supportsIndex() returns TRUE for an index containing nodes.
   *
   * @param string[]|null $datasource_ids
   *   The IDs of datasources the index should have, or NULL if it should have
   *   all of them.
   * @param bool $expected
   *   Whether the processor is supposed to support that index.
   *
   * @dataProvider supportsIndexDataProvider
   */
  public function testSupportsIndex(array $datasource_ids = NULL, $expected) {
    if ($datasource_ids !== NULL) {
      $datasource_ids = array_flip($datasource_ids);
      $this->datasources = array_intersect_key($this->datasources, $datasource_ids);
    }
    $this->index->method('getDatasources')
      ->will($this->returnValue($this->datasources));

    // In supportsIndex(), the entity status processor will use the entity type
    // manager to get the definition of each datasource's entity type and then
    // check whether it implements \Drupal\Core\Entity\EntityPublishedInterface.
    // We therefore need to ensure each of these calls returns an appropriate
    // value.
    $self = $this;
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->willReturnCallback(function ($entity_type_id) use ($self) {
        $entity_type = $self->getMock(EntityTypeInterface::class);
        $publishable = in_array($entity_type_id, ['node', 'comment']);
        $entity_type->method('entityClassImplements')
          ->willReturnMap([
            [EntityPublishedInterface::class, $publishable],
          ]);
        return $entity_type;
      });
    $this->container->set('entity_type.manager', $entity_type_manager);

    $this->assertEquals($expected, EntityStatus::supportsIndex($this->index));
  }

  /**
   * Provides data for the testSupportsIndex() tests.
   *
   * @return array[]
   *   Array of parameter arrays for testSupportsIndex().
   */
  public function supportsIndexDataProvider() {
    return [
      'all datasources' => [NULL, TRUE],
      'node datasource' => [['entity:node'], TRUE],
      'comment datasource' => [['entity:comment'], TRUE],
      'user datasource' => [['entity:user'], TRUE],
      'file datasource' => [['entity:file'], FALSE],
    ];
  }

  /**
   * Tests if unpublished/inactive entities are removed from the indexed items.
   */
  public function testAlterItems() {
    $entity_types = [
      'node' => [
        'class' => Node::class,
        'method' => 'isPublished',
      ],
      'comment' => [
        'class' => Comment::class,
        'method' => 'isPublished',
      ],
      'user' => [
        'class' => User::class,
        'method' => 'isActive',
      ],
      'file' => [
        'class' => File::class,
      ],
    ];
    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $items = [];
    foreach ($entity_types as $entity_type => $info) {
      $datasource_id = "entity:$entity_type";
      foreach ([1 => TRUE, 2 => FALSE] as $i => $status) {
        $item_id = Utility::createCombinedId($datasource_id, "$i:en");
        $item = $fields_helper->createItem($this->index, $item_id, $this->datasources[$datasource_id]);
        $entity = $this->getMockBuilder($info['class'])
          ->disableOriginalConstructor()
          ->getMock();
        if (isset($info['method'])) {
          $entity->method($info['method'])
            ->will($this->returnValue($status));
        }
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $item->setOriginalObject(EntityAdapter::createFromEntity($entity));
        $items[$item_id] = $item;
      }
    }

    $this->processor->alterIndexedItems($items);
    $expected = [
      Utility::createCombinedId('entity:node', '1:en'),
      Utility::createCombinedId('entity:comment', '1:en'),
      Utility::createCombinedId('entity:user', '1:en'),
      Utility::createCombinedId('entity:file', '1:en'),
      Utility::createCombinedId('entity:file', '2:en'),
    ];
    $this->assertEquals($expected, array_keys($items));
  }

}
