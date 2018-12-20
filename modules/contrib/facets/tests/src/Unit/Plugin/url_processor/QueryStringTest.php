<?php

namespace Drupal\Tests\facets\Unit\Plugin\url_processor;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Entity\FacetSource;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetSource\FacetSourcePluginInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Plugin\facets\url_processor\QueryString;
use Drupal\facets\Result\Result;
use Drupal\facets\Result\ResultInterface;
use Drupal\Tests\Core\Routing\TestRouterInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class QueryStringTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\Plugin\facets\url_processor\QueryString
   */
  protected $processor;

  /**
   * An array containing the results before the processor has ran.
   *
   * @var \Drupal\facets\Result\Result[]
   */
  protected $originalResults;

  /**
   * A mock of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $facet = new Facet([], 'facets_facet');
    $this->originalResults = [
      new Result($facet, 'llama', 'Llama', 15),
      new Result($facet, 'badger', 'Badger', 5),
      new Result($facet, 'mushroom', 'Mushroom', 5),
      new Result($facet, 'duck', 'Duck', 15),
      new Result($facet, 'alpaca', 'Alpaca', 25),
    ];

    $this->setContainer();
  }

  /**
   * Tests that the processor correctly throws an exception.
   */
  public function testEmptyProcessorConfiguration() {
    $this->setExpectedException(InvalidProcessorException::class, "The url processor doesn't have the required 'facet' in the configuration array.");
    new QueryString([], 'test', [], new Request(), $this->entityManager);
  }

  /**
   * Tests with one active item.
   */
  public function testSetSingleActiveItem() {
    $facet = new Facet([], 'facets_facet');
    $facet->setResults($this->originalResults);
    $facet->setUrlAlias('test');
    $facet->setFieldIdentifier('test');

    $discovery_property = new \ReflectionProperty($facet, 'id');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($facet, 'test');

    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->willReturn([$facet]);
    $entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($storage);

    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);

    $request = new Request();
    $request->query->set('f', ['test:badger']);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], $request, $entityTypeManager);
    $this->processor->setActiveItems($facet);

    $this->assertEquals(['badger'], $facet->getActiveItems());
  }

  /**
   * Tests with multiple active items.
   */
  public function testSetMultipleActiveItems() {
    $facet = new Facet([], 'facets_facet');
    $facet->setResults($this->originalResults);
    $facet->setUrlAlias('test');
    $facet->setFieldIdentifier('test');

    $discovery_property = new \ReflectionProperty($facet, 'id');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($facet, 'test');

    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->expects($this->atLeastOnce())
      ->method('loadByProperties')
      ->willReturnOnConsecutiveCalls([$facet], [$facet], []);
    $entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($storage);

    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);

    $request = new Request();
    $request->query->set('f', ['test:badger', 'test:mushroom', 'donkey:kong']);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], $request, $entityTypeManager);
    $this->processor->setActiveItems($facet);

    $this->assertEquals(['badger', 'mushroom'], $facet->getActiveItems());
  }

  /**
   * Tests with an empty build.
   */
  public function testEmptyBuild() {
    $facet = new Facet([], 'facets_facet');
    $facet->setUrlAlias('test');
    $facet->setFacetSourceId('facet_source__dummy');

    $request = new Request();
    $request->query->set('f', []);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], $request, $this->entityManager);
    $results = $this->processor->buildUrls($facet, []);
    $this->assertEmpty($results);
  }

  /**
   * Tests with default build.
   */
  public function testBuild() {
    $facet = new Facet([], 'facets_facet');
    $facet->setFieldIdentifier('test');
    $facet->setUrlAlias('test');
    $facet->setFacetSourceId('facet_source__dummy');

    $request = new Request();
    $request->query->set('f', []);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], $request, $this->entityManager);
    $results = $this->processor->buildUrls($facet, $this->originalResults);

    $this->assertEquals('f', $this->processor->getFilterKey());

    /** @var \Drupal\facets\Result\ResultInterface $r */
    foreach ($results as $r) {
      $this->assertInstanceOf(ResultInterface::class, $r);
      $this->assertEquals('route:test?f%5B0%5D=test%3A' . $r->getRawValue(), $r->getUrl()->toUriString());
    }
  }

  /**
   * Tests with an active item already from url.
   */
  public function testBuildWithActiveItem() {
    $facet = new Facet([], 'facets_facet');
    $facet->setFieldIdentifier('test');
    $facet->setUrlAlias('test');
    $facet->setFacetSourceId('facet_source__dummy');
    $facet2 = new Facet([], 'facets_facet');
    $facet2->setFieldIdentifier('king');
    $facet2->setUrlAlias('king');
    $facet2->setFacetSourceId('facet_source__dummy');

    $discovery_property = new \ReflectionProperty($facet, 'id');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($facet, 'test');

    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->expects($this->atLeastOnce())
      ->method('loadByProperties')
      ->willReturnOnConsecutiveCalls([$facet2], [$facet2], [$facet2], [$facet2], [$facet2], [$facet2]);
    $entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($storage);

    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);

    $original_results = $this->originalResults;
    $original_results[2]->setActiveState(TRUE);

    $request = new Request();
    $request->query->set('f', ['king:kong']);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], $request, $entityTypeManager);
    $results = $this->processor->buildUrls($facet, $original_results);

    /** @var \Drupal\facets\Result\ResultInterface $r */
    foreach ($results as $k => $r) {
      $this->assertInstanceOf(ResultInterface::class, $r);
      if ($k === 2) {
        $this->assertEquals('route:test?f%5B0%5D=king%3Akong', $r->getUrl()->toUriString());
      }
      else {
        $this->assertEquals('route:test?f%5B0%5D=king%3Akong&f%5B1%5D=test%3A' . $r->getRawValue(), $r->getUrl()->toUriString());
      }
    }
  }

  /**
   * Tests with only one result.
   */
  public function testWithOnlyOneResult() {
    $facet = new Facet([], 'facets_facet');
    $facet->setFieldIdentifier('test');
    $facet->setUrlAlias('test');
    $facet->setFacetSourceId('facet_source__dummy');
    $facet->setShowOnlyOneResult(TRUE);

    $this->originalResults[1]->setActiveState(TRUE);
    $this->originalResults[2]->setActiveState(TRUE);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], new Request(), $this->entityManager);
    $results = $this->processor->buildUrls($facet, $this->originalResults);

    $this->assertEquals('route:test?f%5B0%5D=test%3A' . $results[0]->getRawValue(), $results[0]->getUrl()->toUriString());
    $this->assertEquals('route:test?f%5B0%5D=test%3A' . $results[3]->getRawValue(), $results[3]->getUrl()->toUriString());
    $this->assertEquals('route:test?f%5B0%5D=test%3A' . $results[4]->getRawValue(), $results[4]->getUrl()->toUriString());
    $this->assertEquals('route:test', $results[1]->getUrl()->toUriString());
    $this->assertEquals('route:test', $results[2]->getUrl()->toUriString());
  }

  /**
   * Tests that the facet source configuration filter key override works.
   */
  public function testFacetSourceFilterKeyOverride() {
    $facet_source = new FacetSource(['filter_key' => 'ab'], 'facets_facet_source');

    // Override the container with the new facet source.
    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->willReturn($facet_source);
    $em = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $em->expects($this->any())
      ->method('getStorage')
      ->willReturn($storage);

    $container = \Drupal::getContainer();
    $container->set('entity_type.manager', $em);
    \Drupal::setContainer($container);

    $facet = new Facet([], 'facets_facet');
    $facet->setFieldIdentifier('test');
    $facet->setFacetSourceId('facet_source__dummy');
    $facet->setUrlAlias('test');

    $request = new Request();
    $request->query->set('ab', []);

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], $request, $this->entityManager);
    $results = $this->processor->buildUrls($facet, $this->originalResults);

    /** @var \Drupal\facets\Result\ResultInterface $r */
    foreach ($results as $r) {
      $this->assertInstanceOf(ResultInterface::class, $r);
      $this->assertEquals('route:test?ab%5B0%5D=test%3A' . $r->getRawValue(), $r->getUrl()->toUriString());
    }
  }

  /**
   * Tests that the separator works as expected.
   */
  public function testSeparator() {
    $facet = new Facet([], 'facets_facet');
    $facet->setFieldIdentifier('test');
    $facet->setUrlAlias('test');
    $facet->setFacetSourceId('facet_source__dummy');

    $this->processor = new QueryString(['facet' => $facet, 'separator' => '__'], 'query_string', [], new Request(), $this->entityManager);
    $results = $this->processor->buildUrls($facet, $this->originalResults);

    foreach ($results as $result) {
      $this->assertEquals('route:test?f%5B0%5D=test__' . $result->getRawValue(), $result->getUrl()->toUriString());
    }
  }

  /**
   * Tests that contextual filter get's re-added.
   */
  public function testContextualFilters() {
    // Override router.
    $router = $this->getMockBuilder(TestRouterInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $router->expects($this->any())
      ->method('matchRequest')
      ->willReturn([
        '_raw_variables' => new ParameterBag(['node' => '1']),
        '_route' => 'node_view',
      ]);

    // Get the container from the setUp method and change it with the
    // implementation created here, that has the route parameters.
    $container = \Drupal::getContainer();
    $container->set('router.no_access_checks', $router);
    \Drupal::setContainer($container);

    // Create facet.
    $facet = new Facet([], 'facets_facet');
    $facet->setFieldIdentifier('test');
    $facet->setUrlAlias('test');
    $facet->setFacetSourceId('facet_source__dummy');

    $this->processor = new QueryString(['facet' => $facet], 'query_string', [], new Request(), $this->entityManager);
    $results = $this->processor->buildUrls($facet, $this->originalResults);

    foreach ($results as $result) {
      $this->assertEquals(['node' => 1], $result->getUrl()->getRouteParameters());
    }
  }

  /**
   * Sets up a container.
   */
  protected function setContainer() {
    $router = $this->getMockBuilder(TestRouterInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $router->expects($this->any())
      ->method('matchRequest')
      ->willReturn([
        '_raw_variables' => new ParameterBag([]),
        '_route' => 'test',
      ]);

    $validator = $this->getMock(PathValidatorInterface::class);

    $fsi = $this->getMockBuilder(FacetSourcePluginInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $fsi->method('getPath')
      ->willReturn('test');

    $manager = $this->getMockBuilder(FacetSourcePluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $manager->method('createInstance')
      ->willReturn($fsi);
    $manager->method('hasDefinition')
      ->with('facet_source__dummy')
      ->willReturn(TRUE);

    $facetentity = $this->getMockBuilder(Facet::class)
      ->disableOriginalConstructor()
      ->getMock();
    $facetentity->method('id')
      ->willReturn('king');

    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->willReturn([$facetentity]);
    $em = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $em->expects($this->any())
      ->method('getStorage')
      ->willReturn($storage);
    $this->entityManager = $em;

    $container = new ContainerBuilder();
    $container->set('router.no_access_checks', $router);
    $container->set('plugin.manager.facets.facet_source', $manager);
    $container->set('entity_type.manager', $em);
    $container->set('entity.manager', $em);
    $container->set('path.validator', $validator);
    \Drupal::setContainer($container);
  }

}

namespace Drupal\facets\Plugin\facets\url_processor;

/**
 * Mocks the usage of drupal static.
 *
 * @see \drupal_static
 */
function &drupal_static($name, $default_value = NULL, $reset = FALSE) {
  $data = [];
  return $data;
}
