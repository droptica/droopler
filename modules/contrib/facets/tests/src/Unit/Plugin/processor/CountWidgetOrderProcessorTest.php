<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\CountWidgetOrderProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class CountWidgetOrderProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\Processor\SortProcessorInterface
   */
  protected $processor;

  /**
   * An array containing the results before the processor has ran.
   *
   * @var \Drupal\facets\Result\Result[]
   */
  protected $originalResults;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $facet = new Facet([], 'facets_facet');
    $this->originalResults = [
      new Result($facet, 'llama', 'llama', 10),
      new Result($facet, 'badger', 'badger', 5),
      new Result($facet, 'duck', 'duck', 15),
    ];

    $this->processor = new CountWidgetOrderProcessor([], 'count_widget_order', []);
  }

  /**
   * Tests sorting.
   */
  public function testSorting() {
    $sort_value = $this->processor->sortResults($this->originalResults[0], $this->originalResults[1]);
    $this->assertEquals(1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[1], $this->originalResults[2]);
    $this->assertEquals(-1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[2], $this->originalResults[2]);
    $this->assertEquals(0, $sort_value);
  }

  /**
   * Tests configuration.
   */
  public function testDefaultConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals(['sort' => 'DESC'], $config);
  }

}
