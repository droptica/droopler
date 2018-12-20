<?php

namespace Drupal\Tests\facets_summary\Unit\Plugin\Processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\facets\FacetSource\FacetSourcePluginInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\facets_summary\Plugin\facets_summary\processor\HideWhenNotRenderedProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Class HideWhenNotRenderedProcessorTest.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_summary\Plugin\facets_summary\processor\HideWhenNotRenderedProcessor
 */
class HideWhenNotRenderedProcessorTest extends UnitTestCase {

  /**
   * The processor we're testing.
   *
   * @var \Drupal\facets_summary\Processor\ProcessorInterface|\Drupal\facets_summary\Processor\BuildProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->processor = new HideWhenNotRenderedProcessor([], 'hide_when_not_rendered', []);
  }

  /**
   * Tests the is hidden method.
   *
   * @covers ::isHidden
   */
  public function testIsHidden() {
    $this->assertFalse($this->processor->isHidden());
  }

  /**
   * Tests the is locked method.
   *
   * @covers ::isLocked
   */
  public function testIsLocked() {
    $this->assertFalse($this->processor->isLocked());
  }

  /**
   * Tests the build method, containing the actual work of the processor.
   *
   * @covers ::build
   */
  public function testBuild() {
    $this->createContainer(TRUE);

    $summary = new FacetsSummary([], 'facets_summary');
    $summary->setFacetSourceId('foo');

    $result = $this->processor->build($summary, ['foo'], []);
    $this->assertEquals(['foo'], $result);
  }

  /**
   * Tests the build method, containing the actual work of the processor.
   *
   * @covers ::build
   */
  public function testBuildWithNoCurrentRequest() {
    $this->createContainer(FALSE);

    $summary = new FacetsSummary([], 'facets_summary');
    $summary->setFacetSourceId('foo');

    $result = $this->processor->build($summary, ['foo'], []);
    $this->assertEquals([], $result);
  }

  /**
   * Rendered in current request.
   *
   * @param bool $renderedInCurrentRequestValue
   *   The value for rendered in current request.
   */
  protected function createContainer($renderedInCurrentRequestValue) {
    $fsi = $this->getMockBuilder(FacetSourcePluginInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $fsi->method('isRenderedInCurrentRequest')
      ->willReturn($renderedInCurrentRequestValue);

    $facetSourceManager = $this->getMockBuilder(FacetSourcePluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $facetSourceManager->method('createInstance')
      ->willReturn($fsi);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.facets.facet_source', $facetSourceManager);
    \Drupal::setContainer($container);
  }

}
