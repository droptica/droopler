<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\ActiveWidgetOrderProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class ActiveWidgetOrderProcessorTest extends UnitTestCase {

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
    /** @var \Drupal\facets\Result\Result[] $original_results */
    $original_results = [
      new Result($facet, 'Boxer', 'Boxer', 10),
      new Result($facet, 'Old Major', 'Old Major', 3),
      new Result($facet, 'Minimus', 'Minimus', 60),
      new Result($facet, 'Mr Whymper', 'Mr. Whymper', 1),
      new Result($facet, 'Clover', 'Clover', 50),
    ];

    $original_results[1]->setActiveState(TRUE);
    $original_results[2]->setActiveState(TRUE);
    $original_results[3]->setActiveState(TRUE);

    $this->originalResults = $original_results;

    $this->processor = new ActiveWidgetOrderProcessor([], 'active_widget_order', []);
  }

  /**
   * Tests sorting.
   */
  public function testSorting() {
    $sort_value = $this->processor->sortResults($this->originalResults[0], $this->originalResults[1]);
    $this->assertEquals(1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[1], $this->originalResults[2]);
    $this->assertEquals(0, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[2], $this->originalResults[3]);
    $this->assertEquals(0, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[3], $this->originalResults[4]);
    $this->assertEquals(-1, $sort_value);

    $sort_value = $this->processor->sortResults($this->originalResults[3], $this->originalResults[3]);
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
