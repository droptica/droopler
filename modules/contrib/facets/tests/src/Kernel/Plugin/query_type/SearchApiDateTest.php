<?php

namespace Drupal\Tests\facets\Kernel\Plugin\query_type;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\query_type\SearchApiDate;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\ServerInterface;

/**
 * Kernel test for date query type.
 *
 * @group facets
 */
class SearchApiDateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'search_api',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // This is the default set by Drupal as well, but to be explicit we set it
    // here as well. The raw value is the UTC, the displayed value is calculated
    // by the PHP timezone - presently.
    date_default_timezone_set('Australia/Sydney');

    $this->installEntitySchema('facets_facet');
  }

  /**
   * Tests string query type without executing the query with an "AND" operator.
   *
   * @dataProvider resultsProvider
   */
  public function testQueryTypeAnd($granularity, $original_results, $grouped_results) {
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
      'processor_id' => 'date_item',
      'weights' => [],
      'settings' => [
        'granularity' => $granularity,
        'date_format' => '',
        'date_display' => 'actual_date',
      ],
    ]);

    $query_type = new SearchApiDate(
      [
        'facet' => $facet,
        'query' => $query->reveal(),
        'results' => $original_results,
      ],
      'search_api_date',
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
   * Data provider for date results and different groupings.
   */
  public function resultsProvider() {
    return [
      'Year' => [
        SearchApiDate::FACETAPI_DATE_YEAR,
        [
          ['count' => 1, 'filter' => '984711763'],
          ['count' => 1, 'filter' => '1268900542'],
          ['count' => 1, 'filter' => '1269963121'],
          ['count' => 1, 'filter' => '1306314733'],
          ['count' => 1, 'filter' => '1464167533'],
          ['count' => 2, 'filter' => '1464167534'],
          ['count' => 1, 'filter' => '1464172214'],
          ['count' => 1, 'filter' => '1464174734'],
          ['count' => 1, 'filter' => '1464202800'],
          ['count' => 1, 'filter' => '1464250210'],
          ['count' => 1, 'filter' => '1464250230'],
          ['count' => 1, 'filter' => '1464926723'],
          ['count' => 1, 'filter' => '1465930475'],
        ],
        [
          '2001' => ['count' => 1, 'filter' => 2001],
          '2010' => ['count' => 2, 'filter' => 2010],
          '2011' => ['count' => 1, 'filter' => 2011],
          '2016' => ['count' => 10, 'filter' => 2016],
        ],
      ],
      'Month' => [
        SearchApiDate::FACETAPI_DATE_MONTH,
        [
          ['count' => 1, 'filter' => '984711763'],
          ['count' => 1, 'filter' => '1268900542'],
          ['count' => 1, 'filter' => '1269963121'],
          ['count' => 1, 'filter' => '1306314733'],
          ['count' => 1, 'filter' => '1464167533'],
          ['count' => 2, 'filter' => '1464167534'],
          ['count' => 1, 'filter' => '1464172214'],
          ['count' => 1, 'filter' => '1464174734'],
          ['count' => 1, 'filter' => '1464202800'],
          ['count' => 1, 'filter' => '1464250210'],
          ['count' => 1, 'filter' => '1464250230'],
          ['count' => 1, 'filter' => '1464926723'],
          ['count' => 1, 'filter' => '1465930475'],
        ],
        [
          '2001-03' => ['count' => 1, 'filter' => 'March 2001'],
          '2010-03' => ['count' => 2, 'filter' => 'March 2010'],
          '2011-05' => ['count' => 1, 'filter' => 'May 2011'],
          '2016-05' => ['count' => 8, 'filter' => 'May 2016'],
          '2016-06' => ['count' => 2, 'filter' => 'June 2016'],
        ],
      ],
      'Day' => [
        SearchApiDate::FACETAPI_DATE_DAY,
        [
          ['count' => 1, 'filter' => '984711763'],
          ['count' => 1, 'filter' => '1268900542'],
          ['count' => 1, 'filter' => '1269963121'],
          ['count' => 1, 'filter' => '1306314733'],
          ['count' => 1, 'filter' => '1464167533'],
          ['count' => 2, 'filter' => '1464167534'],
          ['count' => 1, 'filter' => '1464172214'],
          ['count' => 1, 'filter' => '1464174734'],
          ['count' => 1, 'filter' => '1464202800'],
          ['count' => 1, 'filter' => '1464250210'],
          ['count' => 1, 'filter' => '1464250230'],
          ['count' => 1, 'filter' => '1464926723'],
          ['count' => 1, 'filter' => '1465930475'],
        ],
        [
          '2001-03-16' => ['count' => 1, 'filter' => '16 March 2001'],
          '2010-03-18' => ['count' => 1, 'filter' => '18 March 2010'],
          '2010-03-31' => ['count' => 1, 'filter' => '31 March 2010'],
          '2011-05-25' => ['count' => 1, 'filter' => '25 May 2011'],
          '2016-05-25' => ['count' => 5, 'filter' => '25 May 2016'],
          '2016-05-26' => ['count' => 3, 'filter' => '26 May 2016'],
          '2016-06-03' => ['count' => 1, 'filter' => '03 June 2016'],
          '2016-06-15' => ['count' => 1, 'filter' => '15 June 2016'],
        ],
      ],
      'Hour' => [
        SearchApiDate::FACETAPI_DATE_HOUR,
        [
          ['count' => 1, 'filter' => '984711763'],
          ['count' => 1, 'filter' => '1268900542'],
          ['count' => 1, 'filter' => '1269963121'],
          ['count' => 1, 'filter' => '1306314733'],
          ['count' => 1, 'filter' => '1464167533'],
          ['count' => 2, 'filter' => '1464167534'],
          ['count' => 1, 'filter' => '1464172214'],
          ['count' => 1, 'filter' => '1464174734'],
          ['count' => 1, 'filter' => '1464202800'],
          ['count' => 1, 'filter' => '1464250210'],
          ['count' => 1, 'filter' => '1464250230'],
          ['count' => 1, 'filter' => '1464926723'],
          ['count' => 1, 'filter' => '1465930475'],
        ],
        [
          '2001-03-16T14' => ['count' => 1, 'filter' => '16/03/2001 14h'],
          '2010-03-18T19' => ['count' => 1, 'filter' => '18/03/2010 19h'],
          '2010-03-31T02' => ['count' => 1, 'filter' => '31/03/2010 02h'],
          '2011-05-25T19' => ['count' => 1, 'filter' => '25/05/2011 19h'],
          '2016-05-25T19' => ['count' => 3, 'filter' => '25/05/2016 19h'],
          '2016-05-25T20' => ['count' => 1, 'filter' => '25/05/2016 20h'],
          '2016-05-25T21' => ['count' => 1, 'filter' => '25/05/2016 21h'],
          '2016-05-26T05' => ['count' => 1, 'filter' => '26/05/2016 05h'],
          '2016-05-26T18' => ['count' => 2, 'filter' => '26/05/2016 18h'],
          '2016-06-03T14' => ['count' => 1, 'filter' => '03/06/2016 14h'],
          '2016-06-15T04' => ['count' => 1, 'filter' => '15/06/2016 04h'],
        ],
      ],
      'Minute' => [
        SearchApiDate::FACETAPI_DATE_MINUTE,
        [
          ['count' => 1, 'filter' => '984711763'],
          ['count' => 1, 'filter' => '1268900542'],
          ['count' => 1, 'filter' => '1269963121'],
          ['count' => 1, 'filter' => '1306314733'],
          ['count' => 1, 'filter' => '1464167533'],
          ['count' => 2, 'filter' => '1464167534'],
          ['count' => 1, 'filter' => '1464172214'],
          ['count' => 1, 'filter' => '1464174734'],
          ['count' => 1, 'filter' => '1464202800'],
          ['count' => 1, 'filter' => '1464250210'],
          ['count' => 1, 'filter' => '1464250230'],
          ['count' => 1, 'filter' => '1464926723'],
          ['count' => 1, 'filter' => '1465930475'],
        ],
        [
          '2001-03-16T14:02' => ['count' => 1, 'filter' => '16/03/2001 14:02'],
          '2010-03-18T19:22' => ['count' => 1, 'filter' => '18/03/2010 19:22'],
          '2010-03-31T02:32' => ['count' => 1, 'filter' => '31/03/2010 02:32'],
          '2011-05-25T19:12' => ['count' => 1, 'filter' => '25/05/2011 19:12'],
          '2016-05-25T19:12' => ['count' => 3, 'filter' => '25/05/2016 19:12'],
          '2016-05-25T20:30' => ['count' => 1, 'filter' => '25/05/2016 20:30'],
          '2016-05-25T21:12' => ['count' => 1, 'filter' => '25/05/2016 21:12'],
          '2016-05-26T05:00' => ['count' => 1, 'filter' => '26/05/2016 05:00'],
          '2016-05-26T18:10' => ['count' => 2, 'filter' => '26/05/2016 18:10'],
          '2016-06-03T14:05' => ['count' => 1, 'filter' => '03/06/2016 14:05'],
          '2016-06-15T04:54' => ['count' => 1, 'filter' => '15/06/2016 04:54'],
        ],
      ],
      'Second' => [
        SearchApiDate::FACETAPI_DATE_SECOND,
        [
          ['count' => 1, 'filter' => '984711763'],
          ['count' => 1, 'filter' => '1268900542'],
          ['count' => 1, 'filter' => '1269963121'],
          ['count' => 1, 'filter' => '1306314733'],
          ['count' => 1, 'filter' => '1464167533'],
          ['count' => 2, 'filter' => '1464167534'],
          ['count' => 1, 'filter' => '1464172214'],
          ['count' => 1, 'filter' => '1464174734'],
          ['count' => 1, 'filter' => '1464202800'],
          ['count' => 1, 'filter' => '1464250210'],
          ['count' => 1, 'filter' => '1464250230'],
          ['count' => 1, 'filter' => '1464926723'],
          ['count' => 1, 'filter' => '1465930475'],
        ],
        [
          '2001-03-16T14:02:43' => ['count' => 1, 'filter' => '16/03/2001 14:02:43'],
          '2010-03-18T19:22:22' => ['count' => 1, 'filter' => '18/03/2010 19:22:22'],
          '2010-03-31T02:32:01' => ['count' => 1, 'filter' => '31/03/2010 02:32:01'],
          '2011-05-25T19:12:13' => ['count' => 1, 'filter' => '25/05/2011 19:12:13'],
          '2016-05-25T19:12:13' => ['count' => 1, 'filter' => '25/05/2016 19:12:13'],
          '2016-05-25T19:12:14' => ['count' => 2, 'filter' => '25/05/2016 19:12:14'],
          '2016-05-25T20:30:14' => ['count' => 1, 'filter' => '25/05/2016 20:30:14'],
          '2016-05-25T21:12:14' => ['count' => 1, 'filter' => '25/05/2016 21:12:14'],
          '2016-05-26T05:00:00' => ['count' => 1, 'filter' => '26/05/2016 05:00:00'],
          '2016-05-26T18:10:10' => ['count' => 1, 'filter' => '26/05/2016 18:10:10'],
          '2016-05-26T18:10:30' => ['count' => 1, 'filter' => '26/05/2016 18:10:30'],
          '2016-06-03T14:05:23' => ['count' => 1, 'filter' => '03/06/2016 14:05:23'],
          '2016-06-15T04:54:35' => ['count' => 1, 'filter' => '15/06/2016 04:54:35'],
        ],
      ],
    ];
  }

  /**
   * Tests string query type without results.
   */
  public function testEmptyResults() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $facet->addProcessor([
      'processor_id' => 'date_item',
      'weights' => [],
      'settings' => [
        'granularity' => SearchApiDate::FACETAPI_DATE_YEAR,
        'date_format' => '',
        'date_display' => 'actual_date',
      ],
    ]);

    $query_type = new SearchApiDate(
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

}
