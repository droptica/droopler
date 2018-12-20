<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\Plugin\facets\processor\DependentFacetProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class DependentFacetProcessorTest extends UnitTestCase {

  /**
   * An array of results.
   *
   * @var \Drupal\facets\Result\ResultInterface[]
   */
  protected $results;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $facet = new Facet([], 'facets_facet');
    $this->results = [
      new Result($facet, 'snow_owl', 'Snow owl', 2),
      new Result($facet, 'forest_owl', 'Forest owl', 3),
      new Result($facet, 'sand_owl', 'Sand owl', 8),
      new Result($facet, 'church_owl', 'Church owl', 1),
      new Result($facet, 'barn_owl', 'Barn owl', 1),
    ];
  }

  /**
   * Tests to no-config case.
   */
  public function testNotConfigured() {
    $facetManager = $this->prophesize(DefaultFacetManager::class)
      ->reveal();
    $etm = $this->prophesize(EntityTypeManagerInterface::class)
      ->reveal();
    $dfp = new DependentFacetProcessor([], 'dependent_facet_processor', [], $facetManager, $etm);

    $facet = new Facet(['id' => 'owl', 'name' => 'øwl'], 'facets_facet');

    $computed = $dfp->build($facet, $this->results);
    $this->assertEquals($computed, $this->results);
  }

  /**
   * Tests the case where no facets are enabled.
   */
  public function testNoEnabledFacets() {
    $facetManager = $this->prophesize(DefaultFacetManager::class)
      ->reveal();
    $etm = $this->prophesize(EntityTypeManagerInterface::class)
      ->reveal();
    $configuration = ['owl' => ['enable' => FALSE, 'condition' => 'not_empty']];
    $dfp = new DependentFacetProcessor($configuration, 'dependent_facet_processor', [], $facetManager, $etm);

    $facet = new Facet(['id' => 'owl', 'name' => 'øwl'], 'facets_facet');

    $computed = $dfp->build($facet, $this->results);
    $this->assertEquals($computed, $this->results);
  }

  /**
   * Tests that facet is not empty.
   *
   * @dataProvider provideNegated
   */
  public function testNotEmpty($negated) {
    $facet = new Facet(['id' => 'owl', 'name' => 'øwl'], 'facets_facet');
    $facet->setActiveItem('snow_owl');

    $facetManager = $this->prophesize(DefaultFacetManager::class);
    $facetManager->returnProcessedFacet($facet)->willReturn($facet);

    $entityStorage = $this->prophesize(EntityStorageInterface::class);
    $entityStorage->load('owl')->willReturn($facet);

    $etm = $this->prophesize(EntityTypeManagerInterface::class);
    $etm->getStorage('facets_facet')->willReturn($entityStorage->reveal());

    $configuration = [
      'owl' => [
        'enable' => TRUE,
        'negate' => $negated,
        'condition' => 'not_empty',
      ],
    ];
    $dfp = new DependentFacetProcessor($configuration, 'dependent_facet_processor', [], $facetManager->reveal(), $etm->reveal());

    $computed = $dfp->build($facet, $this->results);

    if ($negated) {
      $this->assertEquals($computed, []);
    }
    else {
      $this->assertEquals($computed, $this->results);
    }
  }

  /**
   * Provides test cases with data.
   *
   * @return array
   *   An array of test data.
   */
  public function provideNegated() {
    return [
      'negated' => [TRUE],
      'normal' => [FALSE],
    ];
  }

}
