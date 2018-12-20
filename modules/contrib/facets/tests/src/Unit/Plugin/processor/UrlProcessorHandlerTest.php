<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Plugin\facets\processor\UrlProcessorHandler;
use Drupal\facets\UrlProcessor\UrlProcessorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class UrlProcessorHandlerTest extends UnitTestCase {

  /**
   * Tests that the processor correctly throws an exception.
   */
  public function testEmptyProcessorConfiguration() {
    $this->setExpectedException(InvalidProcessorException::class, "The UrlProcessorHandler doesn't have the required 'facet' in the configuration array.");
    new UrlProcessorHandler([], 'test', []);
  }

  /**
   * Tests that the processor correctly throws an exception.
   */
  public function testInvalidProcessorConfiguration() {
    $this->setExpectedException(InvalidProcessorException::class, "The UrlProcessorHandler doesn't have the required 'facet' in the configuration array.");
    new UrlProcessorHandler(['facet' => new \stdClass()], 'test', []);
  }

  /**
   * Tests that the build method is correctly called.
   */
  public function testBuild() {
    $facet = new Facet(['id' => '_test'], 'facets_facet');
    $this->createContainer();

    $processor = new UrlProcessorHandler(['facet' => $facet], 'url_processor_handler', []);
    // The actual results of this should be tested in the actual processor.
    $processor->build($facet, []);
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $facet = new Facet([], 'facets_facet');
    $this->createContainer();
    $processor = new UrlProcessorHandler(['facet' => $facet], 'url_processor_handler', []);

    $config = $processor->defaultConfiguration();
    $this->assertEquals([], $config);
  }

  /**
   * Tests testDescription().
   */
  public function testDescription() {
    $facet = new Facet([], 'facets_facet');
    $this->createContainer();
    $processor = new UrlProcessorHandler(['facet' => $facet], 'url_processor_handler', []);

    $this->assertEquals('', $processor->getDescription());
  }

  /**
   * Tests isHidden().
   */
  public function testIsHidden() {
    $facet = new Facet([], 'facets_facet');
    $this->createContainer();
    $processor = new UrlProcessorHandler(['facet' => $facet], 'url_processor_handler', []);

    $this->assertEquals(FALSE, $processor->isHidden());
  }

  /**
   * Tests isLocked().
   */
  public function testIsLocked() {
    $facet = new Facet([], 'facets_facet');
    $this->createContainer();
    $processor = new UrlProcessorHandler(['facet' => $facet], 'url_processor_handler', []);

    $this->assertEquals(FALSE, $processor->isLocked());
  }

  /**
   * Sets up a container.
   */
  protected function createContainer() {
    $url_processor = $this->getMockBuilder(UrlProcessorInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $manager = $this->getMockBuilder(FacetSourcePluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->exactly(1))
      ->method('createInstance')
      ->willReturn($url_processor);

    $storage = $this->getMock(EntityStorageInterface::class);
    $em = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $em->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity.manager', $em);
    $container->set('entity_type.manager', $em);
    $container->set('plugin.manager.facets.url_processor', $manager);
    \Drupal::setContainer($container);
  }

}
