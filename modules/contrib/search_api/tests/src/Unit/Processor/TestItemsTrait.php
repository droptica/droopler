<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Utility\DataTypeHelper;
use Drupal\search_api\Utility\FieldsHelper;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Utility\QueryHelperInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Provides common methods for test cases that need to create search items.
 */
trait TestItemsTrait {

  /**
   * The used item IDs for test items.
   *
   * @var string[]
   */
  protected $itemIds = [];

  /**
   * The class container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Creates an array with a single item which has the given field.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that should be used for the item.
   * @param string $fieldType
   *   The field type to set for the field.
   * @param mixed $fieldValue
   *   A field value to add to the field.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   (optional) A variable, passed by reference, into which the created field
   *   will be saved.
   * @param string $fieldId
   *   (optional) The field ID to set for the field.
   *
   * @return \Drupal\search_api\Item\ItemInterface[]
   *   An array containing a single item with the specified field.
   */
  public function createSingleFieldItem(IndexInterface $index, $fieldType, $fieldValue, FieldInterface &$field = NULL, $fieldId = 'field_test') {
    $this->itemIds[0] = $itemId = Utility::createCombinedId('entity:node', '1:en');
    $item = new Item($index, $itemId);
    $field = new Field($index, $fieldId);
    $field->setType($fieldType);
    $field->addValue($fieldValue);
    $item->setField($fieldId, $field);
    $item->setFieldsExtracted(TRUE);

    return [$itemId => $item];
  }

  /**
   * Creates a certain number of test items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that should be used for the items.
   * @param int $count
   *   The number of items to create.
   * @param array[] $fields
   *   The fields to create on the items, with keys being combined property
   *   paths and values being arrays with properties to set on the field.
   * @param \Drupal\Core\TypedData\ComplexDataInterface|null $object
   *   (optional) The object to set on each item as the "original object".
   * @param array|null $datasource_ids
   *   (optional) An array of datasource IDs to use for the items, in that order
   *   (starting again from the front if necessary).
   *
   * @return \Drupal\search_api\Item\ItemInterface[]
   *   An array containing the requested test items.
   */
  public function createItems(IndexInterface $index, $count, array $fields, ComplexDataInterface $object = NULL, array $datasource_ids = ['entity:node']) {
    $datasource_count = count($datasource_ids);
    $items = [];
    for ($i = 0; $i < $count; ++$i) {
      $datasource_id = $datasource_ids[$i % $datasource_count];
      $this->itemIds[$i] = $item_id = Utility::createCombinedId($datasource_id, ($i + 1) . ':en');
      $item = new Item($index, $item_id);
      if (isset($object)) {
        $item->setOriginalObject($object);
      }
      foreach ($fields as $combined_property_path => $field_info) {
        list($field_info['datasource_id'], $field_info['property_path']) = Utility::splitCombinedId($combined_property_path);
        // Only add fields of the right datasource.
        if (isset($field_info['datasource_id']) && $field_info['datasource_id'] != $datasource_id) {
          continue;
        }
        $fields_helper = \Drupal::getContainer()
          ->get('search_api.fields_helper');
        $field_id = $fields_helper->getNewFieldId($index, $field_info['property_path']);
        $field = $fields_helper->createField($index, $field_id, $field_info);
        $item->setField($field_id, $field);
      }
      $item->setFieldsExtracted(TRUE);
      $items[$item_id] = $item;
    }
    return $items;
  }

  /**
   * Adds a container with several mock services commonly needed by our tests.
   */
  protected function setUpMockContainer() {
    /** @var \Drupal\Tests\UnitTestCase|\Drupal\Tests\search_api\Unit\Processor\TestItemsTrait $this */
    $dataTypeManager = $this->getMockBuilder('Drupal\search_api\DataType\DataTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $dataTypeManager->method('getInstances')
      ->will($this->returnValue([]));

    $moduleHandler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $dataTypeHelper = new DataTypeHelper($moduleHandler, $dataTypeManager);

    $entityTypeManager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityFieldManager = $this->getMockBuilder('Drupal\Core\Entity\EntityFieldManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityBundleInfo = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeBundleInfoInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $fieldsHelper = new FieldsHelper($entityTypeManager, $entityFieldManager, $entityBundleInfo, $dataTypeHelper);

    $queryHelper = $this->createMock(QueryHelperInterface::class);
    $queryHelper->method('createQuery')
      ->willReturnCallback(function (IndexInterface $index, array $options = []) {
        return Query::create($index, $options);
      });
    $queryHelper->method('getResults')
      ->will($this->returnValue([]));

    $this->container = new ContainerBuilder();
    $this->container->set('plugin.manager.search_api.data_type', $dataTypeManager);
    $this->container->set('search_api.data_type_helper', $dataTypeHelper);
    $this->container->set('search_api.fields_helper', $fieldsHelper);
    $this->container->set('search_api.query_helper', $queryHelper);
    \Drupal::setContainer($this->container);
  }

}
