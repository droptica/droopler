<?php

namespace Drupal\Tests\facets\Unit\Plugin\query_type;

use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\query_type\SearchApiString;
use Drupal\facets\Result\ResultInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for query type.
 *
 * @group facets
 */
class SearchApiStringTest extends UnitTestCase {

  /**
   * Tests string query type without executing the query with an "AND" operator.
   */
  public function testQueryTypeAnd() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'and'],
      'facets_facet'
    );

    $original_results = [
      ['count' => 3, 'filter' => 'badger'],
      ['count' => 5, 'filter' => 'mushroom'],
      ['count' => 7, 'filter' => 'narwhal'],
      ['count' => 9, 'filter' => 'unicorn'],
    ];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf(ResultInterface::class, $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests string query type without executing the query with an "OR" operator.
   */
  public function testQueryTypeOr() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'or'],
      'facets_facet'
    );
    $facet->setFieldIdentifier('field_animal');

    $original_results = [
      ['count' => 3, 'filter' => 'badger'],
      ['count' => 5, 'filter' => 'mushroom'],
      ['count' => 7, 'filter' => 'narwhal'],
      ['count' => 9, 'filter' => 'unicorn'],
    ];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf(ResultInterface::class, $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests string query type without results.
   */
  public function testEmptyResults() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);
    $this->assertEmpty($results);
  }

  /**
   * Tests string query type without results.
   */
  public function testConfiguration() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $default_config = ['facet' => $facet, 'query' => $query];
    $query_type = new SearchApiString($default_config, 'search_api_string', []);

    $this->assertEquals([], $query_type->defaultConfiguration());
    $this->assertEquals($default_config, $query_type->getConfiguration());

    $query_type->setConfiguration(['owl' => 'Long-eared owl']);
    $this->assertEquals(['owl' => 'Long-eared owl'], $query_type->getConfiguration());
  }

  /**
   * Tests trimming in ::build.
   *
   * @dataProvider provideTrimValues
   */
  public function testTrim($expected_value, $input_value) {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $original_results = [['count' => 1, 'filter' => $input_value]];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);

    $this->assertInstanceOf(ResultInterface::class, $results[0]);
    $this->assertEquals(1, $results[0]->getCount());
    $this->assertEquals($expected_value, $results[0]->getDisplayValue());
  }

  /**
   * Data provider for ::provideTrimValues.
   *
   * @return array
   *   An array of expected and input values.
   */
  public function provideTrimValues() {
    return [
      ['owl', '"owl"'],
      ['owl', 'owl'],
      ['owl', '"owl'],
      ['owl', 'owl"'],
      ['"owl', '""owl"'],
      ['owl"', '"owl""'],
    ];
  }

}
