<?php

namespace Drupal\Tests\facets\Unit\Plugin\query_type;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\GranularItemProcessor;
use Drupal\facets\Plugin\facets\query_type\SearchApiGranular;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\IndexInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\ServerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Unit test for granular query type.
 *
 * @group facets
 */
class SearchApiGranularTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $processor_id = 'granularity_item';
    $processor_definitions = [
      $processor_id => [
        'id' => $processor_id,
        'class' => GranularItemProcessor::class,
      ],
    ];

    $granularityProcessor = new GranularItemProcessor(['granularity' => 10], 'granularity_item', []);

    $processor_manager = $this->prophesize(ProcessorPluginManager::class);
    $processor_manager->getDefinitions()->willReturn($processor_definitions);
    $processor_manager->createInstance('granularity_item', Argument::any())
      ->willReturn($granularityProcessor);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.facets.processor', $processor_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests string query type without executing the query with an "AND" operator.
   */
  public function testQueryTypeAnd() {
    $backend = $this->prophesize(BackendInterface::class);
    $backend->getSupportedFeatures()->willReturn([]);
    $server = $this->prophesize(ServerInterface::class);
    $server->getBackend()->willReturn($backend);
    $index = $this->prophesize(IndexInterface::class);
    $index->getServerInstance()->willReturn($server);
    $query = $this->prophesize(SearchApiQuery::class);
    $query->getIndex()->willReturn($index);

    $facet = new Facet(
      ['query_operator' => 'AND', 'widget' => 'links'],
      'facets_facet'
    );
    $facet->addProcessor([
      'processor_id' => 'granularity_item',
      'weights' => [],
      'settings' => ['granularity' => 10],
    ]);

    // Results for the widget.
    $original_results = [
      ['count' => 3, 'filter' => '2'],
      ['count' => 5, 'filter' => '4'],
      ['count' => 7, 'filter' => '9'],
      ['count' => 9, 'filter' => '11'],
    ];

    // Facets the widget should produce.
    $grouped_results = [
      0 => ['count' => 15, 'filter' => '0'],
      10 => ['count' => 9, 'filter' => 10],
    ];

    $query_type = new SearchApiGranular(
      [
        'facet' => $facet,
        'query' => $query->reveal(),
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertInternalType('array', $results);

    foreach ($grouped_results as $k => $result) {
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

    $query_type = new SearchApiGranular(
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
   * Tests the calculateResultFilter method.
   *
   * @dataProvider provideDataForCalculateResultFilter
   */
  public function testCalculateResultFilter($input, $expected_result) {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'AND', 'widget' => 'links'],
      'facets_facet'
    );
    $facet->addProcessor([
      'processor_id' => 'granularity_item',
      'weights' => [],
      'settings' => [],
    ]);
    $facet->getProcessors()['granularity_item']->setConfiguration([
      'granularity' => 3,
      'min_value' => 5,
      'max_value' => 15,
    ]);

    $query_type = new SearchApiGranular(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_string',
      []
    );

    $result = $query_type->calculateResultFilter($input);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides testdata.
   *
   * @return array
   *   Test data.
   */
  public function provideDataForCalculateResultFilter() {
    return [
      'normal' => [10, ['display' => 8.0, 'raw' => 8.0]],
      'under_min' => [4, FALSE],
      'over_max' => [20, FALSE],
    ];
  }

}
