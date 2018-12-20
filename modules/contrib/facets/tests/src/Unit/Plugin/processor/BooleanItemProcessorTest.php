<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\BooleanItemProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class BooleanItemProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\BuildProcessorInterface
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
      new Result($facet, 0, 0, 10),
      new Result($facet, 1, 1, 15),
    ];

    $this->processor = new BooleanItemProcessor([], 'boolean_item_processor', []);
  }

  /**
   * Tests filtering of results.
   */
  public function testBuild() {
    $facet = new Facet([], 'facets_facet');
    $facet->setResults($this->originalResults);

    $filtered_results = $this->processor->build($facet, $this->originalResults);

    // The default values for on / off are On and Off.
    $this->assertEquals('Off', $filtered_results[0]->getDisplayValue());
    $this->assertEquals('On', $filtered_results[1]->getDisplayValue());

    // Overwrite the on/off values.
    $configuration = ['on_value' => 'True', 'off_value' => 'False'];
    $this->processor->setConfiguration($configuration);

    $filtered_results = $this->processor->build($facet, $this->originalResults);
    $this->assertEquals('False', $filtered_results[0]->getDisplayValue());
    $this->assertEquals('True', $filtered_results[1]->getDisplayValue());
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals(['on_value' => 'On', 'off_value' => 'Off'], $config);
  }

  /**
   * Tests testDescription().
   */
  public function testDescription() {
    $this->assertEquals('', $this->processor->getDescription());
  }

  /**
   * Tests isHidden().
   */
  public function testIsHidden() {
    $this->assertEquals(FALSE, $this->processor->isHidden());
  }

  /**
   * Tests isLocked().
   */
  public function testIsLocked() {
    $this->assertEquals(FALSE, $this->processor->isLocked());
  }

}
