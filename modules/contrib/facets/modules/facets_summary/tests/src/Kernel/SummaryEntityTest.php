<?php

namespace Drupal\Tests\facets_summary\Kernel;

use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\facets_summary\Plugin\facets_summary\processor\HideWhenNotRenderedProcessor;
use Drupal\facets_summary\Processor\ProcessorInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class SummaryEntityTest.
 *
 * Tests getters and setters for the Summary entity.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_summary\Entity\FacetsSummary
 */
class SummaryEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'facets_summary',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('facets_facet');
    $this->installEntitySchema('facets_summary');
  }

  /**
   * Tests for getName.
   *
   * @covers ::getName
   */
  public function testName() {
    $entity = new FacetsSummary(['description' => 'Owls', 'name' => 'owl'], 'facets_summary');
    $this->assertEquals('owl', $entity->getName());
  }

  /**
   * Tests for facet sources.
   *
   * @covers ::setFacetSourceId
   * @covers ::getFacetSourceId
   */
  public function testFacetSourceId() {
    $entity = new FacetsSummary(['description' => 'Owls', 'name' => 'owl'], 'facets_summary');
    $source = $entity->setFacetSourceId('foo');
    $this->assertInstanceOf(FacetsSummary::class, $source);

    $this->assertEquals('foo', $entity->getFacetSourceId());
  }

  /**
   * Tests facets.
   *
   * @covers ::setFacets
   * @covers ::getFacets
   * @covers ::removeFacet
   */
  public function testFacets() {
    $entity = new FacetsSummary(['description' => 'Owls', 'name' => 'owl'], 'facets_summary');

    $this->assertEmpty($entity->getFacets());

    $facets = ['foo' => 'bar'];
    $entity->setFacets($facets);
    $this->assertEquals($facets, $entity->getFacets());

    $entity->removeFacet('foo');
    $this->assertEmpty($entity->getFacets());
  }

  /**
   * Tests processor behavior.
   *
   * @covers ::getProcessorsByStage
   * @covers ::getProcessors
   * @covers ::getProcessorConfigs
   * @covers ::addProcessor
   * @covers ::removeProcessor
   * @covers ::loadProcessors
   */
  public function testProcessor() {
    $entity = new FacetsSummary([], 'facets_summary');

    $this->assertEmpty($entity->getProcessorConfigs());
    $this->assertEmpty($entity->getProcessors());
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_BUILD));

    $id = 'hide_when_not_rendered';
    $config = [
      'processor_id' => $id,
      'weights' => [],
      'settings' => [],
    ];
    $entity->addProcessor($config);
    $this->assertEquals([$id => $config], $entity->getProcessorConfigs());

    $this->assertNotEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_BUILD));
    $processors = $entity->getProcessors();
    $this->assertArrayHasKey($id, $processors);
    $this->assertInstanceOf(HideWhenNotRenderedProcessor::class, $processors[$id]);

    $entity->removeProcessor($id);
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_BUILD));
  }

}
