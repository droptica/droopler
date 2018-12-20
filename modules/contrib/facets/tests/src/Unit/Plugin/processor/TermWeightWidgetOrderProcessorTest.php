<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\TermWeightWidgetOrderProcessor;
use Drupal\facets\Result\Result;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class TermWeightWidgetOrderProcessorTest extends UnitTestCase {

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
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * Mocked term, used for comparison.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $termA;

  /**
   * Mocked term, used for comparison.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $termB;

  /**
   * Mocked entity (term) storage.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $termStorage;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    // Build up a chain of mocks that we will have the processor use to fetch
    // the weight of the terms that are being compared.
    $this->termStorage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->termStorage);

    // Instantiate the processor and load it up with our mock chain.
    $this->processor = new TermWeightWidgetOrderProcessor([], 'term_weight_widget_order', [], $this->entityTypeManager);

    // Setup two mock terms that will be set up to have specific weights before
    // the processor is used to compare them.
    // The mocks are used in the individual tests.
    $this->termA = $this->getMockBuilder(Term::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->termB = $this->getMockBuilder(Term::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Prepare the terms that will be returned when the processor loads its list
    // of term-ids from the Results raw values.
    $terms = [
      1 => $this->termA,
      2 => $this->termB,
    ];

    // Setup the termStorage mock to return our terms. As we keep a reference to
    // the terms via $this the individual tests can set up the weights later.
    $this->termStorage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($terms);

    // Prepare the results that we use the processor to sort, the raw_value has
    // to match the term_id keys used above in $terms. Display_value and count
    // is not used.
    $facet = new Facet([], 'facets_facet');
    $this->originalResults = [
      new Result($facet, 1, 10, 100),
      new Result($facet, 2, 20, 200),
    ];

  }

  /**
   * Tests that sorting two terms of equal weight yields 0.
   */
  public function testEqual() {
    $this->termA->expects($this->any())
      ->method('getWeight')
      ->willReturn('1');

    $this->termB->expects($this->any())
      ->method('getWeight')
      ->willReturn('1');

    $sort_value = $this->processor->sortResults($this->originalResults[0], $this->originalResults[1]);
    $this->assertEquals(0, $sort_value);
  }

  /**
   * Compare a term with a high weight with a term with a low.
   */
  public function testHigher() {
    $this->termA->expects($this->any())
      ->method('getWeight')
      ->willReturn('10');

    $this->termB->expects($this->any())
      ->method('getWeight')
      ->willReturn('-10');

    $sort_value = $this->processor->sortResults($this->originalResults[0], $this->originalResults[1]);
    $this->assertGreaterThan(0, $sort_value);
  }

  /**
   * Compare a term with a low weight with a term with a high.
   */
  public function testLow() {
    $this->termA->expects($this->any())
      ->method('getWeight')
      ->willReturn('-10');

    $this->termB->expects($this->any())
      ->method('getWeight')
      ->willReturn('10');

    // Compare the two values and check the result with an assertion.
    $sort_value = $this->processor->sortResults($this->originalResults[0], $this->originalResults[1]);
    $this->assertLessThan(0, $sort_value);
  }

}
