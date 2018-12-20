<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Plugin\search_api\data_type\value\TextToken;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Query\Condition;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the base class for fields-based processors.
 *
 * @coversDefaultClass \Drupal\search_api\Processor\FieldsProcessorPluginBase
 *
 * @group search_api
 */
class FieldsProcessorPluginBaseTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * A search index mock to use in this test case.
   *
   * @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $index;

  /**
   * The class under test.
   *
   * @var \Drupal\Tests\search_api\Unit\Processor\TestFieldsProcessorPlugin
   */
  protected $processor;

  /**
   * Creates a new processor object for use in the tests.
   */
  public function setUp() {
    parent::setUp();

    $this->setUpMockContainer();
    $this->index = $this->createMock(IndexInterface::class);
    $this->index->expects($this->any())
      ->method('status')
      ->will($this->returnValue(TRUE));
    $items = $this->getTestItem();
    $fields = $items[$this->itemIds[0]]->getFields();
    $this->index->expects($this->any())
      ->method('getFields')
      ->will($this->returnValue($fields));

    $this->processor = new TestFieldsProcessorPlugin(['#index' => $this->index], '', []);
  }

  /**
   * Tests whether the processor handles field changes correctly.
   */
  public function testFieldRenaming() {
    $configuration['fields'] = [
      'float_field',
      'float_field_2',
      'string_field',
      'text_field',
      'text_field_2',
    ];
    $this->processor->setConfiguration($configuration);

    $override = function ($type) {
      return in_array($type, ['string', 'text']);
    };
    $this->processor->setMethodOverride('testType', $override);

    $this->index->method('getFieldRenames')
      ->willReturn([
        'float_field' => 'foo',
        'text_field' => 'bar',
      ]);
    $this->index->method('getField')
      ->willReturnMap([
        ['float_field', (new Field($this->index, ''))->setType('float')],
        ['float_field_2', NULL],
        ['string_field', (new Field($this->index, ''))->setType('string')],
        ['bar', (new Field($this->index, ''))->setType('text')],
        ['text_field_2', (new Field($this->index, ''))->setType('text')],
      ]);

    $this->processor->preIndexSave();

    $fields = $this->processor->getConfiguration()['fields'];
    sort($fields);
    $expected = [
      'bar',
      'string_field',
      'text_field_2',
    ];
    $this->assertEquals($expected, $fields);
  }

  /**
   * Tests whether the "Enable on all supported fields" option works correctly.
   *
   * We want the option to make sure that all supported fields are automatically
   * added to the processor, without adding unsupported ones or keeping old ones
   * that were removed.
   */
  public function testAllFields() {
    $configuration['all_fields'] = TRUE;
    $configuration['fields'] = [
      'float_field',
      'string_field',
    ];
    $this->processor->setConfiguration($configuration);

    $override = function ($type) {
      return in_array($type, ['string', 'text']);
    };
    $this->processor->setMethodOverride('testType', $override);

    // Since it's not possible to override an already specified method on a mock
    // object, we need to create a new mock object for the index in this test.
    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->createMock(IndexInterface::class);
    $index->method('getFields')->willReturn([
      'float_field' => (new Field($this->index, ''))->setType('float'),
      'float_field_2' => (new Field($this->index, ''))->setType('float'),
      'string_field' => (new Field($this->index, ''))->setType('string'),
      'text_field' => (new Field($this->index, ''))->setType('text'),
      'text_field_2' => (new Field($this->index, ''))->setType('text'),
    ]);
    $this->processor->setIndex($index);

    $this->processor->preIndexSave();

    $fields = $this->processor->getConfiguration()['fields'];
    sort($fields);
    $expected = [
      'string_field',
      'text_field',
      'text_field_2',
    ];
    $this->assertEquals($expected, $fields);
  }

  /**
   * Tests whether the default implementation of testType() works correctly.
   */
  public function testTestTypeDefault() {
    $items = $this->getTestItem();
    $this->processor->preprocessIndexItems($items);
    $this->assertFieldsProcessed($items, ['text_field', 'string_field']);
  }

  /**
   * Tests whether overriding of testType() works correctly.
   */
  public function testTestTypeOverride() {
    $override = function ($type) {
      return \Drupal::getContainer()
        ->get('search_api.data_type_helper')
        ->isTextType($type, ['string', 'integer']);
    };
    $this->processor->setMethodOverride('testType', $override);

    $items = $this->getTestItem();
    $this->processor->preprocessIndexItems($items);
    $this->assertFieldsProcessed($items, ['string_field', 'integer_field']);
  }

  /**
   * Tests whether selecting fields works correctly.
   */
  public function testTestField() {
    // testType() shouldn't have any effect anymore when fields are configured.
    $override = function () {
      return FALSE;
    };
    $this->processor->setMethodOverride('testType', $override);
    $configuration['fields'] = ['text_field', 'float_field'];
    $this->processor->setConfiguration($configuration);

    $items = $this->getTestItem();
    $this->processor->preprocessIndexItems($items);
    $this->assertFieldsProcessed($items, ['text_field', 'float_field']);
  }

  /**
   * Tests whether overriding of processFieldValue() works correctly.
   */
  public function testProcessFieldValueOverride() {
    $override = function (&$value, &$type) {
      // Check whether the passed $type matches the one included in the value.
      if (strpos($value, "{$type}_field") !== FALSE) {
        $value = "&$value";
      }
      else {
        $value = "/$value";
      }
    };
    $this->processor->setMethodOverride('processFieldValue', $override);

    $items = $this->getTestItem();
    $this->processor->preprocessIndexItems($items);
    $this->assertFieldsProcessed($items, ['text_field', 'string_field'], '&');
  }

  /**
   * Tests whether removing values in processFieldValue() works correctly.
   */
  public function testProcessFieldRemoveValue() {
    $override = function (&$value) {
      if ($value != 'bar') {
        $value = "*$value";
      }
      else {
        $value = '';
      }
    };
    $this->processor->setMethodOverride('processFieldValue', $override);

    $fields = [
      'field1' => [
        'type' => 'string',
        'values' => [
          'foo',
          'bar',
        ],
      ],
    ];
    $items = $this->createItems($this->index, 1, $fields);

    $this->processor->preprocessIndexItems($items);

    $item_fields = $items[$this->itemIds[0]]->getFields();
    $this->assertEquals(['*foo'], $item_fields['field1']->getValues(), 'Field value was correctly removed.');
  }

  /**
   * Tests whether the processField() method operates correctly.
   */
  public function testProcessFieldsTokenized() {
    $override = function (&$value, $type) {
      switch ($type) {
        case 'integer':
          ++$value;
          return;

        case 'string':
          $value = "++$value";
          return;
      }

      if (strpos($value, ' ')) {
        $value = TestFieldsProcessorPlugin::createTokenizedText($value, 4)->getTokens();
      }
      elseif ($value == 'bar') {
        $value = TestFieldsProcessorPlugin::createTokenizedText('*bar', 2)->getTokens();
      }
      elseif ($value == 'baz') {
        $value = '';
      }
      else {
        $value = "*$value";
      }
    };
    $this->processor->setMethodOverride('processFieldValue', $override);

    $value = TestFieldsProcessorPlugin::createTokenizedText('foobar baz', 3);
    $tokens = $value->getTokens();
    $tokens[] = new TextToken('foo bar', 2);
    $value->setTokens($tokens);
    $fields = [
      'field1' => [
        'type' => 'text',
        'values' => [
          TestFieldsProcessorPlugin::createTokenizedText('foo bar baz', 3),
          $value,
          new TextValue('foo'),
          new TextValue('foo bar'),
          new TextValue('bar'),
          new TextValue('baz'),
        ],
      ],
      'field2' => [
        'type' => 'integer',
        'values' => [
          1,
          3,
        ],
      ],
      'field3' => [
        'type' => 'string',
        'values' => [
          'foo',
          'foo bar baz',
        ],
      ],
    ];
    $items = $this->createItems($this->index, 1, $fields);

    $this->processor->setConfiguration([
      'fields' => ['field1', 'field2', 'field3'],
    ]);

    $this->processor->preprocessIndexItems($items);

    $fields = $items[$this->itemIds[0]]->getFields();

    /** @var \Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface[] $values */
    $values = $fields['field1']->getValues();
    $summary = [];
    foreach ($values as $i => $value) {
      $summary[$i]['text'] = $value->toText();
      $tokens = $value->getTokens();
      if ($tokens !== NULL) {
        $summary[$i]['tokens'] = [];
        foreach ($tokens as $token) {
          $summary[$i]['tokens'][] = [
            'text' => $token->getText(),
            'boost' => $token->getBoost(),
          ];
        }
      }
    }
    $expected = [
      [
        'text' => '*foo *bar',
        'tokens' => [
          [
            'text' => '*foo',
            'boost' => 3,
          ],
          [
            'text' => '*bar',
            'boost' => 6,
          ],
        ],
      ],
      [
        'text' => '*foobar foo bar',
        'tokens' => [
          [
            'text' => '*foobar',
            'boost' => 3,
          ],
          [
            'text' => 'foo',
            'boost' => 8,
          ],
          [
            'text' => 'bar',
            'boost' => 8,
          ],
        ],
      ],
      [
        'text' => '*foo',
      ],
      [
        'text' => 'foo bar',
        'tokens' => [
          [
            'text' => 'foo',
            'boost' => 4,
          ],
          [
            'text' => 'bar',
            'boost' => 4,
          ],
        ],
      ],
      [
        'text' => '*bar',
        'tokens' => [
          [
            'text' => '*bar',
            'boost' => 2,
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $summary);

    $expected = [2, 4];
    $this->assertEquals('integer', $fields['field2']->getType());
    $this->assertEquals($expected, $fields['field2']->getValues());

    $expected = ['++foo', '++foo bar baz'];
    $this->assertEquals('string', $fields['field3']->getType());
    $this->assertEquals($expected, $fields['field3']->getValues());
  }

  /**
   * Tests whether preprocessing of queries without search keys works correctly.
   */
  public function testProcessKeysNoKeys() {
    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);

    $this->processor->preprocessSearchQuery($query);

    $this->assertNull($query->getKeys(), 'Query without keys was correctly ignored.');
  }

  /**
   * Tests whether preprocessing of simple search keys works correctly.
   */
  public function testProcessKeysSimple() {
    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $keys = &$query->getKeys();
    $keys = 'foo';

    $this->processor->preprocessSearchQuery($query);

    $this->assertEquals('*foo', $query->getKeys(), 'Search keys were correctly preprocessed.');
  }

  /**
   * Tests whether preprocessing of complex search keys works correctly.
   */
  public function testProcessKeysComplex() {
    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $keys = &$query->getKeys();
    $keys = [
      '#conjunction' => 'OR',
      'foo',
      [
        '#conjunction' => 'AND',
        'bar',
        'baz',
        '#negation' => TRUE,
      ],
    ];

    $this->processor->preprocessSearchQuery($query);

    $expected = [
      '#conjunction' => 'OR',
      '*foo',
      [
        '#conjunction' => 'AND',
        '*bar',
        '*baz',
        '#negation' => TRUE,
      ],
    ];
    $this->assertEquals($expected, $query->getKeys(), 'Search keys were correctly preprocessed.');
  }

  /**
   * Tests whether overriding of processKey() works correctly.
   */
  public function testProcessKeyOverride() {
    $override = function (&$value) {
      if ($value != 'baz') {
        $value = "&$value";
      }
      else {
        $value = '';
      }
    };
    $this->processor->setMethodOverride('processKey', $override);

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $keys = &$query->getKeys();
    $keys = [
      '#conjunction' => 'OR',
      'foo',
      [
        '#conjunction' => 'AND',
        'bar',
        'baz',
        '#negation' => TRUE,
      ],
    ];

    $this->processor->preprocessSearchQuery($query);

    $expected = [
      '#conjunction' => 'OR',
      '&foo',
      [
        '#conjunction' => 'AND',
        '&bar',
        '#negation' => TRUE,
      ],
    ];
    $this->assertEquals($expected, $query->getKeys(), 'Search keys were correctly preprocessed.');
  }

  /**
   * Tests whether preprocessing search conditions works correctly.
   */
  public function testProcessConditions() {
    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $query->addCondition('text_field', 'foo');
    $query->addCondition('text_field', ['foo', 'bar'], 'IN');
    $query->addCondition('string_field', NULL, '<>');
    $query->addCondition('integer_field', 'bar');

    $this->processor->preprocessSearchQuery($query);

    $expected = [
      new Condition('text_field', '*foo'),
      new Condition('text_field', ['*foo', '*bar'], 'IN'),
      new Condition('string_field', 'undefined', '<>'),
      new Condition('integer_field', 'bar'),
    ];
    $this->assertEquals($expected, $query->getConditionGroup()->getConditions(), 'Conditions were preprocessed correctly.');
  }

  /**
   * Tests whether preprocessing nested search conditions works correctly.
   */
  public function testProcessConditionsNestedConditions() {
    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $conditions = $query->createConditionGroup();
    $conditions->addCondition('text_field', 'foo');
    $conditions->addCondition('text_field', ['foo', 'bar'], 'IN');
    $conditions->addCondition('string_field', NULL, '<>');
    $conditions->addCondition('integer_field', 'bar');
    $query->addConditionGroup($conditions);

    $this->processor->preprocessSearchQuery($query);

    $expected = [
      new Condition('text_field', '*foo'),
      new Condition('text_field', ['*foo', '*bar'], 'IN'),
      new Condition('string_field', 'undefined', '<>'),
      new Condition('integer_field', 'bar'),
    ];
    $this->assertEquals($expected, $query->getConditionGroup()->getConditions()[0]->getConditions(), 'Conditions were preprocessed correctly.');
  }

  /**
   * Tests whether overriding processConditionValue() works correctly.
   */
  public function testProcessConditionValueOverride() {
    $override = function (&$value) {
      if (isset($value)) {
        $value = '';
      }
    };
    $this->processor->setMethodOverride('processConditionValue', $override);

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $query->addCondition('text_field', 'foo');
    $query->addCondition('string_field', NULL, '<>');
    $query->addCondition('integer_field', 'bar');

    $this->processor->preprocessSearchQuery($query);

    $expected = [
      new Condition('string_field', NULL, '<>'),
      new Condition('integer_field', 'bar'),
    ];
    $this->assertEquals($expected, array_merge($query->getConditionGroup()->getConditions()), 'Conditions were preprocessed correctly.');
  }

  /**
   * Tests whether overriding processConditionValue() works correctly.
   */
  public function testProcessConditionValueArrayHandling() {
    $override = function (&$value) {
      $length = strlen($value);
      if ($length == 2) {
        $value = '';
      }
      elseif ($length == 3) {
        $value .= '*';
      }
    };
    $this->processor->setMethodOverride('process', $override);

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($this->index);
    $query->addCondition('text_field', ['a', 'b'], 'NOT IN');
    $query->addCondition('text_field', ['a', 'bo'], 'IN');
    $query->addCondition('text_field', ['ab', 'bo'], 'NOT IN');
    $query->addCondition('text_field', ['a', 'bo'], 'BETWEEN');
    $query->addCondition('text_field', ['ab', 'bo'], 'NOT BETWEEN');
    $query->addCondition('text_field', ['a', 'bar'], 'IN');
    $query->addCondition('text_field', ['abo', 'baz'], 'BETWEEN');

    $this->processor->preprocessSearchQuery($query);

    $expected = [
      new Condition('text_field', ['a', 'b'], 'NOT IN'),
      new Condition('text_field', ['a'], 'IN'),
      new Condition('text_field', ['a', 'bo'], 'BETWEEN'),
      new Condition('text_field', ['ab', 'bo'], 'NOT BETWEEN'),
      new Condition('text_field', ['a', 'bar*'], 'IN'),
      new Condition('text_field', ['abo*', 'baz*'], 'BETWEEN'),
    ];
    $this->assertEquals($expected, array_merge($query->getConditionGroup()->getConditions()), 'Conditions were preprocessed correctly.');
  }

  /**
   * Returns an array with one test item suitable for this test case.
   *
   * @param string[]|null $types
   *   (optional) The types of fields to create. Defaults to using "text",
   *   "string", "integer" and "float".
   *
   * @return \Drupal\search_api\Item\ItemInterface[]
   *   An array containing one item.
   */
  protected function getTestItem($types = NULL) {
    if ($types === NULL) {
      $types = ['text', 'string', 'integer', 'float'];
    }

    $fields = [];
    foreach ($types as $type) {
      $field_id = "{$type}_field";
      $fields[$field_id] = [
        'type' => $type,
        'values' => [
          "$field_id value 1",
          "$field_id value 2",
        ],
      ];
    }
    return $this->createItems($this->index, 1, $fields);
  }

  /**
   * Asserts that the given fields have been correctly processed.
   *
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array containing one item.
   * @param string[] $processed_fields
   *   The fields which should be processed.
   * @param string $prefix
   *   (optional) The prefix that processed fields receive.
   */
  protected function assertFieldsProcessed(array $items, array $processed_fields, $prefix = "*") {
    $processed_fields = array_fill_keys($processed_fields, TRUE);
    foreach ($items as $item) {
      foreach ($item->getFields() as $field_id => $field) {
        if (!empty($processed_fields[$field_id])) {
          $expected = [
            "$prefix$field_id value 1",
            "$prefix$field_id value 2",
          ];
        }
        else {
          $expected = [
            "$field_id value 1",
            "$field_id value 2",
          ];
        }
        $this->assertEquals($expected, $field->getValues(), "Field $field_id is correct.");
      }
    }
  }

}
