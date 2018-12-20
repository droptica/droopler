<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\processor\RoleFilter;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Unit\TestNodeInterface;
use Drupal\Tests\search_api\Unit\TestUserInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Role filter" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\RoleFilter
 */
class RoleFilterTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\search_api\processor\RoleFilter
   */
  protected $processor;

  /**
   * The test items to use.
   *
   * @var \Drupal\search_api\Item\ItemInterface[]
   */
  protected $items = [];

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpMockContainer();

    $this->processor = new RoleFilter([], 'role_filter', []);

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->createMock(IndexInterface::class);

    $node_datasource = $this->createMock(DatasourceInterface::class);
    $node_datasource->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $node_datasource */
    $user_datasource = $this->createMock(DatasourceInterface::class);
    $user_datasource->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('user'));
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $user_datasource */

    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $item = $fields_helper->createItem($index, Utility::createCombinedId('entity:node', '1:en'), $node_datasource);
    $node = $this->getMockBuilder(TestNodeInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    /** @var \Drupal\node\NodeInterface $node */
    $item->setOriginalObject(EntityAdapter::createFromEntity($node));
    $this->items[$item->getId()] = $item;

    $item = $fields_helper->createItem($index, Utility::createCombinedId('entity:user', '1:en'), $user_datasource);
    $account1 = $this->getMockBuilder(TestUserInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $account1->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(['authenticated' => 'authenticated', 'editor' => 'editor']));
    /** @var \Drupal\user\UserInterface $account1 */
    $item->setOriginalObject(EntityAdapter::createFromEntity($account1));
    $this->items[$item->getId()] = $item;

    $item = $fields_helper->createItem($index, Utility::createCombinedId('entity:user', '2:en'), $user_datasource);
    $account2 = $this->getMockBuilder(TestUserInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $account2->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(['authenticated' => 'authenticated']));
    /** @var \Drupal\user\UserInterface $account2 */
    $item->setOriginalObject(EntityAdapter::createFromEntity($account2));
    $this->items[$item->getId()] = $item;
  }

  /**
   * Tests preprocessing search items with an inclusive filter.
   */
  public function testFilterInclusive() {
    $configuration['roles'] = ['authenticated'];
    $configuration['default'] = 0;
    $this->processor->setConfiguration($configuration);

    $this->processor->alterIndexedItems($this->items);

    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:user', '1:en')]), 'User with two roles was not removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:user', '2:en')]), 'User with only the authenticated role was not removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:node', '1:en')]), 'Node item was not removed.');
  }

  /**
   * Tests preprocessing search items with an exclusive filter.
   */
  public function testFilterExclusive() {
    $configuration['roles'] = ['editor'];
    $configuration['default'] = 1;
    $this->processor->setConfiguration($configuration);

    $this->processor->alterIndexedItems($this->items);

    $this->assertTrue(empty($this->items[Utility::createCombinedId('entity:user', '1:en')]), 'User with editor role was successfully removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:user', '2:en')]), 'User without the editor role was not removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:node', '1:en')]), 'Node item was not removed.');
  }

}
