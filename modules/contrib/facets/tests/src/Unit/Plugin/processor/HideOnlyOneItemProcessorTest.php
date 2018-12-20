<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\HideOnlyOneItemProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Class HideOnlyOneItemProcessorTest.
 *
 * @coversDefaultClass Drupal\facets\Plugin\facets\processor\HideOnlyOneItemProcessor
 * @group facets
 */
class HideOnlyOneItemProcessorTest extends UnitTestCase {

  /**
   * Tests with one result.
   *
   * @covers ::build
   */
  public function testWithOneResult() {
    $processor = new HideOnlyOneItemProcessor([], 'hide_only_one_item', []);
    $facet = new Facet([], 'facets_facet');
    $results = [
      new Result($facet, '1', 1, 1),
    ];
    $facet = $this->getMockBuilder(Facet::class)
      ->disableOriginalConstructor()
      ->getMock();
    $processed_results = $processor->build($facet, $results);
    $this->assertCount(0, $processed_results);
  }

  /**
   * Tests with one result that is already active.
   *
   * @covers ::build
   */
  public function testWithOneActiveResult() {
    $processor = new HideOnlyOneItemProcessor([], 'hide_only_one_item', []);
    $facet = new Facet([], 'facets_facet');
    $results = [
      new Result($facet, '1', 1, 1),
    ];
    $results[0]->setActiveState(TRUE);
    $facet = $this->getMockBuilder(Facet::class)
      ->disableOriginalConstructor()
      ->getMock();
    $processed_results = $processor->build($facet, $results);
    $this->assertCount(1, $processed_results);
  }

  /**
   * Tests with one result.
   *
   * @covers ::build
   */
  public function testWithMoreResults() {
    $processor = new HideOnlyOneItemProcessor([], 'hide_only_one_item', []);
    $facet = new Facet([], 'facets_facet');
    $results = [
      new Result($facet, '1', 1, 1),
      new Result($facet, '2', 2, 2),
    ];
    $facet = $this->getMockBuilder(Facet::class)
      ->disableOriginalConstructor()
      ->getMock();
    $processed_results = $processor->build($facet, $results);
    $this->assertCount(2, $processed_results);
  }

}
