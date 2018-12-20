<?php

namespace Drupal\Tests\facets_range_widget\Unit\Plugin\processor;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\UrlProcessorHandler;
use Drupal\facets\Plugin\facets\url_processor\QueryString;
use Drupal\facets\Result\Result;
use Drupal\facets\Utility\FacetsUrlGenerator;
use Drupal\facets_range_widget\Plugin\facets\processor\RangeSliderProcessor;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Unit test for processor.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_range_widget\Plugin\facets\processor\RangeSliderProcessor
 */
class RangeSliderProcessorTest extends UnitTestCase {

  /**
   * The processor we're testing.
   *
   * @var \Drupal\facets_range_widget\Plugin\facets\processor\RangeSliderProcessor
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->processor = new RangeSliderProcessor([], 'range_slider', []);

    $facets_url_generator = $this->prophesize(FacetsUrlGenerator::class);
    $facets_url_generator->getUrl(Argument::any(), Argument::any())->willReturn(new Url('test', [], ['query' => ['f' => ['animals::(min:__range_slider_min__,max:__range_slider_max__)']]]));
    $url_generator = $this->prophesize(UrlGeneratorInterface::class);

    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator->reveal());
    $container->set('facets.utility.url_generator', $facets_url_generator->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the pre query method.
   *
   * @covers ::preQuery
   */
  public function testPreQuery() {
    $facet = new Facet(['id' => 'llama'], 'facets_facet');
    $facet->setActiveItems(['(min:2,max:10)']);

    $this->processor->preQuery($facet);

    $this->assertCount(2, $facet->getActiveItems()[0]);
    $this->assertEquals([2, 10], $facet->getActiveItems()[0]);
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuild() {
    // Create the Url processor.
    $queryString = $this->prophesize(QueryString::class);
    $queryString->getFilterKey()->willReturn('f');
    $queryString->getSeparator()->willReturn('::');
    $queryString->getActiveFilters()->willReturn([]);
    $urlHandler = $this->prophesize(UrlProcessorHandler::class);
    $urlHandler->getProcessor()->willReturn($queryString->reveal());

    $facet = $this->prophesize(Facet::class);
    $facet->getProcessors()->willReturn(['url_processor_handler' => $urlHandler->reveal()]);
    $facet->getUrlAlias()->willReturn('animals');
    $facet->id()->willReturn('animals');

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = [
      new Result($facet->reveal(), 1, 1, 1),
      new Result($facet->reveal(), 5, 5, 5),
    ];
    $results[0]->setUrl(new Url('test'));
    $results[1]->setUrl(new Url('test'));

    $new_results = $this->processor->build($facet->reveal(), $results);

    $this->assertCount(2, $new_results);
    $params = UrlHelper::buildQuery(['f' => ['animals::(min:__range_slider_min__,max:__range_slider_max__)']]);
    $expected_route = 'route:test?' . $params;
    $this->assertEquals($expected_route, $new_results[0]->getUrl()->toUriString());
    $this->assertEquals($expected_route, $new_results[1]->getUrl()->toUriString());
  }

}
