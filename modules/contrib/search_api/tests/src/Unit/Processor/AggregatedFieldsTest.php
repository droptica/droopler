<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\DataType\DataTypeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\processor\AggregatedFields;
use Drupal\search_api\Plugin\search_api\processor\Property\AggregatedFieldProperty;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Utility\PluginHelperInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Unit\TestComplexDataInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Aggregated fields" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\AggregatedFields
 */
class AggregatedFieldsTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\search_api\processor\AggregatedFields
   */
  protected $processor;

  /**
   * A search index mock for the tests.
   *
   * @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $index;

  /**
   * The field ID used in this test.
   *
   * @var string
   */
  protected $fieldId = 'aggregated_field';

  /**
   * The callback with which text values should be preprocessed.
   *
   * @var callable
   */
  protected $valueCallback;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $datasource = $this->createMock(DatasourceInterface::class);
    $datasource->expects($this->any())
      ->method('getPropertyDefinitions')
      ->willReturn([]);
    $this->index = new Index([
      'datasourceInstances' => [
        'entity:test1' => $datasource,
        'entity:test2' => $datasource,
        'entity:test3' => $datasource,
      ],
      'processorInstances' => [],
      'field_settings' => [
        'foo' => [
          'type' => 'string',
          'datasource_id' => 'entity:test1',
          'property_path' => 'foo',
        ],
        'bar' => [
          'type' => 'string',
          'datasource_id' => 'entity:test1',
          'property_path' => 'foo:bar',
        ],
        'bla' => [
          'type' => 'string',
          'datasource_id' => 'entity:test2',
          'property_path' => 'foobaz:bla',
        ],
        'always_empty' => [
          'type' => 'string',
          'datasource_id' => 'entity:test3',
          'property_path' => 'always_empty',
        ],
        'aggregated_field' => [
          'type' => 'text',
          'property_path' => 'aggregated_field',
        ],
      ],
    ], 'search_api_index');
    $this->processor = new AggregatedFields(['#index' => $this->index], 'aggregated_field', []);
    $this->index->addProcessor($this->processor);
    $this->setUpMockContainer();

    $plugin_helper = $this->createMock(PluginHelperInterface::class);
    $this->container->set('search_api.plugin_helper', $plugin_helper);

    // We want to check correct data type handling, so we need a somewhat more
    // complex mock-up for the datatype plugin handler.
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\search_api\DataType\DataTypePluginManager $data_type_manager */
    $data_type_manager = $this->container->get('plugin.manager.search_api.data_type');
    $data_type_manager->method('hasDefinition')
      ->willReturn(TRUE);
    $this->valueCallback = function ($value) {
      if (is_numeric($value)) {
        return $value + 1;
      }
      else {
        return '*' . $value;
      }
    };
    $data_type = $this->createMock(DataTypeInterface::class);
    $data_type->method('getValue')
      ->willReturnCallback($this->valueCallback);
    $data_type_manager->method('createInstance')
      ->willReturnMap([
        ['text', [], $data_type],
      ]);
  }

  /**
   * Tests aggregated fields of the given type.
   *
   * @param string $type
   *   The aggregation type to test.
   * @param array $expected
   *   The expected values for the two items.
   * @param bool $integer
   *   (optional) TRUE if the items' normal fields should contain integers,
   *   FALSE otherwise.
   *
   * @dataProvider aggregationTestsDataProvider
   */
  public function testAggregation($type, array $expected, $integer = FALSE) {
    // Add the field configuration.
    $configuration = [
      'type' => $type,
      'fields' => [
        'entity:test1/foo',
        'entity:test1/foo:bar',
        'entity:test2/foobaz:bla',
        'entity:test3/always_empty',
      ],
    ];
    $this->index->getField($this->fieldId)->setConfiguration($configuration);

    if ($integer) {
      $field_values = [
        'foo' => [2, 4],
        'bar' => [16],
        'bla' => [7],
      ];
    }
    else {
      $field_values = [
        'foo' => ['foo', 'bar'],
        'bar' => ['baz'],
        'bla' => ['foobar'],
      ];
    }
    $items = [];
    $i = 0;
    foreach (['entity:test1', 'entity:test2', 'entity:test3'] as $datasource_id) {
      $this->itemIds[$i++] = $item_id = Utility::createCombinedId($datasource_id, '1:en');
      $item = \Drupal::getContainer()
        ->get('search_api.fields_helper')
        ->createItem($this->index, $item_id);
      foreach ([NULL, $datasource_id] as $field_datasource_id) {
        foreach ($this->index->getFieldsByDatasource($field_datasource_id) as $field_id => $field) {
          $field = clone $field;
          if (!empty($field_values[$field_id])) {
            $field->setValues($field_values[$field_id]);
          }
          $item->setField($field_id, $field);
        }
      }
      $item->setFieldsExtracted(TRUE);
      $items[$item_id] = $item;
    }

    // Add the processor's field values to the items.
    foreach ($items as $item) {
      $this->processor->addFieldValues($item);
    }

    $this->assertEquals(array_map($this->valueCallback, $expected[0]), $items[$this->itemIds[0]]->getField($this->fieldId)->getValues(), 'Correct aggregation for item 1.');
    $this->assertEquals(array_map($this->valueCallback, $expected[1]), $items[$this->itemIds[1]]->getField($this->fieldId)->getValues(), 'Correct aggregation for item 2.');
    $this->assertEquals(array_map($this->valueCallback, $expected[2]), $items[$this->itemIds[2]]->getField($this->fieldId)->getValues(), 'Correct aggregation for item 3.');
  }

  /**
   * Provides test data for aggregation tests.
   *
   * @return array
   *   An array containing test data sets, with each being an array of
   *   arguments to pass to the test method.
   *
   * @see static::testAggregation()
   */
  public function aggregationTestsDataProvider() {
    return [
      '"Union" aggregation' => [
        'union',
        [
          ['foo', 'bar', 'baz'],
          ['foobar'],
          [],
        ],
      ],
      '"Concatenation" aggregation' => [
        'concat',
        [
          ["foo\n\nbar\n\nbaz"],
          ['foobar'],
          [''],
        ],
      ],
      '"Sum" aggregation' => [
        'sum',
        [
          [22],
          [7],
          [0],
        ],
        TRUE,
      ],
      '"Count" aggregation' => [
        'count',
        [
          [3],
          [1],
          [0],
        ],
      ],
      '"Maximum" aggregation' => [
        'max',
        [
          [16],
          [7],
          [],
        ],
        TRUE,
      ],
      '"Minimum" aggregation' => [
        'min',
        [
          [2],
          [7],
          [],
        ],
        TRUE,
      ],
      '"First" aggregation' => [
        'first',
        [
          ['foo'],
          ['foobar'],
          [],
        ],
      ],
      '"Last" aggregation' => [
        'last',
        [
          ['baz'],
          ['foobar'],
          [],
        ],
      ],
    ];
  }

  /**
   * Tests whether the properties are correctly altered.
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AggregatedFields::getPropertyDefinitions()
   */
  public function testGetPropertyDefinitions() {
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $this->getStringTranslationStub();
    $this->processor->setStringTranslation($translation);

    // Check for added properties when no datasource is given.
    /** @var \Drupal\search_api\Processor\ProcessorPropertyInterface[] $properties */
    $properties = $this->processor->getPropertyDefinitions(NULL);

    $this->assertArrayHasKey('aggregated_field', $properties, 'The "aggregated_field" property was added to the properties.');
    $this->assertInstanceOf(AggregatedFieldProperty::class, $properties['aggregated_field'], 'The "aggregated_field" property has the correct class.');
    $this->assertEquals('string', $properties['aggregated_field']->getDataType(), 'Correct data type set in the data definition.');
    $this->assertEquals($translation->translate('Aggregated field'), $properties['aggregated_field']->getLabel(), 'Correct label set in the data definition.');
    $expected_description = $translation->translate('An aggregation of multiple other fields.');
    $this->assertEquals($expected_description, $properties['aggregated_field']->getDescription(), 'Correct description set in the data definition.');

    // Verify that there are no properties if a datasource is given.
    $datasource = $this->createMock(DatasourceInterface::class);
    $properties = $this->processor->getPropertyDefinitions($datasource);
    $this->assertEmpty($properties, 'Datasource-specific properties did not get changed.');
  }

  /**
   * Tests that field extraction in the processor works correctly.
   */
  public function testFieldExtraction() {
    /** @var \Drupal\Tests\search_api\Unit\TestComplexDataInterface|\PHPUnit_Framework_MockObject_MockObject $object */
    $object = $this->createMock(TestComplexDataInterface::class);
    $bar_foo_property = $this->createMock(TypedDataInterface::class);
    $bar_foo_property->method('getValue')
      ->willReturn('value3');
    $bar_foo_property->method('getDataDefinition')
      ->willReturn(new DataDefinition());
    $bar_property = $this->createMock(TestComplexDataInterface::class);
    $bar_property->method('get')
      ->willReturnMap([
        ['foo', $bar_foo_property],
      ]);
    $bar_property->method('getProperties')
      ->willReturn([
        'foo' => TRUE,
      ]);
    $foobar_property = $this->createMock(TypedDataInterface::class);
    $foobar_property->method('getValue')
      ->willReturn('wrong_value2');
    $foobar_property->method('getDataDefinition')
      ->willReturn(new DataDefinition());
    $object->method('get')
      ->willReturnMap([
        ['bar', $bar_property],
        ['foobar', $foobar_property],
      ]);
    $object->method('getProperties')
      ->willReturn([
        'bar' => TRUE,
        'foobar' => TRUE,
      ]);

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->createMock(IndexInterface::class);

    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $field = $fields_helper->createField($index, 'aggregated_field', [
      'property_path' => 'aggregated_field',
      'configuration' => [
        'type' => 'union',
        'fields' => [
          'aggregated_field',
          'foo',
          'entity:test1/bar:foo',
          'entity:test1/baz',
          'entity:test2/foobar',
        ],
      ],
    ]);
    $index->method('getFields')->willReturn([
      'aggregated_field' => $field,
    ]);
    $index->method('getPropertyDefinitions')
      ->willReturnMap([
        [
          NULL,
          [
            'foo' => new ProcessorProperty([
              'processor_id' => 'processor1',
            ]),
          ],
        ],
        [
          'entity:test1',
          [
            'bar' => new DataDefinition(),
            'foobar' => new DataDefinition(),
          ],
        ],
      ]);
    $processor_mock = $this->createMock(ProcessorInterface::class);
    $processor_mock->method('addFieldValues')
      ->willReturnCallback(function (ItemInterface $item) {
        foreach ($item->getFields(FALSE) as $field) {
          if ($field->getCombinedPropertyPath() == 'foo') {
            $field->setValues(['value4', 'value5']);
          }
        }
      });
    $index->method('getProcessorsByStage')
      ->willReturnMap([
        [
          ProcessorInterface::STAGE_ADD_PROPERTIES,
          [],
          [
            'aggregated_field' => $this->processor,
            'processor1' => $processor_mock,
          ],
        ],
      ]);
    $this->processor->setIndex($index);

    /** @var \Drupal\search_api\Datasource\DatasourceInterface|\PHPUnit_Framework_MockObject_MockObject $datasource */
    $datasource = $this->createMock(DatasourceInterface::class);
    $datasource->method('getPluginId')
      ->willReturn('entity:test1');

    $item = $fields_helper->createItem($index, 'id', $datasource);
    $item->setOriginalObject($object);
    $item->setField('aggregated_field', clone $field);
    $item->setField('test1', $fields_helper->createField($index, 'test1', [
      'property_path' => 'baz',
      'values' => [
        'wrong_value1',
      ],
    ]));
    $item->setField('test2', $fields_helper->createField($index, 'test2', [
      'datasource_id' => 'entity:test1',
      'property_path' => 'baz',
      'values' => [
        'value1',
        'value2',
      ],
    ]));
    $item->setFieldsExtracted(TRUE);

    $this->processor->addFieldValues($item);

    $expected = [
      'value1',
      'value2',
      'value3',
      'value4',
      'value5',
    ];
    $actual = $item->getField('aggregated_field')->getValues();
    sort($actual);
    $this->assertEquals($expected, $actual);
  }

}
