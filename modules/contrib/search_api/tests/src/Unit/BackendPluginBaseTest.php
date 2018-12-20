<?php

namespace Drupal\Tests\search_api\Unit;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_test\Plugin\search_api\backend\TestBackend;
use Drupal\Tests\UnitTestCase;

/**
 * Tests methods provided by the backend plugin base class.
 *
 * @coversDefaultClass \Drupal\search_api\Backend\BackendPluginBase
 *
 * @group search_api
 */
class BackendPluginBaseTest extends UnitTestCase {

  /**
   * Tests whether fulltext fields are correctly extracted from queries.
   *
   * @param string[]|null $query_fields
   *   The fulltext fields set explicitly on the query, if any.
   * @param string[] $expected
   *   The field IDs that are expected to be returned.
   *
   * @covers ::getQueryFulltextFields
   *
   * @dataProvider getQueryFulltextFieldsDataProvider
   */
  public function testGetQueryFulltextFields($query_fields, array $expected) {
    $index = $this->createMock(IndexInterface::class);
    $index->method('getFulltextFields')->willReturn(['field1', 'field2']);

    $query = $this->createMock(QueryInterface::class);
    $query->method('getFulltextFields')->willReturn($query_fields);
    $query->method('getIndex')->willReturn($index);

    $backend = new TestBackend([], '', []);
    $class = new \ReflectionClass(TestBackend::class);
    $method = $class->getMethod('getQueryFulltextFields');
    $method->setAccessible(TRUE);
    $this->assertEquals($expected, $method->invokeArgs($backend, [$query]));
  }

  /**
   * Provides test data for testGetQueryFulltextFields().
   *
   * @return array[]
   *   An array of argument arrays for testGetQueryFulltextFields().
   */
  public function getQueryFulltextFieldsDataProvider() {
    return [
      'null fields' => [NULL, ['field1', 'field2']],
      'field1' => [['field1'], ['field1']],
      'field2' => [['field2'], ['field2']],
      'all fields' => [['field1', 'field2'], ['field1', 'field2']],
      'invalid fields' => [['field1', 'foo'], ['field1']],
    ];
  }

}
