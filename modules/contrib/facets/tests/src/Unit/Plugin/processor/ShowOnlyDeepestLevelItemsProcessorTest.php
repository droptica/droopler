<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\ShowOnlyDeepestLevelItemsProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Class ShowOnlyDeepestLevelItemsProcessorTest.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets\Plugin\facets\processor\ShowOnlyDeepestLevelItemsProcessor
 */
class ShowOnlyDeepestLevelItemsProcessorTest extends UnitTestCase {

  /**
   * The processor under test.
   *
   * @var \Drupal\facets\Plugin\facets\processor\ShowOnlyDeepestLevelItemsProcessor
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->processor = new ShowOnlyDeepestLevelItemsProcessor([], 'test', []);
  }

  /**
   * Tests that only items without children survive.
   *
   * @covers ::build
   */
  public function testRemoveItemsWithoutChildren() {
    $facet = new Facet(['id' => 'llama'], 'facets_facet');
    // Setup results.
    $results = [
      new Result($facet, 'a', 'A', 5),
      new Result($facet, 'b', 'B', 2),
      new Result($facet, 'c', 'C', 4),
    ];
    $child = new Result($facet, 'a_1', 'A 1', 3);
    $results[0]->setChildren([$child]);

    // Execute the build method, so we can test the behavior.
    $built_results = $this->processor->build($facet, $results);

    // Sort to have a 0-indexed array.
    sort($built_results);

    // Check the output.
    $this->assertCount(2, $built_results);
    $this->assertSame('b', $built_results[0]->getRawValue());
    $this->assertSame('c', $built_results[1]->getRawValue());
  }

}
