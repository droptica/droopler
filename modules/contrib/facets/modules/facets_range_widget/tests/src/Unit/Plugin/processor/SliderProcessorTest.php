<?php

namespace Drupal\Tests\facets_range_widget\Unit\Plugin\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
use Drupal\facets\Result\Result;
use Drupal\facets\Widget\WidgetPluginManager;
use Drupal\facets_range_widget\Plugin\facets\processor\SliderProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_range_widget\Plugin\facets\processor\SliderProcessor
 */
class SliderProcessorTest extends UnitTestCase {

  /**
   * The processor we're testing.
   *
   * @var \Drupal\facets\Processor\ProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->processor = new SliderProcessor([], 'slider_processor', []);
  }

  /**
   * Tests the post query method.
   *
   * @covers ::postQuery
   */
  public function testPostQuery() {
    $widgetconfig = ['min_type' => 'foo', 'step' => 1];
    $facet = new Facet([], 'facets_facet');
    $facet->setWidget('raw', $widgetconfig);
    $this->configureContainer($widgetconfig);

    $result_lower = new Result($facet, 5, '5', 1);
    $result_higher = new Result($facet, 150, '150', 1);
    $facet->setResults([$result_lower, $result_higher]);

    // Process the data.
    $startTime = microtime(TRUE);
    $this->processor->postQuery($facet);
    $new_results = $facet->getResults();
    $stopTime = microtime(TRUE);

    if (($stopTime - $startTime) > 1) {
      $this->fail('Test is too slow');
    }

    $this->assertCount(146, $new_results);
    $this->assertEquals(5, $new_results[0]->getRawValue());
    $this->assertEquals(1, $new_results[0]->getCount());
    $this->assertEquals(6, $new_results[1]->getRawValue());
    $this->assertEquals(0, $new_results[1]->getCount());
  }

  /**
   * Tests the post query method with a big dataset.
   *
   * @covers ::postQuery
   */
  public function testPostQueryBigDataSet() {
    $widgetconfig = ['min_type' => 'foo', 'step' => 1];
    $facet = new Facet([], 'facets_facet');
    $facet->setWidget('raw', $widgetconfig);
    $this->configureContainer($widgetconfig);

    $original_results[] = new Result($facet, 1, 'Small', 5);
    foreach (range(100, 100000, 10) as $k) {
      $original_results[] = new Result($facet, $k, 'result ' . $k, 1);
    }
    $original_results[] = new Result($facet, 150000, 'Big', 5);
    $facet->setResults($original_results);

    // Process the data.
    $startTime = microtime(TRUE);
    $this->processor->postQuery($facet);
    $new_results = $facet->getResults();
    $stopTime = microtime(TRUE);

    if (($stopTime - $startTime) > 1) {
      $this->fail('Test is too slow');
    }
    $this->assertCount(150000, $new_results);
  }

  /**
   * Tests the post query method result sorting.
   *
   * @covers ::postQuery
   */
  public function testPostQueryResultSorting() {
    $widgetconfig = ['min_type' => 'foo', 'step' => 1];
    $facet = new Facet([], 'facets_facet');
    $facet->setWidget('raw', $widgetconfig);
    $this->configureContainer($widgetconfig);

    $original_results = [];
    foreach ([10, 100, 200, 5] as $k) {
      $original_results[] = new Result($facet, $k, 'result ' . $k, 1);
    }
    $facet->setResults($original_results);

    // Process the data.
    $this->processor->postQuery($facet);
    $new_results = $facet->getResults();

    $this->assertCount(196, $new_results);
    $this->assertEquals(5, $new_results[0]->getRawValue());
    $this->assertEquals(200, $new_results[195]->getRawValue());
  }

  /**
   * Adds a regression test for the out of range values.
   */
  public function testOutOfRange() {
    $widgetconfig = ['min_type' => 'foo', 'step' => 7];
    $facet = new Facet([], 'facets_facet');
    $facet->setWidget('raw', $widgetconfig);
    $this->configureContainer($widgetconfig);

    $result_lower = new Result($facet, 5, '5', 4);
    $result_higher = new Result($facet, 15, '15', 4);
    $facet->setResults([$result_lower, $result_higher]);

    // Process the data.
    $this->processor->postQuery($facet);
    $new_results = $facet->getResults();

    $this->assertCount(3, $new_results);
    $this->assertEquals(5, $new_results[0]->getRawValue());
    $this->assertEquals(12, $new_results[1]->getRawValue());
    $this->assertEquals(19, $new_results[2]->getRawValue());
  }

  /**
   * Tests the post query method with fixed min/max.
   *
   * @covers ::postQuery
   */
  public function testPostQueryFixedMinMax() {
    $widgetconfig = [
      'min_type' => 'fixed',
      'min_value' => 10,
      'max_value' => 20,
      'step' => 1,
    ];
    $facet = new Facet([], 'facets_facet');
    $facet->setWidget('raw', $widgetconfig);
    $this->configureContainer($widgetconfig);

    $result_lower = new Result($facet, 5, '5', 1);
    $result_higher = new Result($facet, 150, '150', 1);
    $facet->setResults([$result_lower, $result_higher]);

    // Process the data.
    $this->processor->postQuery($facet);
    $new_results = $facet->getResults();

    $this->assertCount(11, $new_results);
  }

  /**
   * Tests the post query method with step > 1.
   *
   * @covers ::postQuery
   */
  public function testPostQueryStep() {
    $widgetconfig = ['min_type' => 'foo', 'step' => 2];
    $facet = new Facet([], 'facets_facet');
    $facet->setWidget('raw', $widgetconfig);
    $this->configureContainer($widgetconfig);

    $result_lower = new Result($facet, 5, '5', 4);
    $result_higher = new Result($facet, 15, '15', 4);
    $facet->setResults([$result_lower, $result_higher]);

    // Process the data.
    $this->processor->postQuery($facet);
    $new_results = $facet->getResults();

    $this->assertCount(6, $new_results);
    $this->assertEquals(5, $new_results[0]->getRawValue());
    $this->assertEquals(4, $new_results[0]->getCount());
    $this->assertEquals(7, $new_results[1]->getRawValue());
    $this->assertEquals(0, $new_results[1]->getCount());
    $this->assertEquals(15, $new_results[5]->getRawValue());
    $this->assertEquals(4, $new_results[5]->getCount());
  }

  /**
   * Configures the container.
   *
   * @param array $config
   *   The config for the widget.
   */
  protected function configureContainer(array $config = []) {
    $widget = $this->prophesize(ArrayWidget::class);
    $widget->getConfiguration()->willReturn($config);
    $pluginManager = $this->prophesize(WidgetPluginManager::class);
    $pluginManager->createInstance('raw', $config)
      ->willReturn($widget->reveal());
    $container = new ContainerBuilder();
    $container->set('plugin.manager.facets.widget', $pluginManager->reveal());
    \Drupal::setContainer($container);
  }

}
