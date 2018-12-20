<?php

namespace Drupal\Tests\facets\Kernel\FacetManager;

use Drupal\facets\Entity\Facet;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class DefaultFacetManagerTest.
 *
 * @group facets
 * @coversDefaultClass Drupal\facets\FacetManager\DefaultFacetManager
 */
class DefaultFacetManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'search_api',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('facets_facet');
  }

  /**
   * Tests the getEnabledFacets method.
   *
   * @covers ::getEnabledFacets
   */
  public function testGetEnabledFacets() {
    /** @var \Drupal\facets\FacetManager\DefaultFacetManager $dfm */
    $dfm = \Drupal::service('facets.manager');
    $returnValue = $dfm->getEnabledFacets();
    $this->assertEmpty($returnValue);

    // Create a facet.
    $entity = $this->createAndSaveFacet('test_facet');

    $returnValue = $dfm->getEnabledFacets();
    $this->assertNotEmpty($returnValue);
    $this->assertSame($entity->id(), $returnValue['test_facet']->id());
  }

  /**
   * Tests the getFacetsByFacetSourceId method.
   *
   * @covers ::getFacetsByFacetSourceId
   */
  public function testGetFacetsByFacetSourceId() {
    /** @var \Drupal\facets\FacetManager\DefaultFacetManager $dfm */
    $dfm = \Drupal::service('facets.manager');
    $this->assertEmpty($dfm->getFacetsByFacetSourceId('planets'));

    // Create 2 different facets with a unique facet source id.
    $entity = $this->createAndSaveFacet('Jupiter');
    $entity->setFacetSourceId('planets');
    $entity->save();
    $entity = $this->createAndSaveFacet('Pluto');
    $entity->setFacetSourceId('former_planets');
    $entity->save();

    $planetFacets = $dfm->getFacetsByFacetSourceId('planets');
    $this->assertNotEmpty($planetFacets);
    $this->assertCount(1, $planetFacets);
    $this->assertSame('Jupiter', $planetFacets[0]->id());

    $formerPlanetFacets = $dfm->getFacetsByFacetSourceId('former_planets');
    $this->assertNotEmpty($formerPlanetFacets);
    $this->assertCount(1, $formerPlanetFacets);
    $this->assertSame('Pluto', $formerPlanetFacets[0]->id());

    // Make pluto a planet again.
    $entity->setFacetSourceId('planets');
    $entity->save();

    // Test that we now hit the static cache.
    $planetFacets = $dfm->getFacetsByFacetSourceId('planets');
    $this->assertNotEmpty($planetFacets);
    $this->assertCount(1, $planetFacets);

    // Change the 'facets' property on the manager to public, so we can
    // overwrite it here. This is because otherwise we run into the static
    // caches.
    $facetsProperty = new \ReflectionProperty($dfm, 'facets');
    $facetsProperty->setAccessible(TRUE);
    $facetsProperty->setValue($dfm, []);

    // Now that the static cache is reset, test that we have 2 planets.
    $planetFacets = $dfm->getFacetsByFacetSourceId('planets');
    $this->assertNotEmpty($planetFacets);
    $this->assertCount(2, $planetFacets);
    $this->assertSame('Jupiter', $planetFacets[0]->id());
    $this->assertSame('Pluto', $planetFacets[1]->id());
  }

  /**
   * Create and save a facet, for usage in test-scenario's.
   *
   * @param string $id
   *   The id.
   *
   * @return \Drupal\facets\FacetInterface
   *   The newly created facet.
   */
  protected function createAndSaveFacet($id) {
    // Create a facet.
    $entity = Facet::create([
      'id' => $id,
      'name' => 'Test facet',
    ]);
    $entity->setWidget('links');
    $entity->setEmptyBehavior(['behavior' => 'none']);
    $entity->setFacetSourceId('fluffy');
    $entity->save();

    return $entity;
  }

}
