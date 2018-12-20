<?php

namespace Drupal\Tests\facets_summary\Unit\Plugin\Processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\facets_summary\Plugin\facets_summary\processor\ShowTextWhenEmptyProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Class ShowTextWhenEmptyProcessorTest.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_summary\Plugin\facets_summary\processor\ShowTextWhenEmptyProcessor
 */
class ShowTextWhenEmptyProcessorTest extends UnitTestCase {

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
    $string_translation = $this->prophesize(TranslationInterface::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $string_translation->reveal());
    \Drupal::setContainer($container);

    $this->processor = new ShowTextWhenEmptyProcessor([], 'show_text_when_empty', []);
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
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuild() {
    $summary = new FacetsSummary([], 'facets_summary');
    $summary->setFacetSourceId('foo');

    $result = $this->processor->build($summary, ['foo'], []);
    $this->assertInternalType('array', $result);
    $this->assertArrayHasKey('#theme', $result);
    $this->assertEquals('facets_summary_empty', $result['#theme']);
    $this->assertArrayHasKey('#message', $result);

    $configuration = [
      'text' => [
        'value' => 'llama',
        'format' => 'html',
      ],
    ];
    $this->processor->setConfiguration($configuration);
    $result = $this->processor->build($summary, ['foo'], []);
    $this->assertEquals('llama', $result['#message']['#text']);
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuildWithEmptyItems() {
    $summary = new FacetsSummary([], 'facets_summary');
    $summary->setFacetSourceId('foo');

    $build = ['#items' => []];
    $result = $this->processor->build($summary, $build, []);
    $this->assertInternalType('array', $result);
    $this->assertArrayHasKey('#theme', $result);
    $this->assertEquals('facets_summary_empty', $result['#theme']);
    $this->assertArrayHasKey('#message', $result);
    $this->assertArrayHasKey('#text', $result['#message']);
    $this->assertEquals(new TranslatableMarkup('No results found.'), (string) $result['#message']['#text']);
    $this->assertEquals('plain_text', $result['#message']['#format']);
  }

  /**
   * Tests build with config changes.
   *
   * @covers ::build
   */
  public function testBuildWithConfigChange() {
    $summary = new FacetsSummary([], 'facets_summary');
    $summary->setFacetSourceId('foo');

    $build = ['#items' => []];
    $this->processor->setConfiguration(['text' => ['value' => 'Owl', 'format' => 'llama']]);
    $result = $this->processor->build($summary, $build, []);
    $this->assertInternalType('array', $result);
    $this->assertArrayHasKey('#text', $result['#message']);
    $this->assertEquals('Owl', (string) $result['#message']['#text']);
    $this->assertEquals('llama', $result['#message']['#format']);
  }

}
