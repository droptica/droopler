<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\RawValueWidgetOrderProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class RawValueWidgetOrderProcessorTest extends UnitTestCase {

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
      new Result($facet, 'C', 'thetans', 10),
      new Result($facet, 'B', 'xenu', 5),
      new Result($facet, 'A', 'Tom', 15),
      new Result($facet, 'D', 'Hubbard', 666),
      new Result($facet, 'E', 'FALSE', 1),
      new Result($facet, 'G', '1977', 20),
      new Result($facet, 'F', '2', 22),
    ];

    $this->processor = new RawValueWidgetOrderProcessor([], 'raw_value_widget_order', []);
  }

  /**
   * Tests sorting.
   */
  public function testSorting() {
    $sort_value = $this->processor->sortResults($this->originalResults[0], $this->originalResults[1]);
    $this->assertEquals(1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[1], $this->originalResults[2]);
    $this->assertEquals(1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[2], $this->originalResults[3]);
    $this->assertEquals(-1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[3], $this->originalResults[4]);
    $this->assertEquals(-1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[4], $this->originalResults[5]);
    $this->assertEquals(-1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[5], $this->originalResults[6]);
    $this->assertEquals(1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[3], $this->originalResults[3]);
    $this->assertEquals(0, $sort_value);
  }

  /**
   * Tests configuration.
   */
  public function testDefaultConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals(['sort' => 'ASC'], $config);
  }

}
